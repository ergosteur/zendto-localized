<?PHP
//
// ZendTo
// Copyright (C) 2006 Jeffrey Frey, frey at udel dot edu
// Copyright (C) 2010 Julian Field, Jules at ZendTo dot com 
//
// Based on the original PERL dropbox written by Doke Scott.
// Developed by Julian Field.
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//
//

// JKF Uncomment the next 2 lines for loads of debug information.
// error_reporting(E_ALL);
// ini_set("display_errors", 1);

require_once(NSSDROPBOX_LIB_DIR."NSSAuthenticator.php");
require_once(NSSDROPBOX_LIB_DIR."NSSUtils.php");
require_once(NSSDROPBOX_LIB_DIR."wordlist.php");

//
// There are sooo many places where it would be nice to have the base URL
// for this site, so we can just tack-on a page or GET directives.  We
// form it quite simply by concatenating a couple of the SERVER fields.
// There may be other ways (other SERVER fields) that would work better
// for this, but the code-fu below is adequate:
//
$port = $_SERVER['SERVER_PORT'];
$https = $_SERVER['HTTPS'];
if (($https && $port==443) || (!$https && $port==80)) {
  $port = '';
} else {
  $port = ":$port";
}
$NSSDROPBOX_URL = "http".($_SERVER['HTTPS'] ? "s" : "")."://".$_SERVER['SERVER_NAME'].$port.$_SERVER['REQUEST_URI'];
if ( !preg_match('/\/$/',$NSSDROPBOX_URL) ) {
  $NSSDROPBOX_URL = preg_replace('/\/[^\/]+$/',"/",$NSSDROPBOX_URL);
}

/*!

  @class NSSDropbox
  
  An instance of NSSDropbox serves as the parent for all dropped-off "stuff".
  The instance also acts as a container for the site's preferences; the
  connection to the SQLite database backing the dropbox; and the authenticator
  used to validate and authenticate users.  Accessors are provided for all of
  the instance data (some are read-only, but many of the preference fields
  are read-write), and some methods are implemented to handle common actions
  on the instance data.
*/
class NSSDropbox {

  //  Instance data:
  private $_dropboxDirectory;
  private $_dropboxLog = '/var/zendto/zendto.log';
  private $_retainDays = 14;
  private $_recaptchaPublicKey;
  private $_recaptchaPrivateKey;
  private $_emailDomainRegexp;
  private $_secretForCookies;
  private $_cookieName = "dropbox_session";
  private $_cookieTTL = 900;
  private $_requestTTL = 200000;
  
  private $_maxBytesForFile = 1048576000.0;  //  1000 MB
  private $_maxBytesForDropoff = 2097152000.0; // 2000 MB
  
  private $_authenticator = NULL;
  private $_authorizedUser = NULL;
  private $_authorizationFailed = FALSE;
  private $_authorizedUserData = NULL;
  private $_emailSenderAddr = NULL;
  private $_defaultEmailDomain = NULL;
  private $_usernameRegexp = NULL;
  private $_validEmailRegexp = NULL;
  private $_reqRecipient = NULL;
  private $_localIPSubnets = NULL;
  private $_humanDownloads = TRUE;
  private $_showRecipsOnPickup = TRUE;
  private $_bccSender = FALSE;

  private $_clamdscan = '/usr/bin/clamdscan --quiet';
  private $_maxnotelength = 1000;
  private $_maxsubjectlength = 100;
  private $_loginFailMax = 0;
  private $_loginFailTime = 0;
  
  // private $_newDatabase = FALSE;
  public  $database = NULL; // JKF Handle to database, whatever type

  public  $MYZENDTO = NULL;

