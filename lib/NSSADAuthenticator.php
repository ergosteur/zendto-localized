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
  @class NSSADAuthenticator
  
  Uses one or more Active Directory LDAP servers to authenticate users.  The constructor
  wants the following attributes:
  
    ===              	   	=====
    Key                 	Value
    ===                   	=====
    "authLDAPServers"     	Array of hostnames to try binding to
    "authLDAPBaseDN"      	Base distinguished name for search/bind
    "authLDAPAdmins"      	Cheap way to grant admin privs to users; an
                          	array of sAMAccountName's
    "authLDAPAccountSuffix"	Suffix required to specify Domain for username e.g.
				username@localdomain 
    "authLDAPUseSSL"		Should be set to true to encrypt passwords
    "authLDAPBindUser"		Unprivileged user in Active Directory as we cannot 
				bind anonymously
    "authLDAPBindUser"		Unprivileged user's password in AD as we cannot 
				bind anonymously


Example for preferences.php:

 'authenticator'         => 'AD',
 'authLDAPBaseDN'        => 'DC=myorg,DC=ac,DC=uk',
 'authLDAPServers'       => array('mydc.domain.tld','myotherserver.domain.tld'),
 'authLDAPAdmins'        => array('sysadmin1','sysadmin2'),
 'authLDAPAccountSuffix' => '@domain.tld',
 'authLDAPUseSSL'        => true,
 'authLDAPBindUser'      => 'dropboxunpriv@domain.tld',
 'authLDAPBindPass'      => 'secretpw',

*/
class NSSADAuthenticator extends NSSAuthenticator {

  //  Instance data:
  protected $_ldapServers = NULL;
  protected $_ldapBase = NULL;
  protected $_ldapAccountSuffix = NULL;
  protected $_ldapUseSSL = NULL;
  protected $_ldapBindUser = NULL;
  protected $_ldapBindPass  = NULL; 
  protected $_ldapBindOrg = NULL;
  protected $_ldapMemberKey = NULL;
  protected $_ldapMemberRole = NULL;
 
  /*!
    @function _construct
    
    Makes instance-copies of the LDAP server list and base DN.
    $db parameter not used in this authenticator.
  */
  public function __construct(
    $prefs, $db
  )
  {
    if  ( $prefs['authLDAPAdmins'] && (! $prefs['authAdmins']) ) {
      $prefs['authAdmins'] = $prefs['authLDAPAdmins'];
    }
    parent::__construct($prefs, $db);
    
    $this->_ldapServers1  		= $prefs['authLDAPServers1'];
    $this->_ldapBase1     		= $prefs['authLDAPBaseDN1'];
    $this->_ldapAccountSuffix1  	= $prefs['authLDAPAccountSuffix1'];
    $this->_ldapUseSSL1     		= $prefs['authLDAPUseSSL1'];
    $this->_ldapBindUser1  		= $prefs['authLDAPBindUser1'];
    $this->_ldapBindPass1     		= $prefs['authLDAPBindPass1'];
    $this->_ldapOrg1                    = $prefs['authLDAPOrganization1'];

    $this->_ldapServers2      		= $prefs['authLDAPServers2'];
    $this->_ldapBase2         		= $prefs['authLDAPBaseDN2'];
    $this->_ldapAccountSuffix2   	= $prefs['authLDAPAccountSuffix2'];
    $this->_ldapUseSSL2        		= $prefs['authLDAPUseSSL2'];
    $this->_ldapBindUser2    		= $prefs['authLDAPBindUser2'];
    $this->_ldapBindPass2     		= $prefs['authLDAPBindPass2'];
    $this->_ldapOrg2                    = $prefs['authLDAPOrganization2'];

    $this->_ldapMemberKey = strtolower($prefs['authLDAPMemberKey']);
    $this->_ldapMemberRole= strtolower($prefs['authLDAPMemberRole']);
  }
  


  /*!
    @function description
    
    Summarizes the instance -- includes the server list and base DN.
  */
  public function description()
  {
    if (is_array($this->_ldapBase)) {
      $base = '';
      foreach ( $this->_ldapBase as $ldapBase ) {
        $base .= " $ldapBase";
      }
    } else {
      $base = $this->_ldapBase;
    }
    $desc = 'NSSADAuthenticator {
  base-dn: '.$base.'
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
    $result = FALSE;

    $this->_ldapServers = $this->_ldapServers1;
    $this->_ldapUseSSL  = $this->_ldapUseSSL1;
    $this->_ldapBindUser = $this->_ldapBindUser1;
    $this->_ldapBindPass = $this->_ldapBindPass1;
    $this->_ldapBase     = $this->_ldapBase1;
    $this->_ldapAccountSuffix = $this->_ldapAccountSuffix1;
    $this->_ldapOrg      = $this->_ldapOrg1;

    $result = $this->Tryvalid($uname, $response);
    if ($result !== -70 && $result !== -69) {
      return TRUE;
    }

    // Bail out quietly if there isn't a 2nd AD forest
    if (empty($this->_ldapServers2)) {
      return FALSE;
    }
    $this->_ldapServers = $this->_ldapServers2;
    $this->_ldapUseSSL  = $this->_ldapUseSSL2;
    $this->_ldapBindUser = $this->_ldapBindUser2;
    $this->_ldapBindPass = $this->_ldapBindPass2;
    $this->_ldapBase     = $this->_ldapBase2;
    $this->_ldapAccountSuffix = $this->_ldapAccountSuffix2;
    $this->_ldapOrg      = $this->_ldapOrg2;

    $result = $this->Tryvalid($uname, $response);
    if ($result === -70) {
      NSSError('Check User: Unable to connect to any of the LDAP servers; could not authenticate user.','LDAP Error');
      return FALSE;
    } else if ($result === -69) {
      // NSSError('Check User: Incorrect username or password.','LDAP Error');
      return FALSE;
    }
    // return $result;
    return TRUE;
  }

