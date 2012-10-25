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

require_once(NSSDROPBOX_LIB_DIR."NSSDropbox.php");
require_once(NSSDROPBOX_LIB_DIR."NSSUtils.php");
require_once(NSSDROPBOX_LIB_DIR."Timestamp.php");

/*!
  @class NSSDropoff
  
  Wraps an item that's been dropped-off.  There are two methods of
  allocation available.  The primary is using the dropoff ID,
  for which the database will be queried and used to initialize
  the instance.  The second includes no ID, and in this instance
  the $_FILES array will be examined -- if any files were uploaded
  then the dropoff is initialized using $_FILES and $_POST data.
  
  Dropoffs have evolved a bit since the previous version of this
  service.  Each dropoff can now have multiple files associated
  with it, eliminating the need for the end-user to archive
  multiple files for dropoff.  Dropoffs are now created as a
  one-to-many relationship, where the previous version was setup to
  be a one-to-one deal only.
  
  Of course, we're also leveraging the power of SQL to maintain
  the behind-the-scenes data for each dropoff.
*/
class NSSDropoff {

  //  Instance data:
  private $_dropbox = NULL;
  
  private $_dropoffID = -1;
  private $_claimID;
  private $_claimPasscode;
  private $_claimDir;
  
  private $_authorizedUser;
  private $_emailAddr;
  
  private $_auth;
  private $_senderName;
  private $_senderOrganization;
  private $_expiry;
  private $_senderEmail;
  private $_senderIP;
  private $_confirmDelivery;
  private $_informRecipients;
  private $_note;
  private $_subject;
  private $_created;
  private $_bytes;
  private $_formattedBytes;
  
  private $_recipients;
  
  private $_showPasscodeHTML = TRUE;
  private $_cameFromEmail = FALSE;
  private $_invalidClaimID = FALSE;
  private $_invalidClaimPasscode = FALSE;
  private $_isNewDropoff = FALSE;
  private $_formInitError = NULL;
  private $_okayForDownloads = FALSE;
  
  /*!
    @function dropoffsForCurrentUser
    
    Static function that returns an array of all dropoffs (as
    NSSDropoff instances) that include the currently-authenticated
    user in their recipient list.  
  */
  public static function dropoffsForCurrentUser(
    $aDropbox
  )
  {
    $allDropoffs = NULL;
    
    if ( $targetEmail = strtolower($aDropbox->authorizedUserData('mail')) ) {
      $qResult = $aDropbox->database->DBDropoffsForMe($targetEmail);
      if ( $qResult && ($iMax = count($qResult)) ) {
        //  Allocate all of the wrappers:
        $i = 0;
        while ( $i < $iMax ) {
          $params = $qResult[$i];
          $altParams = array();
          foreach ( $params as $key => $value ) {
            $altParams[preg_replace('/^d\./','',$key)] = $value;
          }
          if ( $nextDropoff = new NSSDropoff($aDropbox, $altParams) ) {
            $allDropoffs[] = $nextDropoff;
          }
          $i++;
        }
      }
    }
    return $allDropoffs;
  }
  
  /*!
    @function dropoffsFromCurrentUser
    
    Static function that returns an array of all dropoffs (as
    NSSDropoff instances) that were created by the currently-
    authenticated user.  Matches are made based on the
    user's username OR the user's email address -- that catches
    authenticated as well as anonymouse dropoffs by the user.
  */
  public static function dropoffsFromCurrentUser(
    $aDropbox
  )
  {
    $allDropoffs = NULL;
    
    if ( $authSender = $aDropbox->authorizedUser() ) {
      $targetEmail = strtolower($aDropbox->authorizedUserData('mail'));
      
      $qResult = $aDropbox->database->DBDropoffsFromMe($authSender, $targetEmail);
      if ( $qResult && ($iMax = count($qResult)) ) {
        //  Allocate all of the wrappers:
        $i = 0;
        while ( $i < $iMax ) {
          if ( $nextDropoff = new NSSDropoff($aDropbox,$qResult[$i]) ) {
            $allDropoffs[] = $nextDropoff;
          }
          $i++;
        }
      }
    }
    return $allDropoffs;
  }

  /*!
    @function dropoffsOutsideRetentionTime
    
    Static function that returns an array of all dropoffs (as
    NSSDropoff instances) that are older than the dropbox's
    retention time.  Subsequently, they should be removed --
    see the "cleanup.php" admin script.
  */
  public static function dropoffsOutsideRetentionTime(
    $aDropbox
  )
  {
    $allDropoffs = NULL;
    
    $targetDate = timestampForTime( time() - $aDropbox->retainDays() * 24 * 60 * 60 );
    
    $qResult = $aDropbox->database->DBDropoffsTooOld($targetDate);
    if ( $qResult && ($iMax = count($qResult)) ) {
      //  Allocate all of the wrappers:
      $i = 0;
      while ( $i < $iMax ) {
        if ( $nextDropoff = new NSSDropoff($aDropbox,$qResult[$i]) ) {
          $allDropoffs[] = $nextDropoff;
        }
        $i++;
      }
    }
    return $allDropoffs;
  }

  /*!
    @function dropoffsCreatedToday
    
    Static function that returns an array of all dropoffs (as
    NSSDropoff instances) that were made in the last 24 hours.
  */
  public static function dropoffsCreatedToday(
    $aDropbox
  )
  {
    $allDropoffs = NULL;
    
    $targetDate = timestampForTime( time() - 24 * 60 * 60 );
    
    $qResult = $aDropbox->database->DBDropoffsToday($targetDate);
    if ( $qResult && ($iMax = count($qResult)) ) {
      //  Allocate all of the wrappers:
      $i = 0;
      while ( $i < $iMax ) {
        if ( $nextDropoff = new NSSDropoff($aDropbox,$qResult[$i]) ) {
          $allDropoffs[] = $nextDropoff;
        }
        $i++;
      }
    }
    return $allDropoffs;
  }

  /*!
    @function allDropoffs
    
    Static function that returns an array of every single dropoff (as
    NSSDropoff instances) that exist in the database.
  */
  public static function allDropoffs(
    $aDropbox
  )
  {
    $allDropoffs = NULL;
    
    $qResult = $aDropbox->database->DBDropoffsAll();
    if ( $qResult && ($iMax = count($qResult)) ) {
      //  Allocate all of the wrappers:
      $i = 0;
      while ( $i < $iMax ) {
        if ( $nextDropoff = new NSSDropoff($aDropbox,$qResult[$i]) ) {
          $allDropoffs[] = $nextDropoff;
        }
        $i++;
      }
    }
    return $allDropoffs;
  }

  /*!
    @function cleanupOrphans
    
    Static function that looks for orphans:  directories in the dropoff
    directory that have no matching record in the database AND records in
    the database that have no on-disk directory anymore.  Scrubs both
    types of orphans.  This function gets called from the "cleanup.php"
    script after purging "old" dropoffs.
  */
  public static function cleanupOrphans(
    $aDropbox
  )
  {
    $qResult = $aDropbox->database->DBDropoffsAll();
    $scrubCount = 0;
    if ( $qResult && ($iMax = count($qResult)) ) {
      //
      //  Build a list of claim IDs and walk the dropoff directory
      //  to remove any directories that aren't in the database:
      //
      $dropoffDir = $aDropbox->dropboxDirectory();
      if ( $dirRes = opendir($dropoffDir) ) {
        $i = 0;
        $validClaimIDs = array();
        while ( $i < $iMax ) {
          $nextClaim = $qResult[$i]['claimID'];
          
          //  If there's no directory, then we should scrub this entry
          //  from the database:
          if ( !is_dir($dropoffDir."/".$nextClaim) ) {
            if ( $aDropoff = new NSSDropoff($aDropbox,$qResult[$i]) ) {
              $aDropoff->removeDropoff(FALSE);
              echo "- Removed orphaned record:             $nextClaim\n";
            } else {
              echo "- Unable to remove orphaned record:    $nextClaim\n";
            }
            $scrubCount++;
          } else {
            $validClaimIDs[] = $nextClaim;
          }
          $i++;
        }
        while ( $nextDir = readdir($dirRes) ) {
          //  Each item is a NAME, not a PATH.  Test whether it's a directory
          //  and no longer in the database:
          if ( ( $nextDir != '.' && $nextDir != '..' ) && is_dir($dropoffDir."/".$nextDir) && !in_array($nextDir,$validClaimIDs) ) {
            if ( rmdir_r($dropoffDir."/".$nextDir) ) {
              echo "- Removed orphaned directory:          $nextDir\n";
            } else {
              echo "- Unable to remove orphaned directory: $nextDir\n";
            }
            $scrubCount++;
          }
        }
        closedir($dirRes);
      }
    }
    if ( $scrubCount ) {
      printf("%d orphan%s removed.\n\n",$scrubCount,($scrubCount == 1 ? "" : "s"));
    } else {
      echo "No orphans found.\n\n";
    }
  }