  /*!
    @function __construct
    
    Class constructor.  Takes a hash array of preference fields as its
    only parameter and initializes the instance using those values.
    
    Also gets our backing-database open and creates the appropriate
    authenticator according to the preferences.

    If the optional parameter $dbOnly is true, then just the database
    is setup and nothing more than that. Useful for ancillary scripts.
  */
  public function __construct(
    $prefs, $MyZendTo=FALSE, $dbOnly=FALSE
  )
  {
    global $NSSDROPBOX_URL;
    global $smarty;

    if ( $prefs ) {
      if ( ! $this->checkPrefs($prefs) ) {
        NSSError("The preferences are not configured properly!","Invalid Configuration");
        exit(1);
      }
    
      //  Get the database open:
      $db = new Sql($prefs, $this);
      if ( ! $db ) {
	NSSError("Could create ZendTo database handle");
        return;
      }
      $this->database = $db;

      // ZendTo or MyZendTo ???
      // This is now redundant, but harmless. Use MYZENDTO instead.
      $this->MYZENDTO = $MyZendTo;

      //  Instance copies of the preference data:
      $this->_dropboxDirectory      = $prefs['dropboxDirectory'];
      $this->_recaptchaPublicKey    = $prefs['recaptchaPublicKey'];
      $this->_recaptchaPrivateKey   = $prefs['recaptchaPrivateKey'];
      $this->_emailDomainRegexp     = $prefs['emailDomainRegexp'];
      $this->_dropboxLog            = $prefs['logFilePath'];
      $this->_cookieName            = $prefs['cookieName'];
      $this->_defaultEmailDomain    = $prefs['defaultEmailDomain'];
      $this->_usernameRegexp        = $prefs['usernameRegexp'];
      $this->_useRealProgressBar    = $prefs['useRealProgressBar'];
      $this->_reqRecipient          = $prefs['requestTo'];
      $this->_localIPSubnets        = $prefs['localIPSubnets'];
      $this->_humanDownloads        = $prefs['humanDownloads'];
      $this->_libraryDirectory      = $prefs['libraryDirectory'];
      $this->_usingLibrary          = $prefs['usingLibrary'];

      // Bail out right now if we just want a database connection.
      // Must do it here as we must have the dropboxLog setup right.
      if ($dbOnly) {
        return 0;
      }

      if ( ! ($this->_emailSenderAddr = $smarty->getConfigVariable('EmailSenderAddress')) ) {
        $execAsUser = posix_getpwuid(posix_geteuid());
        $this->_emailSenderAddr = sprintf("%s <%s@%s>",
                                      $smarty->getConfigVariable('ServiceTitle'),
                                      $execAsUser['name'],
                                      $_SERVER['SERVER_NAME']
                                     );
      }
      
      if ( $intValue = intval( $prefs['numberOfDaysToRetain'] ) ) {
        $this->_retainDays          = $intValue;
      }
      if ( $prefs['cookieSecret'] ) {
        $this->_secretForCookies    = $prefs['cookieSecret'];
      }
      if ( $intValue = intval($prefs['cookieTTL']) ) {
        $this->_cookieTTL           = $intValue;
      }
      if ( $intValue = intval($prefs['requestTTL']) ) {
        $this->_requestTTL          = $intValue;
      }
      if ( $intValue = intval($prefs['maxBytesForFile']) ) {
        $this->_maxBytesForFile     = $intValue;
      }
      if ( $intValue = intval($prefs['maxBytesForDropoff']) ) {
        $this->_maxBytesForDropoff  = $intValue;
      }
      if ( $prefs['validEmailRegexp'] ) {
        $this->_validEmailRegexp = $prefs['validEmailRegexp'];
      }
      if ( $prefs['showRecipsOnPickup'] === FALSE ) {
        $this->_showRecipsOnPickup  = FALSE;
      }
      if ( $prefs['bccSender'] ) {
        $this->_bccSender           = TRUE;
      }

      if ( $prefs['clamdscan'] ) {
        $this->_clamdscan           = $prefs['clamdscan'];
      }
      if ( $prefs['maxNoteLength'] ) {
        $this->_maxnotelength       = $prefs['maxNoteLength'];
      }
      if ( $prefs['maxSubjectLength'] ) {
        $this->_maxsubjectlength    = $prefs['maxSubjectLength'];
      }
      if ( $prefs['loginFailMax'] ) {
        $this->_loginFailMax        = $prefs['loginFailMax'];
      }
      if ( $prefs['loginFailTime'] ) {
        $this->_loginFailTime       = $prefs['loginFailTime'];
      }
      
      //  Create an authenticator based on our prefs:
      $this->_authenticator         = NSSAuthenticator($prefs, $this->database);
      //  Create an authenticator based on our prefs:
      
      if ( ! $this->_authenticator ) {
        NSSError("The ZendTo preferences.php has no authentication method selected.","Authentication Error");
        exit(1);
      }
      
      //  First try an authentication, since it _could_ override a cookie
      //  that was already set.  If that doesn't work, then try the cookie:
      if ( $this->userFromAuthentication() ) {
        
        $this->writeToLog("authenticated as '".$this->_authorizedUser."'");
        
        //  Set the cookie now:
        setcookie(
            $this->_cookieName,
            $this->cookieForSession(),
            time() + $this->_cookieTTL,
            "/",
            "",
            FALSE, // TRUE,
            TRUE
          );
      } else {
        if ( $this->userFromCookie() ) {
          //  Update the cookie's time-to-live:
          setcookie(
              $this->_cookieName,
              $this->cookieForSession(),
              time() + $this->_cookieTTL,
              "/",
              "",
              FALSE, // TRUE,
              TRUE
            );
        }
      }
    } else {
      NSSError("The preferences are not configured properly (they're empty)!","Invalid Configuration");
      exit(1);
    }
  }
  
  /*!
    @function description
    
    Debugging too, for the most part.  Give a description of the
    instance.
  */
  public function description()
  {
    return sprintf("NSSDropbox {
  directory:          %s
  log:                %s
  retainDays:         %d
  recaptchaPublicKey: %s
  recaptchaPrivateKey:%s
  emailDomainRegexp:  %s
  secretForCookies:   %s
  authorizedUser:     %s
  authenticator:      %s
}",
                $this->_dropboxDirectory,
                $this->_dropboxLog,
                $this->_retainDays,
		$this->_recaptchaPublicKey,
		$this->_recaptchaPrivateKey,
                $this->_emailDomainRegexp,
                $this->_secretForCookies,
                $this->_authorizedUser,
                ( $this->_authenticator ? $this->_authenticator->description() : "<no authenticator>" )
          );
  
  }

  /*!
    @function logout
    
    Logout the current user.  This amounts to nulling our cookie and giving it a zero
    second time-to-live, which should force the browser to drop the cookie.
  */
  public function logout()
  {
    $this->writeToLog("logged-out user '".$this->_authorizedUser."'");
    setcookie(
        $this->_cookieName,
        "",
        0,
        "/",
        "",
        TRUE,
        TRUE
      );
    $this->_authorizedUser = NULL;
    $this->_authorizedUserData = NULL;
  }