  public function Tryvalid(
    $uname,
    &$response
  )
  {
    global $smarty;

    $result = FALSE;
    
    //  Bind to one of our LDAP servers:
    foreach ( $this->_ldapServers as $ldapServer ) {
      if ($this->_ldapUseSSL) {
        $ldapServer = "ldaps://".$ldapServer;
      }
      if ( $ldapConn = ldap_connect($ldapServer) ) {
        //  Set the protocol to 3 only:
        ldap_set_option($ldapConn,LDAP_OPT_PROTOCOL_VERSION,3);
       	ldap_set_option($ldapConn,LDAP_OPT_REFERRALS,0); 
        //  Connection made, now attempt to start TLS and bind anonymously:
          if ( $ldapBind = @ldap_bind($ldapConn,$this->_ldapBindUser,$this->_ldapBindPass) ) {
            break;
          }
      }
    }
    if ( $ldapBind ) {
      if (!is_array($this->_ldapBase)) {
        $this->_ldapBase = array($this->_ldapBase);
      }
      foreach ( $this->_ldapBase as $ldapBase ) {
        $ldapSearch = ldap_search($ldapConn,$ldapBase,"sAMAccountName=$uname");
        if ( $ldapSearch && ($ldapEntry = ldap_first_entry($ldapConn,$ldapSearch)) && ($ldapDN = ldap_get_dn($ldapConn,$ldapEntry)) ) {
          //  We got a result and a DN for the user in question, so
          //  that means s/he exists!
          $result = TRUE;
          if ( $responseArray = ldap_get_attributes($ldapConn,ldap_first_entry($ldapConn,$ldapSearch)) ) {
            $response = array();
            foreach ( $responseArray as $key => $value ) {
              if ( $value['count'] >= 1 ) {
                $response[$key] = $value[0];
                // For Klas Elmby and his AD "proxyAddresses" attribute
                // containing alternate email addresses for this user
                //if ($key=="proxyAddresses") {
                //  $num = 0;
                //  $response['proxyAdd'] = array();
                //  for ($n=0; $n<$value['count']; $n++) {
                //    if (strncasecmp($value[$n],"smtp:",5)==0) {
                //      $response['proxyAdd'][$num] = substr($value[$n],5);
                //      $num++;
                //    }
                //  }
                //  $response['proxyCount'] = $num;
                //  // BUG BUG BUG -- Klas? $response[$key] = $proxStr;
                //}
              } else {
                $response[$key] = $value;
              }
              // Store the list of groups they are a member of
              if (strtolower($key) == $this->_ldapMemberKey) {
                $groups = $value;
              }
            }
            $response['organization'] = $this->_ldapOrg;
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
              //NSSError('This user is not permitted to use this service.','Authorisation Failed');
              // We found the user okay, but he wasn't a group member
              $result = -69;
              if ($ldapConn) {
                ldap_close($ldapConn);
              }
              return $result;
            }
            //  Chain to the super class for any further properties to be added
            //  to the $response array:
            parent::validUsername($uname,$response);
            if ($ldapConn) {
              ldap_close($ldapConn);
            }
            return $result;
          }
        //} else {
        //  if ( $ldapConn ) {
        //    ldap_close($ldapConn);
        //  }
        //  return -69;
        }
      }
      // If we get to here, we managed to contact the server, but couldn't
      // find them in any of the BaseDNs we were told to search.
      if ($ldapConn) {
        ldap_close($ldapConn);
      }
      return -69;
    } else {
      // NSSError('Invalid username: Unable to connect to any of the LDAP servers; could not authenticate user.','LDAP Error');
      if ( $ldapConn ) {
        ldap_close($ldapConn);
      }
      return -70;
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
    &$uname,
    $password,
    &$response
  )
  {
    $result = FALSE;
    
    // The username should not be their email address.
    // So remove everything after any @ sign.
    // And remove any domain name on the front, separated by \
    // Passed by reference so should change what is stored in the calling code.
    $uname = preg_replace('/@.*$/', '', $uname);
    $uname = preg_replace('/^.*\\\/', '', $uname);

    $this->_ldapServers = $this->_ldapServers1;
    $this->_ldapUseSSL  = $this->_ldapUseSSL1;
    $this->_ldapBindUser = $this->_ldapBindUser1;
    $this->_ldapBindPass = $this->_ldapBindPass1;
    $this->_ldapBase     = $this->_ldapBase1;
    $this->_ldapAccountSuffix = $this->_ldapAccountSuffix1;
    $this->_ldapOrg      = $this->_ldapOrg1;

    $result = $this->Tryauthenticate($uname, $password, $response);
    if ($result !== -69 && $result !== -70) {
      return TRUE;
    }

    $this->_ldapServers = $this->_ldapServers2;
    $this->_ldapUseSSL  = $this->_ldapUseSSL2;
    $this->_ldapBindUser = $this->_ldapBindUser2;
    $this->_ldapBindPass = $this->_ldapBindPass2;
    $this->_ldapBase     = $this->_ldapBase2;
    $this->_ldapAccountSuffix = $this->_ldapAccountSuffix2;
    $this->_ldapOrg      = $this->_ldapOrg2;

    $result = $this->Tryauthenticate($uname, $password, $response);
    if ($result === -70) {
      // Failed because we couldn't connect to any auth servers
      NSSError('Check User: Unable to connect to any of the LDAP servers; could not authenticate user.','LDAP Error');
      return FALSE;
    } else if ($result === -69) {
      // Failed because the user failed authentication tests
      // NSSError('Check User: Incorrect username or password.','LDAP Error');
      return FALSE;
    }
    return TRUE;
  }