  /*!
    @function __construct
    
    Object constructor.  First of all, if we were passed a query result hash
    in $qResult, then initialize the instance using data from the SQL query.
    Otherwise, we need to look at the disposition of the incoming form data:
    
    * The only GET-type form we do comes from the email notifications we
      send to notify recipients.  So the presence of claimID (and possibly
      claimPasscode) in $_GET means we can init as though the user were
      making a pickup.
    
    * If there a POST-type form and a claimID exists in $_POST, then
      try to initialize using that claimID.
    
    * Otherwise, we need to see if the POST-type form data has an action
      of "dropoff" -- if it does, then attempt to create a ~new~ dropoff
      with $_FILES and $_POST.
    
    A _lot_ of state stuff going on in here; might be ripe for simplification
    in the future.
  */
  public function __construct(
    $aDropbox,
    $qResult = FALSE
  )
  {
    $this->_dropbox = $aDropbox;
    
    if ( ! $qResult ) {
      if ( isset($_POST['claimID']) && $_POST['claimID'] ) {
        //  Coming from a web form:
        if ( ! $this->initWithClaimID(trim($_POST['claimID'])) ) {
          $this->_invalidClaimID = TRUE;
        } else {
          $this->_showPasscodeHTML = FALSE;
        }
      } else if ( isset($_GET['claimID']) && $_GET['claimID'] ) {
        //  Coming from an email:
        $this->_cameFromEmail = TRUE;
        if ( ! $this->initWithClaimID(trim($_GET['claimID'])) ) {
          $this->_invalidClaimID = TRUE;
        }
      } else if ( $_POST['Action'] == "dropoff" ) {
        $this->_isNewDropoff = TRUE;
        $this->_showPasscodeHTML = FALSE;
        //  Try to create a new one from form data:
        $this->_formInitError = $this->initWithFormData();
      }
      
      //  If we got a dropoff ID, check the passcode now:
      if ( ! $this->_isNewDropoff && $this->_dropoffID > 0 ) {
        //  Several ways to "authorize" this:
        //
        //    1) if the target user is the currently-logged-in user
        //    2) if the sender is the currently-logged-in user
        //    3) if the incoming form data has the valid passcode
        //
        $curUser = $this->_dropbox->authorizedUser();
        $curUserEmail = $this->_dropbox->authorizedUserData("mail");
        if ( $this->validRecipientEmail($curUserEmail) || ($curUser && ($curUser == $this->_authorizedUser)) || ($curUserEmail && ($curUserEmail == $this->_senderEmail)) ) {
          $this->_showPasscodeHTML = FALSE;
          $this->_okayForDownloads = TRUE;
        } else if ( $this->_cameFromEmail ) {
          if ( trim($_GET['claimPasscode']) != $this->_claimPasscode ) {
            $this->_showPasscodeHTML = TRUE;
          } else {
            $this->_showPasscodeHTML = FALSE;
            $this->_okayForDownloads = TRUE;
          }
        } else {
          if ( !$this->_dropbox->authorizedUserData('grantAdminPriv') &&
               ( trim($_POST['claimPasscode']) != $this->_claimPasscode )
             ) {
            $this->_invalidClaimPasscode = TRUE;
            $this->_showPasscodeHTML = TRUE;
          } else {
            $this->_okayForDownloads = TRUE;
          }
        }
      }
    } else {
      $this->initWithQueryResult($qResult);
    }
  }

  /*
    These are all accessors to get the value of all of the dropoff
    parameters.  Note that there are no functions to set these
    parameters' values:  an instance is immutable once it's created!
    
    I won't document each one of them because the names are
    strategically descriptive *grin*
  */
  public function dropbox() { return $this->_dropbox; }
  public function dropoffID() { return $this->_dropoffID; }
  public function claimID() { return $this->_claimID; }
  public function claimPasscode() { return $this->_claimPasscode; }
  public function claimDir() { return $this->_claimDir; }
  public function authorizedUser() { return $this->_authorizedUser; }
  public function auth() { return $this->_auth; }
  public function senderName() { return $this->_senderName; }
  public function senderOrganization() { return $this->_senderOrganization; }
  public function senderEmail() { return $this->_senderEmail; }
  public function expiry() { return $this->_expiry; }
  public function senderIP() { return $this->_senderIP; }
  public function confirmDelivery() { return $this->_confirmDelivery; }
  public function informRecipients() { return $this->_informRecipients; }
  public function note() { return $this->_note; }
  public function subject() { return $this->_subject; }
  public function created() { return $this->_created; }
  public function recipients() { return $this->_recipients; }
  public function bytes() { return $this->_bytes; }
  public function formattedBytes() { return $this->_formattedBytes; }
  public function formInitError() { return $this->_formInitError; }
  
  /*!
    @function validRecipientEmail
    
    Returns TRUE is the incoming $recipEmail address is a member of the
    recipient list for this dropoff.  Returns FALSE otherwise.
  */
  public function validRecipientEmail(
    $recipEmail
  )
  {
    foreach ( $this->_recipients as $recipient ) {
      if ( strcasecmp($recipient[1],$recipEmail) == 0 ) {
        return TRUE;
      }
    }
    return FALSE;
  }
  
  /*!
    @function files
    
    Returns a hash array containing info for all of the files in
    the dropoff.
  */
  public function files()
  {
    if ( ($dropoffFiles = $this->_dropbox->database->DBFilesByDropoffID($this->_dropoffID)) && (($iMax = count($dropoffFiles)) > 0) ) {
      $fileInfo = array();
      
      $totalBytes = 0.0;
      $i = 0;
      
      while ( $i < $iMax ) {
        $totalBytes += floatval($dropoffFiles[$i++]['lengthInBytes']);
      }
      $dropoffFiles['totalFiles'] = $iMax;
      $dropoffFiles['totalBytes'] = $totalBytes;
      $dropoffFiles['formattedBytes'] = NSSFormattedMemSize($totalBytes);
      return $dropoffFiles;
    }
    return NULL;
  }

  /*!
    @function addFileWithContent
    
    Add another file to this dropoff's payload, using the provided content,
    filename, and MIME type.
  */
  public function addFileWithContent(
    $content,
    $filename,
    $description,
    $mimeType = 'application/octet-stream'
  )
  {
    if ( ($contentLen = strlen($content)) && strlen($filename) && $this->_dropoffID ) {
      if ( strlen($mimeType) < 1 ) {
        $mimeType = 'application/octet-stream';
      }
      if ( $this->_claimDir ) {
        $tmpname = tempnam($this->_claimDir,'aff_');
        if ( $fptr = fopen($tmpname,'w') ) {
          fwrite($fptr,$content,$contentLen);
          fclose($fptr);
          
          //  Add to database:
          if ( ! $this->_dropbox->database->DBAddFile2( $this->_dropbox, $this->_dropoffID, $tmpname, $filename,
                            $contentLen, $mimeType, $description, $claimID ) ) {
            unlink($tmpname);
            return false;
          }
          return true;
        }
      }
    }
    return false;
  }