/*!
  Construct the array of filenames mapped to descriptions.
*/
public function getLibraryDescs()
{
  // Read the list of descriptions we know about
  $name2desc = $this->database->DBGetLibraryDescs();

  // Read the list of filenames from the default library directory.
  // This will be overwritten with the user-specific set if they have
  // a library directory of their own
  $files = array();

  $here = $this->_libraryDirectory;
  $where = ''; // Relative subdirectory to put in "where" element
  if (is_dir($here.'/'.$this->_authorizedUser)) {
    $here = $here . '/' . $this->_authorizedUser;
    $where = $this->_authorizedUser . '/';
  }
  $dir = opendir($here);
  // No library directory? No worries, just tell them it's empty
  if (!$dir) {
    return '[]';
  }
  while (($file = readdir($dir)) !== false) {
    // Ignore filenames starting with '.'
    if (! preg_match('/^\./', $file) && ! is_dir($here.'/'.$file)) {
      $files[] = $file;
    }
  }
  closedir($dir);

  usort($files, strcasecmp);

  //$results = array();
  //foreach ($files as $file) {
  //  if ($name2desc[$file]) {
  //    // Slot in the file's description, if present
  //    $results[$file] = $name2desc[$file];
  //  } else {
  //    $results[$file] = '';
  //  }
  //}
  //return $results;

  $results = array();
  $a = 0;
  foreach ($files as $file) {
    $results[$a] = array();
    $results[$a]['filename'] = $file;
    $results[$a]['where'] = $where . $file; # Relative path
    if ($name2desc[$file]) {
      // Slot in the file's description, if present
      $results[$a]['description'] = $name2desc[$file];
    } else {
      $results[$a]['description'] = '';
    }
    $a++;
  }
  // This returns the string "[]" if there are no files.
  return json_encode($results); // Seb wants it in JSON
}

  /*!
    @function libraryDirectory
    
    Accessor pair for getting/setting the directory where library files are
    stored.  Always use a canonical path -- and, of course, be sure your
    web server is allowed to read from it!!
  */
  public function libraryDirectory() { return $this->_libraryDirectory; }
  public function setLibraryDirectory(
    $libraryDirectory
  )
  {
    if ( $libraryDirectory && $libraryDirectory != $this->_libraryDirectory && is_dir($libraryDirectory) ) {
      $this->_libraryDirectory = $libraryDirectory;
    }
  }
  
  /*!
    @function dropboxDirectory
    
    Accessor pair for getting/setting the directory where dropoffs are
    stored.  Always use a canonical path -- and, of course, be sure your
    web server is allowed to write to it!!
  */
  public function dropboxDirectory() { return $this->_dropboxDirectory; }
  public function setDropBoxDirectory(
    $dropboxDirectory
  )
  {
    if ( $dropboxDirectory && $dropboxDirectory != $this->_dropboxDirectory && is_dir($dropboxDirectory) ) {
      $this->_dropboxDirectory = $dropboxDirectory;
    }
  }
  
  /*!
    @function dropboxLog
    
    Accessor pair for getting/setting the path to the log file for this
    dropbox.  Make sure your web server has access privs on the file
    (or the enclosing directory, in which case the file will get created
    automatically the first time we log to it).
  */
  public function dropboxLog() { return $this->_dropboxLog; }
  public function setDropBoxLog(
    $dropboxLog
  )
  {
    if ( $dropboxLog && $dropboxLog != $this->_dropboxLog ) {
      $this->_dropboxLog = $dropboxLog;
    }
  }
  
  /*!
    @function retainDays
    
    Accessor pair for getting/setting the number of days that a dropoff
    is allowed to reside in the dropbox.  The "cleanup.php" admin script
    actually removes them, we don't do it from the web interface.
  */
  public function retainDays() { return $this->_retainDays; }
  public function setRetainDays(
    $retainDays
  )
  {
    if ( intval($retainDays) > 0 && intval($retainDays) != $this->_retainDays ) {
      $this->_retainDays = intval($retainDays);
    }
  }

  /*!
    @function maxBytesForFile
    
    Accessor pair for getting/setting the maximum size (in bytes) of a single
    file that is part of a dropoff.  Note that there is a PHP system parameter
    that you must be sure is set high-enough to accomodate what you select
    herein!
  */
  public function maxBytesForFile() { return $this->_maxBytesForFile; }
  public function setMaxBytesForFile(
    $maxBytesForFile
  )
  {
    if ( ($intValue = intval($maxBytesForFile)) > 0 ) {
      $this->_maxBytesForFile = $intValue;
    }
  }

  /*!
    @function maxBytesForDropoff
    
    Accessor pair for getting/setting the maximum size (in bytes) of a dropoff
    (all files summed).  Note that there is a PHP system parameter that you must
    be sure is set high-enough to accomodate what you select herein!
  */
  public function maxBytesForDropoff() { return $this->_maxBytesForDropoff; }
  public function setMaxBytesForDropoff(
    $maxBytesForDropoff
  )
  {
    if ( ($intValue = intval($maxBytesForDropoff)) > 0 ) {
      $this->_maxBytesForDropoff = $intValue;
    }
  }

  /*!
    @function validEmailRegexp
    
    Accessor pair for getting/setting the regexp that defines a valid
    sender email address.
  */
  public function validEmailRegexp() { return $this->_validEmailRegexp; }
  public function setvalidEmailRegexp(
    $validEmailRegexp
  )
  {
    if ( $validEmailRegexp && $validEmailRegexp != $this->_validEmailRegexp ) {
      $this->_validEmailRegexp = $validEmailRegexp;
    }
  }

  // JKF Start
  //
  /*!
    @function bccSender
    
    Accessor pair for getting/setting the bccSender flag.
  */
  public function bccSender() { return $this->_bccSender; }
  public function setbccSender(
    $bccSender
  )
  {
    $this->_bccSender = $bccSender;
  }
  /*!
    @function clamdscan
    
    Accessor pair for getting/setting the clamdscan command-line.
  */
  public function clamdscan() { return $this->_clamdscan; }
  public function setclamdscan(
    $clamdscan
  )
  {
    $this->_clamdscan = $clamdscan;
  }
  /*!
    @function maxnotelength
    
    Accessor pair for getting/setting the max length of the note to recips.
  */
  public function maxnotelength() { return $this->_maxnotelength; }
  public function setmaxnotelength(
    $maxnotelength
  )
  {
    $this->_maxnotelength = $maxnotelength;
  }
  /*!
    @function usingLibrary
    
    Accessor pair for getting/setting if we are using a library of files or not.
  */
  public function usingLibrary() { return $this->_usingLibrary; }
  public function setusingLibrary(
    $usingLibrary
  )
  {
    $this->_usingLibrary = $usingLibrary;
  }
  /*!
    @function maxsubjectlength
    
    Accessor pair for getting/setting the max length of the note to recips.
  */
  public function maxsubjectlength() { return $this->_maxsubjectlength; }
  public function setmaxsubjectlength(
    $maxsubjectlength
  )
  {
    $this->_maxsubjectlength = $maxsubjectlength;
  }
  /*!
    @function cookieName
    
    Accessor pair for getting/setting the max length of the note to recips.
  */
  public function cookieName() { return $this->_cookieName; }
  public function setcookieName(
    $cookieName
  )
  {
    $this->_cookieName = $cookieName;
  }
  //
  // JKF End

  /*!
    @function recaptchaPublicKey
    
    Accessor pair for getting/setting the recaptchaPublicKey.
  */
  public function recaptchaPublicKey() { return $this->_recaptchaPublicKey; }
  public function setrecaptchaPublicKey(
    $recaptchaPublicKey
  )
  {
    if ( $recaptchaPublicKey && $recaptchaPublicKey != $this->_recaptchaPublicKey ) {
      $this->_recaptchaPublicKey = $recaptchaPublicKey;
    }
  }

  /*!
  /*!
    @function recaptchaPrivateKey
    
    Accessor pair for getting/setting the recaptchaPrivateKey.
  */
  public function recaptchaPrivateKey() { return $this->_recaptchaPrivateKey; }
  public function setrecaptchaPrivateKey(
    $recaptchaPrivateKey
  )
  {
    if ( $recaptchaPrivateKey && $recaptchaPrivateKey != $this->_recaptchaPrivateKey ) {
      $this->_recaptchaPrivateKey = $recaptchaPrivateKey;
    }
  }

  /*!
    @function emailDomainRegexp
    
    Accessor pair for getting/setting the description 6 of users
    that are properly authenticated and thus "inside" users.
  */
  public function emailDomainRegexp() { return $this->_emailDomainRegexp; }
  public function setemailDomainRegexp(
    $emailDomainRegexp
  )
  {
    if ( $emailDomainRegexp && $emailDomainRegexp != $this->_emailDomainRegexp )
    {
      $this->_emailDomainRegexp = $emailDomainRegexp;
    }
  }

  /*!
    @function defaultEmailDomain
    
    Accessor pair for getting/setting the default email domain
    that are properly authenticated and thus "inside" users.
  */
  public function defaultEmailDomain() { return $this->_defaultEmailDomain; }
  public function setdefaultEmailDomain(
    $defaultEmailDomain
  )
  {
    if ( $defaultEmailDomain && $defaultEmailDomain != $this->_defaultEmailDomain ) {
      $this->_defaultEmailDomain = $defaultEmailDomain;
    }
  }

  /*!
    @function defaultEmailDomain
    
    Accessor pair for getting/setting the default email domain
    that are properly authenticated and thus "inside" users.
  */
  public function usernameRegexp() { return $this->_usernameRegexp; }
  public function setusernameRegexp(
    $usernameRegexp
  )
  {
    if ( $usernameRegexp && $usernameRegexp != $this->_usernameRegexp ) {
      $this->_usernameRegexp = $usernameRegexp;
    }
  }

  /*!
    @function useRealProgressBar
    
    Accessor pair for getting/setting the flag for real progress bars.
  */
  public function useRealProgressBar() { return $this->_useRealProgressBar; }
  public function setuseRealProgressBar(
    $useRealProgressBar
  )
  {
    $this->_useRealProgressBar = $useRealProgressBar;
  }

  /*!
    @function localIPSubnets
    
    Accessor pair for getting/setting the list of "on-site" IP subnets.
  */
  public function localIPSubnets() { return $this->_localIPSubnets; }
  public function setlocalIPSubnets(
    $localIPSubnets
  )
  {
    $this->_localIPSubnets = $localIPSubnets;
  }

  /*!
    @function humanDownloads
    
    Accessor pair for getting/setting the flag to protect downloads from robots.
  */
  public function humanDownloads() { return $this->_humanDownloads; }
  public function sethumanDownloads(
    $humanDownloads
  )
  {
    $this->_humanDownloads = $humanDownloads;
  }

  /*!
    @function reqRecipient
    
    Accessor pair for getting/setting the flag for real progress bars.
  */
  public function reqRecipient() { return $this->_reqRecipient; }
  public function setreqRecipient(
    $reqRecipient
  )
  {
    $this->_reqRecipient = $reqRecipient;
  }

  /*!
    @function contactHelp
    
    Accessor pair for getting/setting the description 6 of users
    that are properly authenticated and thus "inside" users.
  */
  public function contactHelp() { return $this->_contactHelp; }
  public function setcontactHelp(
    $contactHelp
  )
  {
    if ( $contactHelp && $contactHelp != $this->_contactHelp ) {
      $this->_contactHelp = $contactHelp;
    }
  }

  /*!
    @function secretForCookies
    
    Accessor pair for getting/setting the secret string that we include in the
    MD5 sum that gets sent off as our cookie values.
  */
  public function secretForCookies() { return $this->_secretForCookies; }
  public function setSecretForCookies(
    $secretForCookies
  )
  {
    if ( $secretForCookies && $secretForCookies != $this->_secretForCookies ) {
      $this->_secretForCookies = $secretForCookies;
    }
  }

  /*!
    @function loginFailMax
    
    Returns the value of _loginFailMax.
  */
  public function loginFailMax() { return $this->_loginFailMax; }
  
  /*!
    @function loginFailTime
    
    Returns the value of _loginFailTime.
  */
  public function loginFailTime() { return $this->_loginFailTime; }
  
  /*!
    @function isNewDatabase
    
    Returns TRUE if the backing-database was newly-created by this instance.
  */
  public function isNewDatabase() { return $this->_newDatabase; }
  
  /*!
    @function database
    
    Returns a reference to the database object (class is SQLiteDatabase)
    backing this dropbox.
  */
  public function &database() { return $this->database; }
  
  /*!
    @function authorizedUser
    
    If the instance was created and was able to associate with a valid user
    (either via cookie or explicit authentication) the username in question
    is returned.
  */
  public function authorizedUser() { return $this->_authorizedUser; }

  /*!
    @function authorizedUserData
    
    If the instance was created and was able to associate with a valid user
    (either via cookie or explicit authentication) then this function returns
    either the entire hash of user information (if $field is NULL) or a
    particular value from the hash of user information.  For example, you
    could grab the user's email address using:
    
      $userEmail = $aDropbox->authorizedUserData('mail');
      
    If the field you request does not exist, NULL is returned.  Note that
    as the origin of this data is probably an LDAP lookup, there _may_ be
    arrays involved if a given field has multiple values.
  */
  public function authorizedUserData(
    $field = NULL
  )
  {
    if ( $field ) {
      return $this->_authorizedUserData[$field];
    }
    return $this->_authorizedUserData;
  }
  
  public function showRecipsOnPickup() { return $this->_showRecipsOnPickup; }
  public function setShowRecipsOnPickup(
    $showIt
  )
  {
    $this->_showRecipsOnPickup = $showIt;
  }

  /*!
    @function directoryForDropoff
    
    If $claimID enters with a value already assigned, then this function attempts
    to find the on-disk directory which contains that dropoff's files; the directory
    is returned in the $claimDir variable-reference.
    
    If $claimID is NULL, then we're being requested to setup a new dropoff.  So we
    pick a new claim ID, make sure it doesn't exist, and then create the directory.
    The new claim ID goes back in $claimID and the directory goes back to the caller
    in $claimDir.
    
    Returns TRUE on success, FALSE on failure.
  */
  public function directoryForDropoff(
    &$claimID = NULL,
    &$claimDir = NULL
  )
  {
    if ( $claimID ) {
      if ( is_dir($this->_dropboxDirectory."/$claimID") ) {
        $claimDir = $this->_dropboxDirectory."/$claimID";
        return TRUE;
      }
    } else {
      while ( 1 ) {
        $claimID = NSSGenerateCode();
        //  Is it already in the database?
	$this->database->DBListClaims($claimID, $extant);
        if ( !$extant || (count($extant) == 0) ) {
          //  Make sure there's no directory hanging around:
          if ( ! file_exists($this->_dropboxDirectory."/$claimID") ) {
            if ( mkdir($this->_dropboxDirectory."/$claimID",0700) ) {
              $claimDir = $this->_dropboxDirectory."/$claimID";
              return TRUE;
            }
            $this->writeToLog("unable to create ".$this->_dropboxDirectory."/$claimID");
            break;
          }
        }
      }
    }
    return FALSE;
  }
  
  /*!
    @function authenticator
    
    Returns the authenticator object (subclass of NSSAuthenticator) that was created
    when we were initialized.
  */
  public function authenticator() { return $this->_authenticator; }
  
  /*!
    @function deliverEmail
    
    Send the $content of an email message to (one or more) address(es) in
    $toAddr.
  */
  public function deliverEmail(
    $toAddr,
    $fromAddr,
    $subject,
    $content,
    $headers = ""
  )
  {
    // If it contains any characters outside 0x00-0x7f, then encode it
    if (preg_match('/[^\x00-\x7f]/', $subject)) {
      $subject = "=?UTF-8?B?".base64_encode(html_entity_decode($subject))."?=";
    }
    if (preg_match('/[^\x00-\x7f]/', $fromAddr)) {
      $fromAddr = "=?UTF-8?B?".base64_encode(html_entity_decode($fromAddr))."?=";
    }
    if (preg_match('/[^\x00-\x7f]/', $this->_emailSenderAddr)) {
      $sender = "=?UTF-8?B?".base64_encode(html_entity_decode($this->_emailSenderAddr))."?=";
    } else {
      $sender = $this->_emailSenderAddr;
    }

    // Add the From: and Reply-To: headers if they have been supplied.
    if ($fromAddr!="") {
      $headers = sprintf("From: %s", $sender) . PHP_EOL .
                 sprintf("Reply-to: %s", $fromAddr) . PHP_EOL .
                 $headers;
    }

    // Add the MIME headrs for 8-bit UTF-8 encoding
    $headers .= "MIME-Version: 1.0".PHP_EOL;
    $headers .= "Content-Type: text/plain; charset=UTF-8; format=flowed".PHP_EOL;
    $headers .= "Content-Transfer-Encoding: 8bit".PHP_EOL;

    return mail(
              $toAddr,
              $subject,
              $content,
              $headers // JKF Commented out for now due to security concerns ,
              // JKF Commented out for now due to security concerns
              // '-f "'.$fromAddr.'"'
            );
  }

  /*!
    @function writeToLog
    
    Write the $logText to the log file.  Each line is formatted to have a date
    and time, as well as the name of this dropbox.
  */
  public function writeToLog(
    $logText
  )
  {
    global $smarty;
    $logText = sprintf("%s [%s]: %s\n",strftime("%Y-%m-%d %T"),
                       $smarty->getConfigVariable('ServiceTitle'),
                       $logText);
    if (!file_put_contents($this->_dropboxLog,$logText,FILE_APPEND | LOCK_EX)) {
      NSSError(sprintf("Could not write to log file %s, ensure that web server user can write to the log file as set in preferences.php",$this->_dropboxLog));
    }
  }
  
  /*!
    @function SetupPage
    
    End the <HEAD> section; write-out the standard stuff for the <BODY> --
    the page header with title, etc.  Upon exit, the caller should begin
    writing HTML content for the page.
    
    We also get a chance here to spit-out an error if the authentication
    of a user failed.
    
    The single argument gives the text field that we should throw focus
    to when the page loads.  You should pass the text field as
    "[form name].[field name]", which the function turns into
    "document.[form name].[field name].focus()".
  */
  public function SetupPage(
    $focusTarget = NULL
  )
  {  
    global $NSSDROPBOX_URL;
    global $smarty;

    $smarty->assign('zendToURL', $NSSDROPBOX_URL);

    $smarty->assign('focusTarget', $focusTarget);
    $smarty->assign('padlockImage', ($this->_authorizedUser?"images/locked.png":"images/unlocked.png"));
    $smarty->assign('padlockImageAlt', ($this->_authorizedUser?"locked":"unlocked"));
    $smarty->assign('isAuthorizedUser', ($this->_authorizedUser?TRUE:FALSE));
    $smarty->assign('validEmailRegexp', $this->validEmailRegexp());

    $smarty->assign('isIndexPage',
             preg_match('/^index\.php.*/',basename($_SERVER['PHP_SELF'])));

    $smarty->assign('ztVersion', ZTVERSION);
    $smarty->assign('whoAmI', $this->authorizedUserData("displayName"));
    $smarty->assign('isAdminUser', $this->_authorizedUserData['grantAdminPriv']?TRUE:FALSE);
    $smarty->assign('isStatsUser', $this->_authorizedUserData['grantStatsPriv']?TRUE:FALSE);
    // -1 as it only does max-1 files, the maxth is the other form data
    $maxuploads = ini_get('max_file_uploads');
    if ($maxuploads>0) {
      $smarty->assign('uploadFilesMax', $maxuploads-1);
    } else {
      $smarty->assign('uploadFilesMax', 999);
    }

    if ( $this->_authorizationFailed ) {
      NSSError($smarty->getConfigVariable('ErrorBadLogin'),"Authentication Error");
    }
  }
  
  //
  // JKF
  //
  // Setup the new database tables I need.
  // Must be able to add this on the fly if a SELECT fails.
  public function setupDatabaseReqTable()
  {
    if ( $this->database ) {
      if ( ! $this->database->DBCreateReq() ) {
        $this->writeToLog("Failed to add reqtable to database");
        return FALSE;
      }
      $this->writeToLog("Added reqtable to database");
      return TRUE;
    }
    return FALSE;
  }

  // Setup the new database table I need.
  // Must be able to add this on the fly if a SELECT fails.
  public function setupDatabaseAuthTable()
  {
    if ( $this->database ) {
      if ( ! $this->database->DBCreateAuth() ) {
        $this->writeToLog("Failed to add authtable to database");
        return FALSE;
      }
      $this->writeToLog("Added authtable to database");
      return TRUE;
    }
    return FALSE;
  }

  // Setup the new database table I need.
  // Must be able to add this on the fly if a SELECT fails.
  public function setupDatabaseUserTable()
  {
    if ( $this->database ) {
      if ( ! $this->database->DBCreateUser() ) {
        $this->writeToLog("Failed to add usertable to database");
        return FALSE;
      }
      $this->writeToLog("Added usertable to database");
      return TRUE;
    }
    return FALSE;
  }

  // Setup the new database table I need.
  // Must be able to add this on the fly if a SELECT fails.
  public function setupDatabaseRegexpsTable()
  {
    if ( $this->database ) {
      if ( ! $this->database->DBCreateRegexps() ) {
        $this->writeToLog("Failed to add regexps table to database");
        return FALSE;
      }
      $this->writeToLog("Added regexps table to database");
      return TRUE;
    }
    return FALSE;
  }

  // Setup the new database table I need.
  // Must be able to add this on the fly if a SELECT fails.
  public function setupDatabaseLoginlogTable()
  {
    if ( $this->database ) {
      if ( ! $this->database->DBCreateLoginlog() ) {
        $this->writeToLog("Failed to add loginlog table to database");
        return FALSE;
      }
      $this->writeToLog("Added loginlog table to database");
      return TRUE;
    }
    return FALSE;
  }

  /*!
    @function isLocalUser

    Returns true if the user is coming from an IP address defined as being
    'local' in preferences.php 'localIPSubnets'. Returns false otherwise.
  */
  public function isLocalIP()
  {
    foreach ($this->localIPSubnets() as $subnet) {
      // Add a . on the end if there isn't one, so 152.78 becomes 152.78.
      if (! preg_match('/\.$/', $subnet)) {
        $subnet .= '.';
      }
      // Turn the subnet into a regexp.
      $subnet = preg_replace('/\./', '\\.', $subnet);
      $subnet = '/^'.$subnet.'/';
      // $this->writeToLog("Comparing ".$_SERVER[REMOTE_ADDR]." with ".$subnet);
      if (preg_match($subnet, $_SERVER['REMOTE_ADDR'])) {
        return TRUE;
      }
    }
    return FALSE;
  }


  /*!
    @function cookieForSession
    
    Returns an appropriate cookie for the current session.  An initial key is
    constructed using the username, remote IP, current time, a random value,
    the user's browser agent tag, and our special cookie secret.  This key is
    hashed, and included as part of the actual cookie.  The cookie contains
    more or less all but the secret value, so that the initial key and its
    hash can later be reconstructed for authenticity's sake.
  */
  private function cookieForSession()
  {
    $now = time();
    $nonce = mt_rand();
    $digestString = sprintf("%s %s %d %d %s %s %s",
                        $this->_authorizedUser,
                        $_SERVER['REMOTE_ADDR'],
                        $now,
                        $nonce,
                        $_SERVER['HTTP_USER_AGENT'],
                        $this->_cookieName,
                        $this->_secretForCookies
                      );
    return sprintf("%s,%s,%d,%d,%s",
                        $this->_authorizedUser,
                        $_SERVER['REMOTE_ADDR'],
                        $now,
                        $nonce,
                        md5($digestString)
                      );
  }
  
  /*!
    @function userFromCookie
    
    Attempt to parse our cookie (if it exists) and establish the current user's
    username.
  */
  private function userFromCookie()
  {
    if ( isset($_COOKIE[$this->_cookieName]) && ($cookieVal = $_COOKIE[$this->_cookieName]) ) {
      if ( preg_match('/^(.+)\,([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+),([0-9]+),([0-9]+),([A-Fa-f0-9]+)$/',$cookieVal,$cookiePieces) ) {
        //  Coming from the same remote IP?
        if ( $cookiePieces[2] != $_SERVER['REMOTE_ADDR'] ) {
          return FALSE;
        }
        
        //  How old is the internal timestamp?
        if ( time() - $cookiePieces[3] > $this->_cookieTTL ) {
          return FALSE;
        }
        
        //  Verify the MD5 checksum.  This implies that everything
        //  (including the HTTP agent) is unchanged.
        $digestString = sprintf("%s %s %d %d %s %s %s",
                            $cookiePieces[1],
                            $cookiePieces[2],
                            $cookiePieces[3],
                            $cookiePieces[4],
                            $_SERVER['HTTP_USER_AGENT'],
                            $this->_cookieName,
                            $this->_secretForCookies
                          );
        if ( md5($digestString) != $cookiePieces[5] ) {
          return FALSE;
        }
        
        //  Success!  Verify the username as valid:
        if ( $this->_authenticator->validUsername($cookiePieces[1],$this->_authorizedUserData) ) {
          $this->_authorizedUser = $cookiePieces[1];
          return TRUE;
        }
      }
    }
    return FALSE;
  }
  
  /*!
    @function userFromAuthentication
    
    Presumes that a username and password have come in POST'ed form
    data.  We need to do an LDAP bind to verify the user's identity.
  */
  private function userFromAuthentication()
  {
    $result = FALSE;
    
    if ( ($usernameRegex = $this->usernameRegexp()) == NULL ) {
      $usernameRegex = '/^([a-zA-Z0-9][a-zA-Z0-9\_\.\-\@\\\]*)$/';
    }
    
    if ( $this->_authenticator && isset($_POST['uname']) && preg_match($usernameRegex,$_POST['uname']) && isset($_POST['password']) && $_POST['password'] ) {
      $password = $_POST['password']; // JKF Don't unquote the password as it might break passwords with backslashes in them. stripslashes($_POST['password']);
      $uname    = paramPrepare(strtolower($_POST['uname']));
      if ( $result = $this->_authenticator->authenticate($uname,$password,$this->_authorizedUserData) ) {
        // They have been authenticated, yay!
        if ( $this->database->DBLoginlogLength($uname,
                                               time()-$this->_loginFailTime)
             >= $this->_loginFailMax ) {
          // There have been too many failure attempts.
          $this->_authorizationFailed = TRUE;
          $this->writeToLog("authorization attempt for locked-out user $uname");
          // Add a new failure record
	  $this->database->DBAddLoginlog($uname);
          $this->_authorizedUserData = NULL;
          $this->_authorizedUser = '';
          $result = FALSE;
        } else {
          // Successful login attempt with no lockout! :-)
          $this->_authorizedUser = $uname;
          $this->_authorizationFailed = FALSE;
          $this->writeToLog("authorization succeeded for ".$uname);
          // Reset their login log
          $this->database->DBDeleteLoginlog($uname);
          $result = TRUE;
        }
      } else {
        $this->_authorizationFailed = TRUE;
        $this->writeToLog("authorization failed for ".$uname);
        // Add a new failure record
	$this->database->DBAddLoginlog($uname);
        $result = FALSE;
      }
    } else {
      // Login attempt failed, check for bad usernames and report them.
      // But only if there is a username and a password, or else this will
      // false alarm on any page that displays the login box.
      if ( isset($_POST['uname']) && $_POST['uname'] != "" && 
           ! preg_match($usernameRegex,$_POST['uname']) ) {
        $this->writeToLog("Illegal username \"".paramPrepare($_POST['uname']).
                          "\" attempted to login");
        $this->_authorizationFailed = TRUE;
        $this->_authorizedUserData = NULL;
        $this->_authorizedUser = '';
        $result = FALSE;
      }
    }
    return $result;
  }

  /*!
    @function checkRecipientDomain

    Given a complete recipient email address, check if it is valid.
    The result is ignored if the user has logged in, this is only for
    un-authenticated users.
  */
  public function checkRecipientDomain( $recipient )
  {
    $result = FALSE;

    $data = explode('@', $recipient);
    $recipDomain = $data[1];
    $re = $this->emailDomainRegexp();

    if (preg_match('/^\/.*[^\/i]$/', $re)) {
      // emailDomainRegexp() is a filename.
      // Get all the current regexps from the database, and use the
      // timestamp of one of them to compare against the modtime of the
      // text config file. If there aren't any, or the timestamp is older
      // than the modtime, then rebuild all the regexps.
      // emailDomainRegexps are hereby decreed to be type number 1.
      $relist = $this->database->DBReadRegexps(1);
      if ($relist) {
        $rebuildtime = $relist[0]['created'];
      } else {
        // There weren't any stored, so build for 1st time.
        $rebuildtime = 0;
      }

      if (filemtime($re) > $rebuildtime) {
        // File has been modified since we last read it.
        // Build and store the regexps, and read them back in.
        $this->RebuildDomainRegexps(1, $re);
        $relist = $this->database->DBReadRegexps(1);
      }
      // Check against every RE we built from the file
      foreach ($relist as $rerow) {
        $re = $rerow['re'];
        if ($re != '') {
          // If we have a match, then set result and short-cut out of here!
          if (preg_match($re, $recipDomain)) {
            $result = TRUE;
            break;
          }
        }
      }
    } else {
      // It is not a filename so must be an attempt at a Regexp.
      // Add a / on the front if not already there.
      if (!preg_match('/^\//', $re)) {
        $re = '/' . $re;
      }
      // Add a / on the end if not already there.
      if (!preg_match('/\/[a-z]?$/', $re)) {
        $re = $re . '/';
      }
      if (preg_match($re, $recipDomain) ) {
        $result = TRUE;
      }
    }

    // Ask the authenticator if it has a different idea of the truth
    return $this->_authenticator->checkRecipient($result, $recipient);
  }

  // Rebuild the list of regular expressions given in the $filename.
  // Put 10 domains in each regexp to make sure we don't make them
  // too long.
  // Need to collect 10 at a time into a little array, then call
  // another function to make an re from a list of domain names.
  private function RebuildDomainRegexps($type, $filename) {
    if (!is_readable($filename)) {
      $this->writeToLog("Domains list file $filename is not readable or does not exist");
      return;
    }

    // Read $filename into an array
    $contents = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (! $contents) {
      NSSError("Could not read local domain list file $filename");
      return;
    }

    // For each line, add the domain to the temporary list of domains.
    // Once we have 10 domains, convert it into a regexp and zap the temp list.
    $relist = array();
    $domainlist = array();
    $size = 0;
    $counter = 0;
    foreach($contents as $l) {
      // Ignore blank lines and # comment lines
      $line = trim(strtolower($l));
      if ($line == '' || preg_match('/(^#|^\/\/)/', $line)) {
        continue;
      }
      // It's probably a domain, so store it
      $domainlist[] = $line;
      $size++;
      $counter++;
      // Have we got 10 yet, which is enough for 1 RE ?
      if ($size >= 10) {
        $relist[] = $this->MakeDomainRE($domainlist);
        $domainlist = array();
        $size = 0;
      }
    }
    // Don't forget the last trailing few in the file
    if ($size > 0) {
      $relist[] = $this->MakeDomainRE($domainlist);
    }
    // Now we have all the regexps in $relist. So store them in the db.
    $this->database->DBOverwriteRegexps($type, $relist);
    $this->writeToLog("Updated list of $counter local domains from $filename");
  }

  // Given a ref to a regular expression variable and a list of domains,
  // construct an RE which will match ^([a-zA-Z0-9-]+\.)?(domain1|domain2)$
  private function MakeDomainRE($domains) {
    // Error checking on inputs
    if (! $domains) {
      return;
    } else {
      // Replace every '.' with '\.'
      $dfixed = array();
      foreach ($domains as $d) {
        $dfixed[] = strtolower(preg_replace('/\./','\.',$d));
      }
      // Turn the list of domain names into an RE that matches them all
      $re = implode('|', $dfixed);
      $re = '/^([a-zA-Z0-9\-]+\.)?('.$re.')$/';
      return $re;
    }
  }


  /*!
    @function checkPrefs
    
    Examines a preference hash to be sure that all of the required parameters
    are extant.
  */
  private function checkPrefs(
    $prefs
  )
  {
    static $requiredKeys = array(
              'dropboxDirectory',
              'recaptchaPublicKey',
              'recaptchaPrivateKey',
              'emailDomainRegexp',
              'defaultEmailDomain',
              'logFilePath',
              'cookieName',
              'authenticator'
            );
    foreach ( $requiredKeys as $key ) {
      if ( !$prefs[$key] || ($prefs[$key] == "") ) {
        NSSError("You must provide a value for the following preference key: '$key'","Undefined Preference Key");
        return FALSE;
      }
    }
    return TRUE;
  }
  
  //
  // JKF
  //

  // Construct a string containing 3 random words with a space between each.
  public function ThreeRandomWords(
  )
  {
    $avoid = array();
    $word1 = $this->OneRandomWord($avoid);
    $avoid[$word1] = 1;
    $word2 = $this->OneRandomWord($avoid);
    $avoid[$word2] = 1;
    $word3 = $this->OneRandomWord($avoid);
    //$avoid[$word3] = 1;
    //$word4 = $this->OneRandomWord($avoid);
    return "$word1 $word2 $word3";
  }

  private function OneRandomWord(
    $avoid
  )
  {
    global $ShortWordsList;

    // Find a random word, avoiding words we are given in $avoid[]
    $len = count($ShortWordsList);
    do {
      $word = $ShortWordsList[mt_rand(0, $len-1)];
    } while ($avoid[$word] == 1);

    return $word;
  }

  public function WriteReqData(
    $srcname,
    $srcemail,
    $srcorg,
    $destname,
    $destemail,
    $note,
    $subject
  )
  {
    //$randint = mt_rand();
    //$hash    = strtolower(md5($randint));
    $words = $this->ThreeRandomWords();
    $hash = preg_replace('/[^a-zA-Z0-9]/', '', $words);
    $expiry  = time() + $this->_requestTTL;
    if ( $this->database->DBWriteReqData($this, $hash, $srcname, $srcemail,
                                           $srcorg, $destname, $destemail,
                                           $note, $subject, $expiry) != '' ) {
      return $words;
    } else {
      return '';
    }
  }

  public function ReadReqData(
    $authkey,
    &$srcname,
    &$srcemail,
    &$srcorg,
    &$destname,
    &$destemail,
    &$note,
    &$subject,
    &$expiry
  )
  {
    // Only allow letters and numbers in $authkey
    $authkey = preg_replace('/[^a-zA-Z0-9]/', '', $authkey);

    $srcname = '';
    $srcemail = '';
    $srcorg = '';
    $destname = '';
    $destemail = '';
    $note   = '';
    $subject= '';
    $expiry = '';

    $recordlist = $this->database->DBReadReqData($authkey);
    if ( $recordlist && count($recordlist) ) {
      // @ob_end_clean(); //turn off output buffering to decrease cpu usage
      $srcname = htmlentities($recordlist[0]['SrcName'], ENT_QUOTES, 'UTF-8');
      $destname = htmlentities($recordlist[0]['DestName'], ENT_QUOTES, 'UTF-8');
      // Trim accidental whitespace, it's hard to detect and will cause failure
      $srcemail = trim($recordlist[0]['SrcEmail']); // This is already checked carefully
      $destemail = trim($recordlist[0]['DestEmail']); // This is already checked carefully
      $srcorg = trim($recordlist[0]['SrcOrg']);
      $note = htmlentities($recordlist[0]['Note'], ENT_QUOTES, 'UTF-8');
      $subject = htmlentities($recordlist[0]['Subject'], ENT_QUOTES, 'UTF-8');
      $expiry= $recordlist[0]['Expiry'];
      return 1;
    }
    return 0;
  }

  public function DeleteReqData(
    $authkey
  )
  {
    $authkey = preg_replace('/[^a-zA-Z0-9]/', '', $authkey);
    $this->database->DBDeleteReqData($authkey);
  }

  public function PruneReqData(
  )
  {
    $old = time() - 86400; // 1 day ago
    $this->database->DBPruneReqData($old);
  }

  // Add a record to the database for this user, right now.
  // Return the auth string to use in forms, or '' on failure.
  public function WriteAuthData(
    $name,
    $email,
    $org
  )
  {
    $randint = mt_rand();
    $hash    = strtolower(md5($randint));
    $expiry = time() + 86400;

    //  Add to database:
    return $this->database->DBWriteAuthData($this, $hash, $name, $email,
                                            $org, $expiry, $filename, $claimID);
  }

  //
  // JKF
  //
  // ReadAuthData(authkey, name, email, org, expiry)
  //
  public function ReadAuthData(
    $authkey,
    &$name,
    &$email,
    &$org,
    &$expiry
  )
  {
    // Only allow letters and numbers in $authkey
    $authkey = preg_replace('/[^a-zA-Z0-9]/', '', $authkey);

    $name = '';
    $email = '';
    $org   = '';
    $expiry = '';

    $recordlist = $this->database->DBReadAuthData($authkey);
    if ( $recordlist && count($recordlist) ) {
      // @ob_end_clean(); //turn off output buffering to decrease cpu usage
      $name = htmlentities($recordlist[0]['FullName'], ENT_QUOTES, 'UTF-8');
      // Trim accidental whitespace, it's hard to detect and will cause failure
      $email = trim($recordlist[0]['Email']); // This is already checked carefully
      $org   = htmlentities($recordlist[0]['Organization'], ENT_QUOTES, 'UTF-8');
      $expiry= $recordlist[0]['Expiry'];
      return 1;
    }
    return 0;
  }

  public function DeleteAuthData(
    $authkey
  )
  {
    $authkey = preg_replace('/[^a-zA-Z0-9]/', '', $authkey);
    $this->database->DBDeleteAuthData($authkey);
  }

  public function PruneAuthData(
  )
  {
    $old = time() - 86400; // 1 day ago
    $this->database->DBPruneAuthData($old);
  }

}

?>