  public function Tryauthenticate(
    $uname,
    $password,
    &$response
  )
  {
    global $smarty;

    // The username should not be their email address.
    // So remove everything after any @ sign.
    $uname = preg_replace('/@.*$/', '', $uname);
    $uname = preg_replace('/^.*\\\/', '', $uname);

    //  Bind to one of our LDAP servers:
    foreach ( $this->_ldapServers as $ldapServer ) {
      if ($this->_ldapUseSSL) {
        $ldapServer="ldaps://".$ldapServer;
      }
      if ( $ldapConn = ldap_connect($ldapServer) ) {
        // Unfortunately ldap_connect() doesn't actually send any packets,
        // so it will pretty much always succeed even if the server's not
        // there.
        // So if the ldap_bind() fails, I have to fail quietly. :-(
        // Set the protocol to 3 only:
        ldap_set_option($ldapConn,LDAP_OPT_PROTOCOL_VERSION,3);
        ldap_set_option($ldapConn,LDAP_OPT_REFERRALS,0); 
        //  Connection made, now attempt to bind:
        if ( $ldapBind = @ldap_bind($ldapConn,$this->_ldapBindUser,$this->_ldapBindPass) ) {
          break;
        } else {
          // Failed to bind. If the error was 'Can't contact LDAP server'
          // then fail quietly and try the next server, else complain.
          $ldaperror = ldap_error($ldapConn);
          if (! preg_match('/can[not\']* *contact *ldap *server/i',
                           $ldaperror)) {
            NSSError("Connected to $ldapServer but could not bind, it said $ldaperror");
          }
        }
      }
    }
    if ( $ldapBind ) {
      if (!is_array($this->_ldapBase)) {
        $this->_ldapBase = array($this->_ldapBase);
      }
      foreach ( $this->_ldapBase as $ldapBase ) {
        $ldapSearch = ldap_search($ldapConn,$ldapBase,"sAMAccountName=$uname");
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
              $response['organization'] = $this->_ldapOrg;
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
                $result = -69;
                if ( $ldapConn ) {
                  ldap_close($ldapConn);
                }
                return $result;
              }

              // Chain to the super class for any further properties to be added
              // to the $response array:
              parent::authenticate($uname,$password,$response);
              if ( $ldapConn ) {
                ldap_close($ldapConn);
              }
              return $result;
            }
          } else {
            // We found a username matching but password didn't
            if ( $ldapConn ) {
              ldap_close($ldapConn);
            }
            return -69;
          }
          // } else {
          //   if ( $ldapConn ) {
          //     ldap_close($ldapConn);
          //   }
          //   return -69;
        }
      }
      // If we get to here, we managed to contact the server, but couldn't
      // find them in any of the BaseDNs we were told to search.
      if ($ldapConn) {
        ldap_close($ldapConn);
      }
      return -69;
    } else {
      NSSError('Check User: Unable to connect to any of the authentication servers; could not authenticate user.','LDAP Error');
      if ( $ldapConn ) {
        ldap_close($ldapConn);
      }
      return -70;
    }
    if ( $ldapConn ) {
      ldap_close($ldapConn);
    }
    return $result;
  }

}

?>
