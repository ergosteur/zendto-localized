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
  @class NSSLDAPAuthenticator
  
  Uses one or more LDAP servers to authenticate users.  The constructor
  wants the following attributes:
  
    ===                   =====
    Key                   Value
    ===                   =====
    "authLDAPServers"     Array of hostnames to try binding to
    "authLDAPBaseDN"      Base distinguished name for search/bind
    "authLDAPAdmins"      Cheap way to grant admin privs to users; an
                          array of uname's
    "authLDAPOrganization" Readable name of your organisation
  
  As written, the connection will be established exclusively via the
  version 3 protocol and will be TLS-encrypted.
*/
class NSSLDAPAuthenticator extends NSSAuthenticator {

  //  Instance data:
  protected $_ldapServers = NULL;
  protected $_ldapBase = NULL;
  protected $_ldapUseSSL = NULL;
  protected $_ldapFullName = 'givenName sn';
  protected $_ldapDn = NULL;
  protected $_ldapPass = NULL;
  protected $_ldapMemberKey = NULL;
  protected $_ldapMemberRole = NULL;
  protected $_ldapOrg = NULL;

  /*!
    @function _construct
    
    Makes instance-copies of the LDAP server list and base DN.
    $db parameter not used in this authenticator.
  */
  public function __construct(
    $prefs, $db
  )
  {
    if ( $prefs['authLDAPAdmins'] && (! $prefs['authAdmins']) ) {
      $prefs['authAdmins'] = $prefs['authLDAPAdmins'];
    }
    parent::__construct($prefs, $db);
    
    $this->_ldapServers   = $prefs['authLDAPServers'];
    $this->_ldapBase      = $prefs['authLDAPBaseDN'];
    $this->_ldapUseSSL    = $prefs['authLDAPUseSSL'];
    $this->_ldapFullName  = $prefs['authLDAPFullName'];
    $this->_ldapDn        = $prefs['authLDAPBindDn'];
    $this->_ldapPass      = $prefs['authLDAPBindPass'];
    // User must be member of LDAPMemberRole, key name is LDAPMemberKey
    $this->_ldapMemberKey = strtolower($prefs['authLDAPMemberKey']);
    $this->_ldapMemberRole= strtolower($prefs['authLDAPMemberRole']);
    $this->_ldapOrg       = trim($prefs['authLDAPOrganization']);

  }
  


  /*!
    @function description
    
    Summarizes the instance -- includes the server list and base DN.
  */
  public function description()
  {
    $desc = 'NSSLDAPAuthenticator {
  base-dn: '.$this->_ldapBase.'
  servers: (
';
    foreach ( $this->_ldapServers as $ldapServer ) {
      $desc .= "              $ldapServer\n";
    }
    $desc.'           )
';
    $desc .= parent::description().'
}';
    return $desc;
  }

  /*!
    @function checkRecipient

    Performs any additional checks on the recipient email address to
    see if it is valid or not, given the result so far and the
    recipient email address.
    The result is ignored if the user has logged in, this is only for
    un-authenticated users.
    Can over-ride the result so far if it chooses.

    Over-ride this function in your authenticator class if necessary
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
    
    Does an anonymous bind to one of the LDAP servers and searches for the
    first record that matches "uid=$uname".
  */
  public function validUsername(
    $uname,
    &$response
  )
  {
    global $smarty;

    $result = FALSE;
    
    //  Bind to one of our LDAP servers:
    foreach ( $this->_ldapServers as $ldapServer ) {
      //if($this->_ldapUseSSL){$ldapServer="ldaps://".$ldapServer;}
      if ( $ldapConn = ldap_connect($ldapServer) ) {
        //  Set the protocol to 3 only:
        ldap_set_option($ldapConn,LDAP_OPT_PROTOCOL_VERSION,3);
        
        //  Connection made, now attempt to start TLS and bind anonymously:
        //  Only do start_tls if ldapUseSSL is false
        if ( !$this->_ldapUseSSL || ldap_start_tls($ldapConn) ) {
          if ( $ldapBind = @ldap_bind($ldapConn, $this->_ldapDn, $this->_ldapPass) ) {
            break;
          }
        }
      }
    }
    if ( $ldapBind ) {
      $ldapSearch = ldap_search($ldapConn,$this->_ldapBase,"uid=$uname");
      if ( $ldapSearch && ($ldapEntry = ldap_first_entry($ldapConn,$ldapSearch)) && ($ldapDN = ldap_get_dn($ldapConn,$ldapEntry)) ) {
        //  We got a result and a DN for the user in question, so
        //  that means s/he exists!
        $result = TRUE;
        if ( $responseArray = ldap_get_attributes($ldapConn,ldap_first_entry($ldapConn,$ldapSearch)) ) {
          $response = array();
          foreach ( $responseArray as $key => $value ) {
            if ( $value['count'] >= 1 ) {
              $response[$key] = $value[0];
            } else {
              $response[$key] = $value;
            }
            // Store the list of groups they are a member of
            if (strtolower($key) == $this->_ldapMemberKey) {
              $groups = $value;
            }
          }
          // Set displayName and cn if not already set
          if ($this->_ldapFullName != "displayName") {
            $nameKeys = explode(" ", $this->_ldapFullName);
            $nameWords = array();
            foreach ($nameKeys as $k) {
              if ($k) {
                $nameWords[] = $response[$k];
              }
            }
            $response['displayName'] = implode(' ', $nameWords);
          }
          if (!$response['cn']) {
            $response['cn'] = $response['displayName'];
          }
          if (!$response['organization']) {
            $response['organization'] = $this->_ldapOrg;
          }
          // Do the authorisation check. User must be a member of a group.
          $authorisationPassed = TRUE;
          if ($this->_ldapMemberKey != '' && $this->_ldapMemberRole != '') {
            $authorisationPassed = FALSE;
            foreach ($groups as $group) {
              if (strtolower($group) == $this->_ldapMemberRole) {
                $authorisationPassed = TRUE;
              }
            }
          }
          if (!$authorisationPassed) {
            NSSError($smarty->getConfigVariable('ErrorUnauthorizedUser'),'Authorisation Failed');
            $result = FALSE;
          }

          //  Chain to the super class for any further properties to be added
          //  to the $response array:
          parent::validUsername($uname,$response);
        }
      }
    } else {
      NSSError('Unable to connect to any of the LDAP servers; could not authenticate user.','LDAP Error');
    }
    if ( $ldapConn ) {
      ldap_close($ldapConn);
    }
    return $result;
  }
  


