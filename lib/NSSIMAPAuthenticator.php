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

class NSSIMAPAuthenticator extends NSSAuthenticator {
  private $_imapServer= NULL;
  private $_imapDomain= NULL;
  private $_imapOrg   = NULL;
  private $_prefs = NULL;

  // $db parameter not used in this authenticator.
  public function __construct( $prefs, $db )
  {
    if ( $prefs['authIMAPAdmins'] && (! $prefs['authAdmins']) ) {
      $prefs['authAdmins'] = $prefs['authIMAPAdmins'];
    }
    parent::__construct($prefs, $db);
    
    $this->_imapServer= trim($prefs['authIMAPServer']);
    $this->_imapDomain= trim($prefs['authIMAPDomain']);
    if ($this->_imapDomain) {
      // _imapDomain includes the @ if it is set, makes later code simpler
      $this->_imapDomain = '@'.$this->_imapDomain;
    } else {
      $this->_imapDomain = "";
    }
    $this->_imapOrg= trim($prefs['authIMAPOrganization']);
    $this->_prefs = $prefs;
  }

  public function description()
  {
    $desc = "NSSIMAPAuthenticator {\n".
            "  domain:  ".$this->_imapDomain."\n".
            "  server:  ".$this->_imapServer."\n".
            "  org:     ".$this->_imapOrg."\n".
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


  public function validUsername ( $uname, &$response )
  {
    $result = FALSE;

    if ( ! $this->_imapDomain ) {
      // There is no imapDomain so use the full supplied address as the uname
      $response = array(
          'uid'   => $uname,
          'mail'  => $uname,
          'cn'    => $uname,
          'displayName' => $uname,
          'organization' => $this->_imapOrg
      );
      $result = TRUE;
      //  Chain to the super class for any further properties to be added
      //  to the $response array:
      parent::validUsername($uname,$response);
    } else {
      // imapDomain is set, so strip out the username
      if ( preg_match($this->_prefs['usernameRegexp'], strtolower($uname),$pieces) )
      {
        $response = array(
            'uid'   => $pieces[1],
            'mail'  => $pieces[1] .$this->_imapDomain,
            'cn'    => $pieces[1] .$this->_imapDomain,
            'displayName' => $pieces[1] .$this->_imapDomain,
            'organization' => $this->_imapOrg
        );
        $result = TRUE;
        //  Chain to the super class for any further properties to be added
        //  to the $response array:
        parent::validUsername($uname,$response);
      }
    }
			

    return $result;
  }

  public function authenticate( $uname, $password, &$response )
  {
    $result = FALSE;

    $mbox = @imap_open('{'.$this->_imapServer.'}INBOX', $uname, $password);
    if ($mbox)
    {
      $minfo = @imap_status($mbox, '{'.$this->_imapServer.'}INBOX', SA_MESSAGES);
      if ($minfo)
      {
        $response = array(
            'uid'   => strtolower($uname),
            'mail'  => strtolower($uname).$this->_imapDomain,
            'cn'    => strtolower($uname).$this->_imapDomain,
            'displayName' => strtolower($uname).$this->_imapDomain,
            'organization' => $this->_imapOrg
        );
        $result = TRUE;

        //  Chain to the super class for any further properties to be added
        //  to the $response array:
        parent::authenticate($uname,$password,$response);
      }
    }
    @imap_close($mbox);
    return $result;
  }

}

?>