  /*!
    @function downloadFile
    
    Given a fileID -- which is simply a rowID from the "file" table in
    the database -- attempt to download that file.  Download requires that
    NO HTTP headers have been transmitted yet, so we have to be very
    careful to call this function BEFORE the PHP has generated ANY output.
    
    We do quite a bit of logging here:
    
    * Log the pickup to the database; this gives the authorized sender
      the ability to examine who made a pick-up, from when and where.
    
    * Log the pickup to the log file -- UID for auth users, 'emailAddr'
      possibly coming in from a form, or anonymously; claim ID; file
      name.
    
    If all goes well, then the user gets a file and we return TRUE.
    Otherwise, and FALSE is returned.
  */
  public function downloadFile(
    $fileID
  )
  {
    global $smarty;

    //  First, make sure we've been properly authenticated:
    if ( $this->_okayForDownloads ) {
      //  Do we have such a file on-record?
      $fileList = $this->_dropbox->database->DBFileList($this->_dropoffID, $fileID);
      if ( $fileList && count($fileList) ) {
        @ob_end_clean(); //turn off output buffering to decrease cpu usage
        header("Content-type: ".$fileList[0]['mimeType']);
        // header(sprintf('Content-Disposition: attachment; filename="%s"',
        //     $fileList[0]['basename'])
        //   );
        // If we're talking to IE, encode the attachment filename or else
        // intl characters get garbled completely.
        // Thanks to UxBoD for this.
        if (preg_match("/MSIE/", $_SERVER["HTTP_USER_AGENT"])) {
          header('Content-Disposition: attachment; filename="' .
                 urlencode($fileList[0]['basename']) . '"');
        } else {
          header('Content-Disposition: attachment; filename="' .
                 $fileList[0]['basename'] . '"');
        }
        header('Content-Transfer-Encoding: binary');

        //  Range-based support stuff:
        header('Last-Modified: ' . substr(gmdate('r', filemtime($this->_claimDir."/".$fileList[0]['tmpname'])), 0, -5) . 'GMT');
        header('ETag: ' . $this->_dropoffID . $fileList[0]['tmpname']);
        header('Accept-Ranges: bytes');
        
        //  No caching, please:
        header('Cache-control: private');
        header('Pragma: private');
        header('Expires: 0');

        // JKF was $fullSize = $fileList[0]['lengthInBytes'];
        // Changed to read real file size not what the DB said.
        // If it was a library file, then the real file might have been
        // changed between the drop-off being made and the file being
        // downloaded. Must have the correct file size.
        $fullSize = filesize($this->_claimDir."/".$fileList[0]['tmpname']);

        //  Multi-thread and resumed downloading should be supported by this next
        //  block:
        if ( isset($_SERVER['HTTP_RANGE']) ) {
          if ( preg_match('/^[Bb][Yy][Tt][Ee][Ss]=([0-9]*)-([0-9]*)$/',$_SERVER['HTTP_RANGE'],$rangePieces) ) {
            if ( is_numeric($rangePieces[1]) && ($offset = intval($rangePieces[1])) ) {
              if ( ($offset >= 0) && ($offset < $fullSize) ) {
                //  Are we doing an honest-to-god range, or a start-to-end range:
                if ( is_numeric($rangePieces[2]) && ($endOfRange = intval($rangePieces[2])) ) {
                  if ( $endOfRange >= 0 ) {
                    if ( $endOfRange >= $fullSize ) {
                      $endOfRange = $fullSize - 1;
                    }
                    if ( $endOfRange >= $offset ) {
                      $length = $endOfRange - $offset + 1;
                    } else {
                      $offset = 0; $length = $fullSize;
                    }
                  } else {
                    $offset = 0; $length = $fullSize;
                  }
                } else {
                  //  start-to-end range:
                  $length = $fullSize - $offset;
                }
              } else {
                $offset = 0; $length = $fullSize;
              }
            } else if ( is_numeric($rangePieces[2]) && ($length = intval($rangePieces[2])) ) {
              //  The last $rangePieces[2] bytes of the file:
              $offset = $fullSize - $length;
              if ( $offset < 0 ) {
                $offset = 0; $length = $fullSize;
              }
            } else {
              $offset = 0;
              $length = $fullSize;
            }
          } else {
            $offset = 0;
            $length = $fullSize;
          }
        } else {
          $offset = 0;
          $length = $fullSize;
        }
        if ( ($offset > 0) && ($length < $fullSize) ) {
          header("HTTP/1.1 206 Partial Content");
          $this->_dropbox->writeToLog(sprintf('Partial download of %d bytes, range %d - %d / %d (%s)',
              $length,
              $offset,
              $offset + $length - 1,
              $fullSize,
              $_SERVER['HTTP_RANGE']
            )
          );
        }
        header(sprintf('Content-Range: bytes %d-%d/%d',$offset,$offset + $length - 1,$fullSize));
        header('Content-Length: '.$length);

        //  Open the file:
        ob_start();
        $fptr = fopen($this->_claimDir."/".$fileList[0]['tmpname'],'rb');
        if ($fptr) {
          fseek($fptr,$offset);
          while ( ! feof($fptr) && ! connection_aborted() ) {
            set_time_limit(0);
            print( fread($fptr,8 * 1024) );
            flush();
            ob_flush();
          }
          fclose($fptr);
        }

        //  Who made the pick-up?
        $whoWasIt = $this->_dropbox->authorizedUser();
        $whoWasItEmail = "";
        if ( ! $whoWasIt ) {
          $whoWasIt = ( $_POST['emailAddr'] ? $_POST['emailAddr'] : ( $_GET['emailAddr'] ? $_GET['emailAddr'] : $smarty->getConfigVariable('UnknownRecipient')));
          $whoWasItEmail = preg_replace('/ /', '', $whoWasIt);
        } else {
          $whoWasItUID = $whoWasIt;
          $whoWasIt = $this->_dropbox->authorizedUserData('displayName');
          $whoWasItEmail = $this->_dropbox->authorizedUserData('mail');
        }

        //  Only send emails, etc, if the transfer didn't end with an aborted
        //  connection:
        if ( connection_aborted() ) {
          $this->_dropbox->writeToLog(sprintf('%s :: %s | %s [ABORTED]',
                ( $whoWasItUID ? $whoWasItUID : $whoWasIt ),
                $this->_claimID,
                $fileList[0]['basename']
              )
            );
        } else {
          //  Have any pick-ups been made already?
          $extantPickups = $this->_dropbox->database->DBExtantPickups($this->_dropoffID);
           
          if ( $this->_confirmDelivery && (! $extantPickups || ($extantPickups[0][0] == 0)) ) {
            $this->_dropbox->writeToLog("sending confirmation email to ".$this->_senderEmail." for claim ".$this->_claimID);
            $smarty->assign('whoWasIt', $whoWasIt);
            $smarty->assign('claimID', $this->_claimID);
            $smarty->assign('remoteAddr', $_SERVER['REMOTE_ADDR']);
            $smarty->assign('hostname', gethostbyaddr($_SERVER['REMOTE_ADDR']));
            $emailSubject = $smarty->getConfigVariable('PickupEmailSubject');
            // The subject line can have a %s in it, so used it as a template.
            $emailSubject = sprintf($emailSubject, $whoWasIt);

            if ((preg_match('/^[yYtT1]/', MYZENDTO) && $this->_senderEmail != $whoWasItEmail) || preg_match('/^[^yYtT1]/', MYZENDTO)) {
              if ( ! $this->_dropbox->deliverEmail(
                      $this->_senderEmail,
                      $whoWasItEmail,
                      $emailSubject,
                      $smarty->fetch('pickup_email.tpl')
                   )
                 ) {
                $this->_dropbox->writeToLog("error while sending confirmation email for claim ".$this->_claimID);
              }
            }
          } else {
            $this->_dropbox->writeToLog("no need to send confirmation email for claim ".$this->_claimID);
          }
          $this->_dropbox->writeToLog(sprintf("%s :: %s | %s",
                  ( $whoWasItUID ? $whoWasItUID : $whoWasIt ),
                  $this->_claimID,
                  $fileList[0]['basename'])
            );
          
          //  Add to the pickup log:
          $this->_dropbox->database->DBAddToPickupLog($this->_dropbox,
                           $this->_dropoffID,
                           $this->_dropbox->authorizedUser(),
                           ($_POST['emailAddr'] ? $_POST['emailAddr'] : ( $_GET['emailAddr'] ? $_GET['emailAddr'] : "")),
                           $_SERVER['REMOTE_ADDR'],
                           timestampForTime(time()),
                           $this->_claimID);
        }
        return TRUE;
      }
    }
    return FALSE;
  }

