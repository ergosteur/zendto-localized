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

//
// Authenticator that uses a simple table in the SQL database.
// Each user has the following properties:
// username, passwordhash (stored as MD5 hash), mail, displayName and
// organization.
// The properties in the results hash from authenticate and validate are
// uid = username, mail = mail, cn = displayName, displayName = displayName,
// organization = organization.

class NSSLocalAuthenticator extends NSSAuthenticator {
  private $_db = NULL;
  private $_prefs = NULL;

  public function __construct( $prefs, $db )
  {
    if ( $prefs['authLocalAdmins'] && (! $prefs['authAdmins']) ) {
      $prefs['authAdmins'] = $prefs['authLocalAdmins'];
    }
    parent::__construct($prefs, $db);
    
    // Set $this->_db in here to get the database handle.
    $this->_db = $db;
    $this->_prefs = $prefs;
  }

  public function description()
  {
    $desc = "NSSLocalAuthenticator {\n".
            "  database:  ".$this->_db."\n".
            parent::description()."\n".
            "}";
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


  // Is this a valid username, and if so what are all its properties.
  // Need to calculate uid, mail, cn, displayName and organization.
  public function validUsername ( $uname, &$response )
  {
    $result = FALSE;

    if ( preg_match($this->_prefs['usernameRegexp'], strtolower($uname),$pieces) )
    {
      $q = $this->_db->DBReadLocalUser($uname);

      if ($q) {
        $response = array(
            'uid'   => $q[0]['username'],
            'mail'  => $q[0]['mail'],
            'cn'    => $q[0]['displayname'],
            'displayName' => $q[0]['displayname'],
            'organization' => $q[0]['organization']
        );
        $result = TRUE;
      } else {
        $result = FALSE;
      }

      //  Chain to the super class for any further properties to be added
      //  to the $response array:
      parent::validUsername($uname,$response);
    }
    return $result;
  }

  // Try to authenticate this username and password.
  // Fill in the response if it's valid, with the uid, mail, cn, displayName
  // and organization. They will be columns in the database table.
  public function authenticate( $uname, $password, &$response )
  {
    $result = FALSE;

    if ( preg_match($this->_prefs['usernameRegexp'], strtolower($uname),$pieces) )
    {
      $q = $this->_db->DBReadLocalUser($uname);

      if ($q) {
        if (md5($password) == $q[0]['password']) {
          $response = array(
              'uid'   => $q[0]['username'],
              'mail'  => $q[0]['mail'],
              'cn'    => $q[0]['displayname'],
              'displayName' => $q[0]['displayname'],
              'organization' => $q[0]['organization']
          );
          $result = TRUE;
        } else {
          $result = FALSE;
        }
      } else {
        $result = FALSE;
      }

      //  Chain to the super class for any further properties to be added
      //  to the $response array:
      parent::authenticate($uname,$password,$response);
    }
    return $result;
  }

}

?>
