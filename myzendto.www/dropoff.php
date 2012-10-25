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
require_once(NSSDROPBOX_LIB_DIR."NSSDropoff.php");

global $smarty;

# Generate unique ID required for progress bars status
$smarty->assign('progress_id', uniqid(""));

function generateEmailTable(
  $aDropbox,
  $label = 1
)
{
  global $smarty;
  $smarty->assign('recipEmailNum', $label);
}

if ( $theDropbox = new NSSDropbox($NSSDROPBOX_PREFS,TRUE) ) {
  
  if ( $_POST['Action'] == "dropoff" ) {
    //
    // Posted form data indicates that a dropoff form was filled-out
    // and submitted; if posted from data is around, creating a new
    // dropoff instance creates a new dropoff using said form data.
    //
    $theDropbox->SetupPage();
    $template = 'show_dropoff.tpl';
    if ( $theDropoff = new NSSDropoff($theDropbox) ) {
      // Allow HTMLWrite to over-ride the template file if it wants to
      $template2 = $theDropoff->HTMLWrite();
      if ($template2 != "") {
        $template = $template2;
      }
    }
    $smarty->display($template);
  
  } else {
    //
    // We need to present the dropoff form.  This page will include some
    // JavaScript that does basic checking of the form prior to submission
    // as well as the code to handle the attachment of multiple files.
    // After all that, we start the page body and write-out the HTML for
    // the form.
    //
    // If the user is authenticated then some of the fields will be
    // already-filled-in (sender name and email).
    //

    //
    // JKF
    //
    // Look up the "auth" parameter from the GET data, and retrieve the
    // Email, FullName, Organization from the SQL table record pointed
    // to by the "auth" key.
    //
    $authEmail = '';
    $authFullName = '';
    $authOrganization = '';
    $authExpiry = 0;
    $auth = $_GET['auth'];

    if (! $theDropbox->authorizedUser()) {
      // They aren't logged in, so chuck them back to the start
      Header( "HTTP/1.1 302 Moved Temporarily" );
      Header( "Location: ".$NSSDROPBOX_URL );
      exit;
    }

    $theDropbox->SetupPage("dropoff.note");

    $authFullName     = $theDropbox->authorizedUserData("displayName");
    $authOrganization = $theDropbox->authorizedUserData("organization");
    $authEmail        = $theDropbox->authorizedUserData("mail");
    $smarty->assign('senderName', $authFullName);
    $smarty->assign('senderOrg', $authOrganization);
    $smarty->assign('senderEmail', strtolower($authEmail));

    generateEmailTable($theDropbox);
      
    $smarty->display('new_dropoff.tpl');
  }
  
}

?>