  /*!
    @function resendDropoff
    
    Re-send the dropoff to its recipients.
  */
  public function resendDropoff(
  )
  {
        global $smarty;
        $senderName = $this->_senderName;
        $senderOrganization = $this->_senderOrganization;
        $senderEmail = $this->_senderEmail;
        $senderIP = 'unknown IP address';
        if ( $this->_senderIP ) {
          //  Try to get a hostname for the IP, too:
          $senderHost = gethostbyaddr($this->_senderIP);
        }
        $note = htmlentities($this->_note, ENT_NOQUOTES, 'UTF-8');
        // Work out the real email subject line.
        if ($realFileCount == 1) {
          $emailSubject = sprintf($smarty->getConfigVariable(
                                  'DropoffEmailSubject1'),                                    $senderName);
        } else {
          $emailSubject = sprintf($smarty->getConfigVariable(                                    'DropoffEmailSubject2'),
                                  $senderName);
        }
        $claimID = $this->_claimID;
        $claimPasscode = $this->_claimPasscode;
        $files = $this->files();
        $realFileCount = $files['totalFiles'];
        // Files in email count from 1, files from database count from 0.
        $tplFiles = array();
        for ($i=1; $i<=$realFileCount; $i++) {
          $tplFiles[$i] = array();
          $file = $files[$i-1];
          $tplFiles[$i]['name'] = $file['basename'];
          $tplFiles[$i]['type'] = $file['mimeType'];
          $tplFiles[$i]['size'] = NSSFormattedMemSize($file['lengthInBytes']);
          // SLASH $tplFiles[$i]['description'] = stripslashes($file['description']);
          $tplFiles[$i]['description'] = $file['description'];
        }

        //  Construct the email notification and deliver:
        $smarty->assign('senderName',  $senderName);
        $smarty->assign('senderOrg',   $senderOrganization);
        $smarty->assign('senderEmail', $senderEmail);
        $smarty->assign('senderIP',    $senderIP);
        $smarty->assign('senderHost',  $senderHost);
        $smarty->assign('note',        trim($note));
        $smarty->assign('subject',     $emailSubject);
        $smarty->assign('now',         timestampForTime(time()));
        $smarty->assign('claimID',     $claimID);
        $smarty->assign('claimPasscode', $claimPasscode);
        $smarty->assign('fileCount',   $realFileCount);
        $smarty->assign('retainDays',  $this->_dropbox->retainDays());
        $smarty->assignByRef('files',  $tplFiles);
        $emailContent = $smarty->fetch('dropoff_email.tpl');

        // Make the mail come from the sender, not ZendTo
        foreach ( $this->_recipients as $recipient ) {
          // In MyZendTo, don't send email to myself
          if (preg_match('/^[^yYtT1]/', MYZENDTO) ||
              (preg_match('/^[yYtT1]/', MYZENDTO) && $senderEmail != $recipient[1])) {
            $success = $this->_dropbox->deliverEmail(
                $recipient[1],
                $senderEmail,
                $emailSubject,
                sprintf($emailContent,urlencode($recipient[1]))
             );
            if ( ! $success ) {
              $this->_dropbox->writeToLog(sprintf("notification email not re-delivered successfully to %s for claimID $claimID",$recipient[1]));
            } else {
              $this->_dropbox->writeToLog(sprintf("notification email re-delivered successfully to %s for claimID $claimID",$recipient[1]));
            }
          }
        }

        return TRUE;
  }

  /*!
    @function removeDropoff
    
    Scrub the database and on-disk directory for this dropoff, effectively
    removing it.  We do some writing to the log file to make sure we know
    when this happens.
  */
  public function removeDropoff(
    $doLogEntries = TRUE
  )
  {
    if ( is_dir($this->_claimDir) ) {
      //  Remove the contents of the directory:
      if ( ! rmdir_r($this->_claimDir) ) {
        if ( $doLogEntries ) {
          $this->_dropbox->writeToLog("could not remove drop-off directory ".$this->_claimDir);
        }
        return FALSE;
      }
      
      //  Remove any stuff from the database:
      if ( ! $this->_dropbox->database->DBRemoveDropoff($this->_dropbox, $this->_dropoffID, $this->_claimID) ) {
        return FALSE;
      }
        
      if ( $doLogEntries ) {
        $this->_dropbox->writeToLog("drop-off with claimID ".$this->_claimID." removed by ".$this->_dropbox->authorizedUser());
      }
      return TRUE;
    }
    return FALSE;
  }

  /*!
    @function HTMLOnLoadJavascript
    
    Returns the "[form name].[field name]" string that's most appropriate for the page
    that's going to display this object.  Basically allows us to give focus to the
    claim ID or passcode field according to what data we have so far.
  */
  public function HTMLOnLoadJavascript()
  {
    if ( $this->_showPasscodeHTML ) {
      if ( !$this->_invalidClaimID && ($_GET['claimID'] && !$_GET['claimPasscode']) || ($_POST['claimID'] && !$_POST['claimPasscode']) ) {
        return "pickup.claimPasscode";
      }
      return "pickup.claimID";
    }
    return NULL;
  }

  /*!
    @function HTMLWrite
    
    Composes and writes the HTML that should be output for this
    instance.  If the instance is a fully-initialized, existing
    dropoff, then we'll wind up calling HTMLSummary().  Otherwise,
    we output one of several possible errors (wrong claim passcode,
    e.g.) and possibly show the claim ID and passcode "dialog".
  */
  public function HTMLWrite()
  {
    global $NSSDROPBOX_URL;
    global $smarty;

    $claimID = $this->_claimID;
    
    // These need to be usable everywhere
    $smarty->assign('maxBytesForFile', NSSFormattedMemSize($this->_maxBytesForFile));
    $smarty->assign('maxBytesForDropoff', NSSFormattedMemSize($this->_maxBytesForDropoff));
    $smarty->assign('retainDays', $this->_retainDays);

    if ( $this->_invalidClaimID ) {
      NSSError($smarty->getConfigVariable('ErrorBadClaim'),"Invalid Claim ID or Passcode");
      $claimID = ( $this->_cameFromEmail ? $_GET['claimID'] : $_POST['claimID'] );
    }
    if ( $this->_invalidClaimPasscode ) {
      NSSError($smarty->getConfigVariable('ErrorBadClaim'),"Invalid Claim ID or Passcode");
      $claimID = ( $this->_cameFromEmail ? $_GET['claimID'] : $_POST['claimID'] );
    }
    // Kill any nasty characters in $claimID before using it
    $claimID = preg_replace('/[^a-zA-Z0-9]/', '', $claimID);
    $smarty->assign('claimID', $claimID);
    if ( $this->_isNewDropoff ) {
      if ( $this->_formInitError ) {
        NSSError($this->_formInitError,"Upload Error");
      } else {
        $this->HTMLSummary(FALSE,TRUE);
        return 'show_dropoff.tpl';
      }
    }
    else if ( $this->_showPasscodeHTML ) {
      $smarty->assign('cameFromEmail', $this->_cameFromEmail);
      return 'claimid_box.tpl';
    } else {
      $this->HTMLSummary(TRUE);
      return 'show_dropoff.tpl';
    }
    return "";
  }

