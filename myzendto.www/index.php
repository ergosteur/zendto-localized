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
// Include the dropbox preferences -- we need this to have the
// dropbox filepaths setup for us, beyond simply needing our
// configuration!
//
require "../config/preferences.php";
require_once(NSSDROPBOX_LIB_DIR."MyZendTo.Smartyconf.php");
require_once(NSSDROPBOX_LIB_DIR."NSSDropbox.php");

if ( $theDropbox = new NSSDropbox($NSSDROPBOX_PREFS,TRUE) ) {
  //
  // This page handles several actions.  By default, it simply
  // presents whatever "main menu" is appropriate.  This page also
  // handles the presentation of the login "dialog" and subsequently
  // the logout facility.
  //
  switch ( $_GET['action'] ? $_GET['action'] : $_POST['action'] ) {
  
    case 'logout': {
      $theDropbox->logout();
      $theDropbox->SetupPage();
      $smarty->assign('autoHome', TRUE);
      $smarty->display('logout.tpl');
      break;
    }

    default: {
      $aU = $theDropbox->authorizedUser();
      if ($aU) {
        // Show their list of dropoffs
        Header( "HTTP/1.1 302 Moved Temporarily" );
        Header( "Location: ".$NSSDROPBOX_URL."pickup_list.php" );
      } else {
        $theDropbox->SetupPage("login.uname");
        $smarty->display('login.tpl');
      }
      break;
    }
  }

}

?>
