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

/*!
  @class NSSAuthenticator
  
  Abstract class interface to support basic user authentication.
  Authentication methods are implemented as concrete classes that
  conform to this interface.
*/
abstract class NSSAuthenticator {

  private $_adminList = NULL;
  private $_statsList = NULL;
  private $_defaultEmailDomain = NULL;

  /*!
    @function __construct
    
    Constructor function for the concrete classes.  The $prefs
    array should contain any of the attributes necessary to the
    individual authenticators.
  */
  public function __construct(
    $prefs, $db
  )
  {
    $this->_defaultEmailDomain = $prefs['defaultEmailDomain'];
    if ( $prefs['authAdmins'] ) {
      $this->_adminList = $prefs['authAdmins'];
    }
    if ( $prefs['authStats'] ) {
      $this->_statsList = $prefs['authStats'];
    }
  }
  
  /*!
    @function description
    
    Returns a textual description of the authenticator instance,
    suitable for debugging purposes.
  */
  public function description()
  {
    $desc = 'NSSAuthenticator {
  admins:  (
';
    if ( $this->_adminList && count($this->_adminList) ) {
      foreach ( $this->_adminList as $uname ) {
        $desc .= '          '.$uname."\n";
      }
    }
    return $desc.'  )
}';
  }
  
  /*!
    @function checkRecipient

    Performs any additional checks on the recipient email address to
    see if it is valid or not, given the result so far and the
    recipient email address.
    The result is ignored if the user has logged in, this is only for
    un-authenticated users.
    Can over-ride the result so far if it chooses.

    Over-ride this function in your own authenticator class if necessary
    for your site.
  */
  public function checkRecipient(
    $sofar,
    $recipient
  )
  {
    return $sofar;
  }

  /*!
    @function validUsername
    
    Returns TRUE if $uname is a valid username within the context of
    the authenticator.  On return, the variable referenced by
    $response will then contain an array, keyed by LDAP-style
    attribute names, with the various attributes for the user.
    
    Returns FALSE in case of any error.
  */
  public function validUsername(
    $uname,
    &$response
  )
  {
    if ( $response && $this->isAdmin($uname) ) {
      $response['grantAdminPriv'] = TRUE;
      $response['grantStatsPriv'] = TRUE;
    }
    if ( $response && $this->isStats($uname) ) {
      $response['grantStatsPriv'] = TRUE;
    }
    if ( $response ) {
      if ( $response['cn'] == "" || $response['displayName'] == "" ) {
        $n = $response['cn'] + $response['displayName'];
        /// Last ditch default, just use the username
        if ( $n == "" ) {
          $n = $uname;
        }
        $response['cn'] = $n;
        $response['displayName'] = $n;
      }
      if ( $response['mail'] == "" ) {
        $response['mail'] = $uname . '@' . $this->_defaultEmailDomain;
      }
    }
    return TRUE;
  }
  
  /*!
    @function authenticate
    
    Returns TRUE if $uname is a valid username within the context of
    the authenticator and $password is the correct password for that
    user.  On return, the variable referenced by $response will then
    contain an array, keyed by LDAP-style attribute names, with the
    various attributes for the user.
    
    Returns FALSE in case of any error.
  */
  public function authenticate(
    $uname,
    $password,
    &$response
  )
  {
    if ( $response && $this->isAdmin($uname) ) {
      $response['grantAdminPriv'] = TRUE;
      $response['grantStatsPriv'] = TRUE;
    }
    if ( $response && $this->isStats($uname) ) {
      $response['grantStatsPriv'] = TRUE;
    }
    if ( $response ) {
      if ( $response['cn'] == "" || $response['displayName'] == "" ) {
        $n = $response['cn'] + $response['displayName'];
        /// Last ditch default, just use the username
        if ( $n == "" ) {
          $n = $uname;
        }
        $response['cn'] = $n;
        $response['displayName'] = $n;
      }
      if ( $response['mail'] == "" ) {
        $response['mail'] = $uname . '@' . $this->_defaultEmailDomain;
      }
    }
    return TRUE;
  }
  
  /*!
    @function isAdmin
    
  */
  private function isAdmin(
    $uname
  )
  {
    if ( $this->_adminList ) {
      return in_array($uname,$this->_adminList);
    }
    return NULL;
  }
  
  /*!
    @function isStats
    
  */
  private function isStats(
    $uname
  )
  {
    if ( $this->_statsList ) {
      return in_array($uname,$this->_statsList);
    }
    return NULL;
  }
  
}



/*!
  @function NSSAuthenticator
  
  Create an authenticator based upon the contents of the $prefs
  array.  The array should contain (at least) a value keyed by
  the string "authenticator" and having the value:
  
    "LDAP"          NSSLDAPAuthenticator class
    "Static"        NSSStaticAuthenticator class
    "IMAP"          NSSIMAPAuthenticator class
    
  The array should also contain any attributes needed by the target
  class' constructor function.
  
  Returns NULL if an authenticator could not be created.
*/
function NSSAuthenticator(
  $prefs, $db=NULL
)
{
	$authenticator= 'NSS' . $prefs['authenticator'] . 'Authenticator';
	if ( file_exists( NSSDROPBOX_LIB_DIR . $authenticator . '.php' ) )
	{
		include_once( NSSDROPBOX_LIB_DIR . $authenticator . '.php');
		return new $authenticator ( $prefs, $db );
	}
	return NULL;
}

?>