  /*!
    @function HTMLSummary
    
    Compose and write the HTML that shows all of the info for a dropoff.
    This includes:
    
    * A table of claim ID and passcode; sender info; and list of recipients
    
    * A list of the files included in the dropoff.  The icons and names in
      this list will be hyperlinked as download triggers if the $clickable
      argument is TRUE.
    
    * A table of the pickup history for this dropoff.
    
  */
  public function HTMLSummary(
    $clickable = FALSE,
    $overrideShowRecips = FALSE
  )
  {
    global $smarty;

    $curUser = $this->_dropbox->authorizedUser();
    $curUserEmail = $this->_dropbox->authorizedUserData("mail");
    $isSender = FALSE;
    $isAdmin  = FALSE;
    $overrideShowRecips = FALSE;
    if ( $curUser ) {
      if ( $curUserEmail && (strcasecmp($curUserEmail,$this->_senderEmail) == 0) ) {
        $isSender = TRUE;
      }
      if ( $this->_dropbox->authorizedUserData('grantAdminPriv') ) {
        $isAdmin = TRUE;
      }
      if ( ($curUser == $this->_authorizedUser) || $isSender ) {
        $overrideShowRecips = TRUE;
      }
    }
    if ( $this->_senderIP ) {
      //  Try to get a hostname for the IP, too:
      $remoteHostName = gethostbyaddr($this->_senderIP);
    }
    if ( count($this->_recipients) == 1 ) {
      $isSingleRecip = TRUE;
    }
    $smarty->assign('isClickable', $clickable);

    $smarty->assign('isDeleteable', ( $clickable && ( $isAdmin || $isSender || $isSingleRecip )));
    $smarty->assign('isSendable', ( $clickable && $isSender ));

    $smarty->assign('inPickupPHP', preg_match('/pickup\.php/', $_SERVER['PHP_SELF']));
    $smarty->assign('claimPasscode', $this->_claimPasscode);

    $smarty->assign('senderName',  $this->_senderName);
    $smarty->assign('senderOrg',   $this->_senderOrganization);
    $smarty->assign('senderEmail', $this->_senderEmail);
    $smarty->assign('senderHost',  $remoteHostName);
    $smarty->assign('createdDate', timeForDate($this->created()));
    $smarty->assign('expiryDate', timeForDate($this->created())+3600*24*$this->_dropbox->retainDays());
    $smarty->assign('confirmDelivery', $this->_confirmDelivery?TRUE:FALSE);
    $smarty->assign('informRecipients', $this->_informRecipients?TRUE:FALSE);

    $smarty->assign('showRecips', ( $this->_dropbox->showRecipsOnPickup() || $overrideShowRecips || ($this->_dropbox->authorizedUser() && $this->_dropbox->authorizedUserData('grantAdminPriv')) ));
    // MyZendTo: If there is only 1 recipient then that must be the sender
    if (preg_match('/^[yYtT1]/', MYZENDTO) && count($this->_recipients)<=1) {
      $smarty->assign('showRecips', FALSE);
    }
    $reciphtml = array();
    foreach($this->_recipients as $r) {
      $reciphtml[] = array(htmlentities($r[0], ENT_NOQUOTES, 'UTF-8'), htmlentities($r[1], ENT_NOQUOTES, 'UTF-8'));
    }
    $smarty->assign('recipients', $reciphtml);

    $smarty->assign('note', htmlentities($this->_note, ENT_NOQUOTES, 'UTF-8'));
    $smarty->assign('subject', htmlentities($this->_subject, ENT_NOQUOTES, 'UTF-8'));

    $dropoffFiles = $this->_dropbox->database->DBFilesForDropoff($this->_dropoffID);
    $smarty->assign('dropoffFilesCount', count($dropoffFiles));

    // Fill the outputFiles array with all the dropoffFiles, over-riding
    // one or two elements as we go so it's ready-formatted.
    $outputFiles = array();
    $i = 0;
    foreach($dropoffFiles as $file) {
      $outputFiles[$i] = $file;
      $outputFiles[$i]['basename'] = htmlentities($file['basename'], ENT_NOQUOTES, 'UTF-8');
      $outputFiles[$i]['length'] = NSSFormattedMemSize($file['lengthInBytes']);
      $outputFiles[$i]['description'] = htmlentities($file['description'],ENT_NOQUOTES, 'UTF-8');
      $i++;
    }
    $smarty->assignByRef('files', $outputFiles);
    $emailAddr = isset($_POST['emailAddr'])?$_POST['emailAddr']:(isset($_GET['emailAddr'])?$_GET['emailAddr']:NULL);
    $smarty->assign('emailAddr', $emailAddr);
    $smarty->assign('downloadURL', 'download.php?claimID=' . $this->_claimID . '&claimPasscode=' . $this->_claimPasscode . ($emailAddr?('&emailAddr='.$emailAddr):''));

    $pickups = $this->_dropbox->database->DBPickupsForDropoff($this->_dropoffID);
    $smarty->assign('pickupsCount', count($pickups));

    // Fill the outputPickups array with all the pickups, over-riding
    // one or two elements as we go so it's ready-formatted.
    $outputPickups = array();
    $i = 0;
    foreach($pickups as $pickup) {
      $outputPickups[$i] = $pickup;
      $hostname = gethostbyaddr($pickups[$i]['recipientIP']);
      if ( $hostname != $pickups[$i]['recipientIP'] ) {
        $hostname = "$hostname (".$pickups[$i]['recipientIP'].")";
      }
      $outputPickups[$i]['hostname'] = htmlentities($hostname, ENT_NOQUOTES, 'UTF-8');
      $outputPickups[$i]['pickupDate'] = timeForTimestamp($pickups[$i]['pickupTimestamp']);
      $authorizedUser = htmlentities($pickups[$i]['authorizedUser'], ENT_NOQUOTES, 'UTF-8');
      if ( ! $authorizedUser ) {
        $authorizedUser = $pickups[$i]['emailAddr'];
      }
      $outputPickups[$i]['pickedUpBy'] = $authorizedUser;
      $i++;
    }
    $smarty->assignByRef('pickups', $outputPickups);
  }

  /*!
    @function initWithClaimID
    
    Completes the initialization (begun by the __construct function)
    by looking-up a dropoff by the $claimID.
    
    Returns TRUE on success, FALSE otherwise.
  */
  private function initWithClaimID(
    $claimID
  )
  {
    if ( $this->_dropbox ) {
      $qResult = $this->_dropbox->database->DBDropoffsForClaimID($claimID);
      if ( $qResult && ($iMax = count($qResult)) ) {
        //  Set the fields:
        if ( $iMax == 1 ) {
          return $this->initWithQueryResult($qResult[0]);
        } else {
          NSSError("There appear to be multiple drop-offs with that claim identifier, please notify the administrator.","Invalid Claim ID");
        }
      }
    }
    return FALSE;
  }
  
  /*!
    @function initWithQueryResult
    
    Completes the initialization (begun by the __construct function)
    by pulling instance data from a hash of results from an SQL query.
    
    Also builds an in-memory recipient list by doing a query on the
    recipient table.  The list is a 2D array, each outer element being
    a hash containing values keyed by 'recipName' and 'recipEmail'.
    
    Returns TRUE on success, FALSE otherwise.
  */
  private function initWithQueryResult(
    $qResult
  )
  {
    $trimmed = trim($qResult['claimID']);
    if ( ! $this->_dropbox->directoryForDropoff($trimmed, $this->_claimDir) ) {
      NSSError("The directory containing this drop-off's file has gone missing, please notify the administrator.","Drop-Off Directory Not Found");
    } else {
      $this->_dropoffID           = $qResult['rowID'];
      
      $this->_claimID             = trim($qResult['claimID']);
      $this->_claimPasscode       = trim($qResult['claimPasscode']);
      
      $this->_authorizedUser      = $qResult['authorizedUser'];
      $this->_emailAddr           = $qResult['emailAddr'];
      
      $this->_senderName          = $qResult['senderName'];
      $this->_senderOrganization  = $qResult['senderOrganization'];
      $this->_senderEmail         = $qResult['senderEmail'];
      $this->_note                = $qResult['note'];
      $this->_subject             = $qResult['subject'];

      $this->_senderIP            = $qResult['senderIP'];
      $this->_confirmDelivery     = ( preg_match('/[tT1]/',$qResult['confirmDelivery']) ? TRUE : FALSE );
      $this->_informRecipients    = ( preg_match('/[tT1]/',$qResult['informRecipients']) ? TRUE : FALSE );
      $this->_created             = dateForTimestamp($qResult['created']);
      
      $this->_recipients          = $this->_dropbox->database->DBRecipientsForDropoff($qResult['rowID']);
      $this->_bytes               = $this->_dropbox->database->DBBytesOfDropoff($qResult['rowID']);
      $this->_formattedBytes      = NSSFormattedMemSize($this->_bytes);
      
      return TRUE;
    }
    return FALSE;
  }
  
  // Work out how many files they submitted in the form.
  private $maxFilesKey = 0; // This holds the biggest file index we ever look at
  private function numberOfFiles() {
    $i=0;
    $files=0;
    while ($i<200) { // Okay, we can never have more than 200 files in 1 dropoff
//NSSError("file_select_$i = ".$_POST["file_select_".$i], "Debug");
//NSSError("file_$i = ".$_FILES["file_".$i], "Debug");
      if ((array_key_exists("file_select_".$i, $_POST) &&
           $_POST["file_select_".$i] !== "-1") ||
          array_key_exists("file_".$i, $_FILES)) {
        $files++;
        $this->maxFilesKey = $i;
      }
      $i++;
    }
    return $files;
  }



