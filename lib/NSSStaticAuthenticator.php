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
  @class NSSStaticAuthenticator
  
  Used for testing purposes.  This authenticator has a single pseudo-user associated with it.
  There are no attributes necessary.
  
    Username:       test
    Password:       changeme
    Canonical Name: Test User
    Email:          test@nowhere.org
  
  I've added no comments to the class because there's not much to say!
*/

define('NSS_STATIC_UID','test');

class NSSStaticAuthenticator extends NSSAuthenticator {

  // $db parameter not used in this authenticator.
  public function __construct(
    $prefs, $db
  )
  {
    $adjPrefs = $prefs;
    $adjPrefs['authAdmins'] = array( NSS_STATIC_UID );
    
    parent::__construct($adjPrefs, $db);
  }


  
  public function description()
  {
    return 'NSSStaticAuthenticator {
'.parent::description().'
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
 

  public function validUsername(
    $uname,
    &$response
  )
  {
    if ( $uname == NSS_STATIC_UID ) {
      $response = array( 'uid' => NSS_STATIC_UID , 'mail' => NSS_STATIC_UID.'@nowhere.org' , 'cn' => 'Test User' , 'displayName' => 'Test User' );
      
      //  Chain to the super class for any further properties to be added
      //  to the $response array:
      parent::validUsername($uname,$response);
      
      return TRUE;
    }
    return FALSE;
  }
  
  
  
  public function authenticate(
    $uname,
    $password,
    &$response
  )
  {
    if ( ($uname == NSS_STATIC_UID) && ($password == 'changeme') ) {
      $response = array( 'uid' => NSS_STATIC_UID , 'mail' => NSS_STATIC_UID.'@nowhere.org' , 'cn' => 'Test User' , 'displayName' => 'Test User' );
      
      //  Chain to the super class for any further properties to be added
      //  to the $response array:
      parent::authenticate($uname,$password,$response);
      
      return TRUE;
    }
    return FALSE;
  }

}

?>