  /*!
    @function authenticate
    
    Does an anonymous bind to one of the LDAP servers and searches for the
    first record that matches "uid=$uname".  Once that record is found, its
    DN is extracted and we try to re-bind non-anonymously, with the provided
    password.  If it works, voila, the user is authenticated and we return
    all the info from his/her directory entry.
  */
  public function authenticate(
    $uname,
    $password,
    &$response
  )
  {
    global $smarty;

    $result = FALSE;
    
    //  Bind to one of our LDAP servers:
    foreach ( $this->_ldapServers as $ldapServer ) {
      //if($this->_ldapUseSSL){$ldapServer="ldaps://".$ldapServer;}
      if ( $ldapConn = ldap_connect($ldapServer) ) {
        //  Set the protocol to 3 only:
        ldap_set_option($ldapConn,LDAP_OPT_PROTOCOL_VERSION,3);
        
        //  Connection made, now attempt to start TLS and bind anonymously:
        //  Only do start_tls if ldapUseSSL is false
        if ( !$this->_ldapUseSSL || ldap_start_tls($ldapConn) ) {
          if ( $ldapBind = @ldap_bind($ldapConn, $this->_ldapDn, $this->_ldapPass) ) {
            break;
          }
        }
      }
    }
    if ( $ldapBind ) {
      $ldapSearch = ldap_search($ldapConn,$this->_ldapBase,"uid=$uname");
      if ( $ldapSearch && ($ldapEntry = ldap_first_entry($ldapConn,$ldapSearch)) && ($ldapDN = ldap_get_dn($ldapConn,$ldapEntry)) ) {
        //  We got a result and a DN for the user in question, so
        //  try binding as the user now:
        if ( $result = @ldap_bind($ldapConn,$ldapDN,$password) ) {
          if ( $responseArray = ldap_get_attributes($ldapConn,ldap_first_entry($ldapConn,$ldapSearch)) ) {
            $response = array();
            foreach ( $responseArray as $key => $value ) {
              if ( $value['count'] >= 1 ) {
                $response[$key] = $value[0];
              } else {
                $response[$key] = $value;
              }
              // Store the list of groups they are a member of
              if (strtolower($key) == $this->_ldapMemberKey) {
                $groups = $value;
              }
            }
            // Set displayName=cn if not already set
            if ($this->_ldapFullName != "displayName") {
              $nameKeys = explode(" ", $this->_ldapFullName);
              $nameWords = array();
              foreach ($nameKeys as $k) {
                if ($k) {
                  $nameWords[] = $response[$k];
                }
              }
              $response['displayName'] = implode(' ', $nameWords);
            }
            if (!$response['cn']) {
              $response['cn'] = $response['displayName'];
            }
            if (!$response['organization']) {
              $response['organization'] = $this->_ldapOrg;
            }
            // Do the authorisation check. User must be a member of a group.
            $authorisationPassed = TRUE;
            if ($this->_ldapMemberKey != '' && $this->_ldapMemberRole != '') {
              $authorisationPassed = FALSE;
              foreach ($groups as $group) {
                if (strtolower($group) == $this->_ldapMemberRole) {
                  $authorisationPassed = TRUE;
                }
              }
            }
            if (!$authorisationPassed) {
              NSSError($smarty->getConfigVariable('ErrorUnauthorizedUser'),'Authorisation Failed');
              $result = FALSE;
            }
            //  Chain to the super class for any further properties to be added
            //  to the $response array:
            parent::authenticate($uname,$password,$response);
          }
        }
      }
    } else {
      NSSError('Unable to connect to any of the LDAP servers; could not authenticate user.','LDAP Error');
    }
    if ( $ldapConn ) {
      ldap_close($ldapConn);
    }
    return $result;
  }

}

?>