  /*!
    @function initWithFormData
    
    This monster routine examines POST-type form data coming from our dropoff
    form, validates all of it, and actually creates a new dropoff.
    
    The validation is done primarily on the email addresses that are involved,
    and all of that is documented inline below.  We also have to be sure that
    the user didn't leave any crucial fields blank.
    
    We examine the incoming files to be sure that individually they are all
    below our parent dropbox's filesize limit; in the process, we sum the
    sizes so that we can confirm that the whole dropoff is below the parent's
    dropoff size limit.
    
    Barring any problems with all of that, we get a new claimID and claim
    directory for this dropoff and move the uploaded files into it.  We add
    a record to the "dropoff" table in the database.
    
    We also have to craft and email and send it to all of the recipients.  A
    template string is created with the content and then filled-in individually
    (think form letter) for each recipient (we embed the recipient's email address
    in the URL so that it _might_ be possible to identify the picker-upper even
    when the user isn't logged in).
    
    If any errors occur, this function will return an error string.  But
    if all goes according to plan, then we return NULL!
  */
  private function initWithFormData()
  {
    global $NSSDROPBOX_URL;
    global $smarty;
    
    // Start off with the data from the form posting, overwriting it with
    // stored data as necessary.
    $senderName = paramPrepare($_POST['senderName']);
    $senderEmail = paramPrepare(strtolower($_POST['senderEmail']));
    $senderOrganization = paramPrepare($_POST['senderOrganization']);
    // SLASH $note = stripslashes($_POST['note']);
    $note = $_POST['note'];
    $expiry = 0;

    // If they have a valid req key, then they don't need to be verified
    // or logged in.
    $reqSubject = '';
    $req = '';
    if ($_POST['req'] != '') {
      $dummy = '';
      $recipName = '';  // Never actually use this
      $recipEmail = ''; // Never actually use this
      $req = preg_replace('/[^a-zA-Z0-9]/', '', $_POST['req']);
      if ($this->_dropbox->ReadReqData($req,
                                       $recipName, $recipEmail,
                                       $senderOrganization,
                                       $senderName, $senderEmail,
                                       $dummy, $reqSubject, $expiry)) {
        if ($expiry < time()) {
          $this->_dropbox->DeleteReqData($req);
          return $smarty->getConfigVariable('ErrorReadAuth');
        }
        // It was a valid req key, so leave $req alone (and true).
        $reqSubject = trim($reqSubject);
        $this->_subject = $reqSubject;
      } else {
        // Invalid request code, so ignore them
        $req = FALSE;
        $reqSubject = '';
      }
    }

    // It is not a request, or not a valid request
    if ($req == '') {
      // So now they must be authorized as it's not a request
      if (! $this->_dropbox->authorizedUser()) {
        $auth = $_POST['auth'];

        // JKF Get the above from the auth database
        // JKF Fail if it doesn't exist or it's a pickup auth not a dropoff
        $authdatares = $this->_dropbox->ReadAuthData($auth,
                                             $senderName,
                                             $senderEmail,
                                             $senderOrganization,
                                             $expiry);
        if (! $authdatares) {
          return $smarty->getConfigVariable('ErrorReadAuth');
        }
        // If the email is blank (and name has no spaces) then it's a pickup.
        // In a pickup, the name is used as the sender's IP address.
        if (!preg_match('/ /', $senderName) && $senderEmail == '') {
          return $smarty->getConfigVariable('ErrorReadAuth');
        }
        if ($expiry < time()) {
          $this->_dropbox->DeleteAuthData($auth);
          return $smarty->getConfigVariable('ErrorReadAuth');
        }
      } else {
        // Logged-in user so just read their data
        $senderName = $this->_dropbox->authorizedUserData("displayName");
        $senderOrganization = paramPrepare($_POST['senderOrganization']);
        $senderEmail = trim($this->_dropbox->authorizedUserData("mail"));
      }
    }


    // Erase the note if it is just white space.
    if (preg_match('/^\s*$/', $note)) {
      $note = "";
    }

    // Check the length of the note.
    $notelength = strlen($note);
    $maxlen = $this->_dropbox->maxnotelength();
    if ($notelength>$maxlen) {
      return sprintf($smarty->getConfigVariable('ErrorNoteTooLong'),
                     $notelength, $maxlen);
    }

    $confirmDelivery = ( $_POST['confirmDelivery'] ? TRUE : FALSE );
    $informRecipients = ( $_POST['informRecipients'] ? TRUE : FALSE );
    
    $recipients = array();
    $recipIndex = 1;
    while ( array_key_exists('recipient_'.$recipIndex,$_POST) ) {
      $recipName = paramPrepare($_POST['recipName_'.$recipIndex]);
      $recipEmail = paramPrepare($_POST['recipEmail_'.$recipIndex]);
      if ( $recipName || $recipEmail ) {
        //  Take the email to purely lowercase for simplicity:
        $recipEmail = strtolower($recipEmail);
         
        //  Just a username?  We add an implicit "@domain.com" for these and validate them!
        $emailParts[1] = NULL;
        $emailParts[2] = NULL;
        if ( preg_match('/\@/',$recipEmail) ) {
          // Has an @ sign so is an email address. Must be valid!
          if ( ! preg_match($this->_dropbox->validEmailRegexp(),$recipEmail,$emailParts) ) {
            return sprintf($smarty->getConfigVariable('ErrorBadRecipient'),
                           $recipEmail);
           }
        } else {
          // No @ sign so just stick default domain in right hand side
          $emailParts[1] = $recipEmail;
          $emailParts[2] = $this->_dropbox->defaultEmailDomain();
        }
        $recipEmailDomain = $emailParts[2];
        // Don't think this line is needed any more, but harmless
        $recipEmail = $emailParts[1]."@".$emailParts[2];
    
        //  Look at the recipient's email domain; un-authenticated users can only deliver
        //  to the dropbox's domain:
        // JKF Changed checkRecipientDomain to return true if it's a local user
        if ( ! $this->_dropbox->authorizedUser() && ! $this->_dropbox->checkRecipientDomain($recipEmail) ) {
          return $smarty->getConfigVariable('ErrorWillNotSend');
        }
        $recipients[] = array(( $recipName ? $recipName : "" ),$recipEmail);
      } else if ( $recipName && !$recipEmail ) {
        return $smarty->getConfigVariable('ErrorNoEmail');
      }
      $recipIndex++;
    }
    
    //
    //  Check for an uploaded CSV/TXT file containing addresses:
    //
    if ( $_FILES['recipient_csv']['tmp_name'] ) {
      if ( $_FILES['recipient_csv']['error'] != UPLOAD_ERR_OK ) {
        $error = sprintf($smarty->getConfigVariable('ErrorWhileUploading'),$_FILES['recipient_csv']['name']);
        switch ( $_FILES['recipient_csv']['error'] ) {
          case UPLOAD_ERR_INI_SIZE:
            $error .= $smarty->getConfigVariable('ErrorRecipientsTooBigForPHP');
            break;
          case UPLOAD_ERR_FORM_SIZE:
            $error .= sprintf($smarty->getConfigVariable('ErrorRecipientsFileTooBig'), $this->_dropbox->maxBytesForFile());
            break;
          case UPLOAD_ERR_PARTIAL:
            $error .= $smarty->getConfigVariable('ErrorRecipientsPartialUpload');
            break;
          case UPLOAD_ERR_NO_FILE:
            $error .= $smarty->getConfigVariable('ErrorNoRecipientsFile');
            break;
          case UPLOAD_ERR_NO_TMP_DIR:
            $error .= $smarty->getConfigVariable('ErrorNoTemp');
            break;
          case UPLOAD_ERR_CANT_WRITE:
            $error .= $smarty->getConfigVariable('ErrorBadTemp');
            break;
        }
        return $error;
      }
      
      //  Parse the CSV/TXT file:
      if ( $csv = fopen($_FILES['recipient_csv']['tmp_name'],'r') ) {
        while ( $fields = fgetcsv($csv) ) {
          if ( $fields[0] !== NULL ) {
            //  Got one; figure out which field is an email address:
            foreach ( $fields as $recipEmail ) {
              //  Take the email to purely lowercase for simplicity:
              $recipEmail = strtolower($recipEmail);
               
              // JKF Don't allow just usernames in CSV file!
              if ( ! preg_match($this->_dropbox->validEmailRegexp(),$recipEmail,$emailParts) ) {
                continue;
              }
              $recipEmailDomain = $emailParts[2];
              $recipEmail = $emailParts[1]."@".$emailParts[2];
          
              //  Look at the recipient's email domain;
              //  un-authenticated users can only deliver to the dropbox's
              //  domain:
              if ( ! $this->_dropbox->authorizedUser() && (! $this->_dropbox->checkRecipientDomain($recipEmail)) ) {
                return $smarty->getConfigVariable('ErrorWillNotSend');
              }
              // $recipients[] = array(( $recipName ? $recipName : "" ),$recipEmail);
              $recipients[] = array("",$recipEmail);
            }
          }
        }
        fclose($csv);
      } else {
        return $smarty->getConfigVariable('ErrorBadRecipientsFile');
      }
      
      //$fileCount = count( array_keys($_FILES) ) - 1;
    //} else {
    //  //$fileCount = count( array_keys($_FILES) );
    //  $fileCount = numberOfFiles();
    }
    $fileCount = $this->numberOfFiles();
    
    // If it's in response to a request, and the recipient override
    // is set, then zap the first recipient and replace with ours.
    $reqRecipient = $this->_dropbox->reqRecipient();
    if ($req != '' && $reqRecipient != '') {
      $recipients[0][1] = $reqRecipient;
    }

    //  Confirm that all fields are present and accounted for:
    if ( $fileCount == 0 ) {
      return $smarty->getConfigVariable('ErrorNoFiles');
    }
    
    //  Now make sure each file was uploaded successfully, isn't too large,
    //  and that the total size of the upload isn't over capacity:
    $i = 1;
    $totalBytes = 0.0;
    $totalFiles = 0;
    // while ( $i <= $fileCount ) {
    while ( $i <= $this->maxFilesKey ) {
      $key = "file_".$i;
      if ( array_key_exists('file_select_'.$i, $_POST) && $_POST['file_select_'.$i] != "-1" ) {
        $totalFiles++;
      } elseif ( $_FILES[$key]['name'] ) {
        if ( $_FILES[$key]['error'] != UPLOAD_ERR_OK ) {
          $error = sprintf($smarty->getConfigVariable('ErrorWhileUploading'),$_FILES[$key]['name']);
          switch ( $_FILES[$key]['error'] ) {
            case UPLOAD_ERR_INI_SIZE:
              $error .= $smarty->getConfigVariable('ErrorTooBigForPHP');
              break;
            case UPLOAD_ERR_FORM_SIZE:
              $error .= sprintf($smarty->getConfigVariable('ErrorFileTooBig'), $this->_dropbox->maxBytesForFile());
              break;
            case UPLOAD_ERR_PARTIAL:
              $error .= $smarty->getConfigVariable('ErrorPartialUpload');
              break;
            case UPLOAD_ERR_NO_FILE:
              $error .= $smarty->getConfigVariable('ErrorNoFile');
              break;
            case UPLOAD_ERR_NO_TMP_DIR:
              $error .= $smarty->getConfigVariable('ErrorNoTemp');
              break;
            case UPLOAD_ERR_CANT_WRITE:
              $error .= $smarty->getConfigVariable('ErrorBadTemp');
              break;
          }
          return $error;
        }
        if ( ($bytes = $_FILES[$key]['size']) < 0 ) {
          //  Grrr...stupid 32-bit nonsense.  Convert to the positive
          //  value float-wise:
          $bytes = ($bytes & 0x7FFFFFFF) + 2147483648.0;
        }
        if ( $bytes > $this->_dropbox->maxBytesForFile() ) {
          return sprintf($smarty->getConfigVariable('ErrorNamedFileTooBig'),
                      $_FILES[$key]['name'],
                      NSSFormattedMemSize($this->_dropbox->maxBytesForFile())
                    );
        }
        if ( ($totalBytes += $bytes) > $this->_dropbox->maxBytesForDropoff() ) {
          return sprintf($smarty->getConfigVariable('ErrorDropoffTooBig'),
                      $_FILES[$key]['name'],
                      NSSFormattedMemSize($this->_dropbox->maxBytesForDropoff())
                    );
        }
        // MyZendTo: If they don't have enough quota left, disallow it
        if (preg_match('/^[yYtT1]/', MYZENDTO)) {
          $QuotaLeft = $this->_dropbox->database->DBRemainingQuota($this->_dropbox->authorizedUser());
          if ( $totalBytes > $QuotaLeft ) {
            return sprintf($smarty->getConfigVariable('ErrorDropoffQuota'),
                           NSSFormattedMemSize($totalBytes-$QuotaLeft)
                          );
          }
        }
        $totalFiles++;
      }
      $i++;
    }
    //if ( $totalBytes == 0 ) {
    if ( $totalFiles == 0 ) {
      return $smarty->getConfigVariable('ErrorNoFiles');
    }

    // JKF Start
    //

    // Call clamdscan on all the files, fail if they are infected
    // If the name of the scanner is set to '' or 'DISABLED' then skip this.
    $jkfclamdscan = $this->_dropbox->clamdscan();
    if ($jkfclamdscan != 'DISABLED') {
      $jkffilecount = 1;
      $jkffilelist = '';
      while ( $jkffilecount <= $this->maxFilesKey ) {
        $key = "file_".$jkffilecount;
        if (array_key_exists($key, $_FILES) &&
            array_key_exists('tmp_name', $_FILES[$key])) {
          $jkffilelist .= ' ' . $_FILES[$key]['tmp_name'];
        }
        $jkffilecount++;
      }
      exec("/bin/chmod g+r " . $jkffilelist); // Need clamd to read them!
      $jkfinfected = 0;
      $jkfoutput = array();
      $jkfclam = exec($jkfclamdscan . $jkffilelist, $jkfoutput, &$jkfinfected);
      if ($jkfinfected == 1) {
        return $smarty->getConfigVariable('ErrorVirusFound');
      }
      if ($jkfinfected == 2) {
        return $smarty->getConfigVariable('ErrorVirusFailed');
      }
    }

    //
    // JKF End

    if ( ! $senderName ) {
      return $smarty->getConfigVariable('ErrorSenderName');
    }
    if ( ! $senderEmail ) {
      return $smarty->getConfigVariable('ErrorSenderEmail');
    }
    if ( ! preg_match($this->_dropbox->validEmailRegexp(),$senderEmail,$emailParts) ) {
      return $smarty->getConfigVariable('ErrorSenderBadEmail');
    }
    $senderEmail = $emailParts[1]."@".$emailParts[2];
    
    //  Invent a passcode and claim ID:
    $claimPasscode = NSSGenerateCode();
    $claimID = NULL; $claimDir = NULL;
    if ( ! $this->_dropbox->directoryForDropoff($claimID,$claimDir) ) {
      return $smarty->getConfigVariable('ErrorUniqueDir');
    }
    
    //  Insert into database:
    if ( $this->_dropbox->database->DBStartTran() ) {
      if ( $dropoffID = $this->_dropbox->database->DBAddDropoff($claimID,
                          $claimPasscode,
                          $this->_dropbox->authorizedUser(),
                          $senderName, $senderOrganization, $senderEmail,
                          $_SERVER['REMOTE_ADDR'],
                          $confirmDelivery,
                          timestampForTime(time()),
                          $note) ) {

        //  Add recipients:
        if ( ! $this->_dropbox->database->DBAddRecipients($recipients, $dropoffID) ) {
          $this->_dropbox->database->DBRollbackTran();
          return $smarty->getConfigVariable('ErrorStoreRecipients');
        }
        
        //  Process the files:
        $i = 1;
        $realFileCount = 0;
        $tplFiles = array(); // These are the file hashes we process in tpl.
        //while ( $i <= $fileCount ) {
        while ( $i <= $this->maxFilesKey ) {
          $key = "file_".$i;
          $selectkey = 'file_select_'.$i;
          if ( array_key_exists($selectkey, $_POST) &&
               $_POST[$selectkey] != "-1" ) {
            // It's a library file.
            // Get the name of the library file they want (safely)
            // by removing all "../" elements and things like it
            $libraryfile = preg_replace('/\.\.[:\/\\\]/', '', $_POST[$selectkey]);
            $libraryfile = paramPrepare($libraryfile);
            // Generate a random filename (collisions are very unlikely)
            $tmpname = mt_rand(10000000, 99999999);
            // Link in the library file
            symlink($this->_dropbox->libraryDirectory().'/'.$libraryfile,
                    $claimDir.'/'.$tmpname);

            // Now strip off the possible subdirectory name as we only
            // want it in the symlink and not after that.
            $libraryfilesize = filesize($this->_dropbox->libraryDirectory().'/'.$libraryfile);
            $libraryfile = trim(preg_replace('/^.*\//', '', $libraryfile));

            // We use this a few times
            $librarydesc = paramPrepare(trim($_POST["desc_".$i]));

            //  Add to database:
            if ( ! $this->_dropbox->database->DBAddFile1($dropoffID, $tmpname,
                             $libraryfile,
                             $libraryfilesize,
                             "application/octet-stream",
                             $librarydesc) ) {
              //  Exit gracefully -- dump database changes and remove the dropoff
              //  directory:
              $this->_dropbox->writeToLog("error while adding dropoff library file to database for $claimID");
              if ( ! rmdir_r($claimDir) ) {
                $this->_dropbox->writeToLog("unable to remove $claimDir -- orphaned!!");
              }
              if ( ! $this->_dropbox->database->DBRollbackTran() ) {
                $this->_dropbox->writeToLog("failed to ROLLBACK after botched dropoff:  $claimID");
                $this->_dropbox->writeToLog("there may be orphans");
              }
              return sprintf($smarty->getConfigVariable('ErrorNamedStore'),
                             $libraryfile);
            }
            
            //  That's right, one more file!
            $realFileCount++;
            
            $tplFiles[$i] = array();
            $tplFiles[$i]['name'] = $libraryfile;
            $tplFiles[$i]['type'] = 'Library';
            $tplFiles[$i]['size'] = NSSFormattedMemSize($libraryfilesize);
            $tplFiles[$i]['description'] = $librarydesc;

            // Update the description in the library index
            $this->_dropbox->database()->DBUpdateLibraryDescription($libraryfile, $librarydesc);
          } elseif ( $_FILES[$key]['name'] ) {
            // It's an uploaded file
            $tmpname = basename($_FILES[$key]['tmp_name']);
            // Get file size from local copy, not what browser told us
            $bytes = filesize($_FILES[$key]['tmp_name']);
            if ( ! move_uploaded_file($_FILES[$key]['tmp_name'],$claimDir."/".$tmpname) ) {
              //  Exit gracefully -- dump database changes and remove the dropoff
              //  directory:
              $this->_dropbox->writeToLog("error while storing dropoff files for $claimID");
              if ( ! rmdir_r($claimDir) ) {
                $this->_dropbox->writeToLog("unable to remove $claimDir -- orphaned!!");
              }
              if ( ! $this->_dropbox->database->DBRollbackTran() ) {
                $this->_dropbox->writeToLog("failed to ROLLBACK after botched dropoff:  $claimID");
                $this->_dropbox->writeToLog("there may be orphans");
              }
              return sprintf($smarty->getConfigVariable('ErrorNamedDrop'),
                             $_FILES[$key]['name']);
            }
            //if ( ($bytes = $_FILES[$key]['size']) < 0 ) {
            //  //  Grrr...stupid 32-bit nonsense.  Convert to the positive
            //  //  value float-wise:
            //  $bytes = ($bytes & 0x7FFFFFFF) + 2147483648.0;
            //}
            //  Add to database:
            if ( ! $this->_dropbox->database->DBAddFile1($dropoffID, $tmpname,
                             paramPrepare($_FILES[$key]['name']),
                             $bytes,
                             ( $_FILES[$key]['type'] ? $_FILES[$key]['type']
                               : "application/octet-stream" ),
                             paramPrepare($_POST["desc_".$i])) ) {
              //  Exit gracefully -- dump database changes and remove the dropoff
              //  directory:
              $this->_dropbox->writeToLog("error while adding dropoff file to database for $claimID");
              if ( ! rmdir_r($claimDir) ) {
                $this->_dropbox->writeToLog("unable to remove $claimDir -- orphaned!!");
              }
              if ( ! $this->_dropbox->database->DBRollbackTran() ) {
                $this->_dropbox->writeToLog("failed to ROLLBACK after botched dropoff:  $claimID");
                $this->_dropbox->writeToLog("there may be orphans");
              }
              return sprintf($smarty->getConfigVariable('ErrorNamedStore'),
                             $_FILES[$key]['name']);
            }
            
            //  That's right, one more file!
            $realFileCount++;
            
            $tplFiles[$i] = array();
            $tplFiles[$i]['name'] = paramPrepare($_FILES[$key]['name']);
            $tplFiles[$i]['type'] = $_FILES[$key]['type'];
            // Get filesize from local copy, not browser $tplFiles[$i]['size'] = NSSFormattedMemSize($_FILES[$key]['size']);
            $tplFiles[$i]['size'] = NSSFormattedMemSize($bytes);
            $tplFiles[$i]['description'] = paramPrepare($_POST["desc_".$i]);
          }
          $i++;
        }
        
        //  Once we get here, it's time to commit the stuff to the database:
	$this->_dropbox->database->DBCommitTran();

        $this->_dropoffID             = $dropoffID;
          
        //  At long last, fill-in the fields:
        $this->_claimID               = $claimID;
        $this->_claimPasscode         = $claimPasscode;
        $this->_claimDir              = $claimDir;
        
        $this->_authorizedUser        = $this->_dropbox->authorizedUser();
        
	$this->_note                  = $note;
        $this->_senderName            = $senderName;
        $this->_senderOrganization    = $senderOrganization;
        $this->_senderEmail           = $senderEmail;
        $this->_senderIP              = $_SERVER['REMOTE_ADDR'];
        $senderIP                     = $_SERVER['REMOTE_ADDR'];
	$senderHost = gethostbyaddr($_SERVER['REMOTE_ADDR']);
        if ($senderHost != '') {
          $senderHost = '(' . $senderHost . ')';
        }
        $this->_confirmDelivery       = $confirmDelivery;
        $this->_informRecipients      = $informRecipients;
        $this->_created               = getdate();
        
        $this->_recipients            = $recipients;
        
        // This Drop-off request has been fulfilled, so kill the keys
        // to stop playback attacks.
        if ($req) {
          $this->_dropbox->DeleteReqData($req);
        }
        if ($auth) {
          $this->_dropbox->DeleteAuthData($auth);
        }

        // Work out the real email subject line.
        if ($reqSubject != '') {
          $emailSubject = $reqSubject;
        } else {
          if ($realFileCount == 1) {
            $emailSubject = sprintf($smarty->getConfigVariable(
                                    'DropoffEmailSubject1'),
                                    $senderName);
          } else {
            $emailSubject = sprintf($smarty->getConfigVariable(
                                    'DropoffEmailSubject2'),
                                    $senderName);
          }
        }

        //  Construct the email notification and deliver:
        $smarty->assign('senderName',  $senderName);
        $smarty->assign('senderOrg',   $senderOrganization);
        $smarty->assign('senderEmail', $senderEmail);
        $smarty->assign('senderIP',    $senderIP);
        $smarty->assign('senderHost',  $senderHost);
        $smarty->assign('note',        trim($note));
        $smarty->assign('subject',     $emailSubject);
        $smarty->assign('now',         timestampForTime(time()));
        $smarty->assign('claimID',     $claimID);
        $smarty->assign('claimPasscode', $claimPasscode);
        $smarty->assign('fileCount',   $realFileCount);
        $smarty->assign('retainDays',  $this->_dropbox->retainDays());
        $smarty->assignByRef('files',  $tplFiles);

        $emailTemplate = $smarty->fetch('dropoff_email.tpl');

        // Inform all the recipients by email if they want me to
        if ($informRecipients) {
          // Do we want to Bcc the sender as well?
          $emailBcc = '';
          if ($this->_dropbox->bccSender()) {
            // and don't forget to encode it if there are intl chars in it
            if (preg_match('/[^\x00-\x7f]/', $senderEmail)) {
              $emailBcc = "Bcc: =?UTF-8?B?".base64_encode(html_entity_decode($senderEmail))."?=".PHP_EOL;
            } else {
              $emailBcc = "Bcc: $senderEmail".PHP_EOL;
            }
          }
          // Make the mail come from the sender, not ZendTo
          foreach ( $recipients as $recipient ) {
            // In MyZendTo, don't send email to myself
            if ((preg_match('/^[yYtT1]/', MYZENDTO) && $senderEmail != $recipient[1]) || preg_match('/^[^yYtT1]/', MYZENDTO)) {
              $emailContent = preg_replace('/__EMAILADDR__/', urlencode($recipient[1]), $emailTemplate);
              $success = $this->_dropbox->deliverEmail(
                  $recipient[1],
                  $senderEmail,
                  $emailSubject,
                  $emailContent,
                  $emailBcc
               );
              $emailBcc = ''; // Only Bcc the sender on the first email out
              if ( ! $success ) {
                $this->_dropbox->writeToLog(sprintf("notification email not delivered successfully to %s for claimID $claimID",$recipient[1]));
              } else {
                $this->_dropbox->writeToLog(sprintf("notification email delivered successfully to %s for claimID $claimID",$recipient[1]));
              }
            }
          }
        }
        
        //  Log our success:
        $this->_dropbox->writeToLog(sprintf("$senderName <$senderEmail> => $claimID [%s]",
                                     ( $realFileCount == 1 ? "1 file" : "$realFileCount files" )));
      } else {
        return $smarty->getConfigVariable('ErrorAddDropoff');
      }
    } else {
      return $smarty->getConfigVariable('ErrorBeginTransaction');
    }
    return NULL;
  }

}

?>
