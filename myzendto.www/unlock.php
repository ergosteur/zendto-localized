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

if ( $theDropbox = new NSSDropbox($NSSDROPBOX_PREFS) ) {
  
  $theDropbox->SetupPage();

  if ( $theDropbox->authorizedUser() && $theDropbox->authorizedUserData('grantAdminPriv') ) {
    if ($_POST['action'] == 'unlock') {
      // Unlock the ticked users
      $output = array();
      for ($i=0; $i<=$_POST['unlockMax']; $i++) {
        $user = $_POST['unlocktick_'.$i];
        if ($user) {
          // Unlock the user
          $theDropbox->database->DBDeleteLoginlog($user);
          $output[] = $user;
        }
      }
      if ($output) {
        NSSError("Unlocked ".implode(', ',$output).".");
      }
    }

    // Build the list of locked users
    $all = $theDropbox->database->DBLoginlogAll(time() -
             $theDropbox->loginFailTime());
    $failures = array();
    $lockedout = array();
    $names     = array();
    $unlockMax = 0;
    $max = $theDropbox->loginFailMax();
    foreach ($all as $rec) {
      $failures[$rec['username']]++;
    }
    foreach ($failures as $user => $count) {
      if ($count >= $max) {
        $lockedout[] = $user;
        $props = array();
        $theDropbox->authenticator()->validUsername($user,$props);
        if ($props['displayName']) {
          $names[] = $props['displayName'];
        } else {
          $names[] = "Unknown user";
        }
        $unlockMax++;
      }
    }

    $smarty->assign('lockedout', $lockedout);
    $smarty->assign('lockednames', $names);
    $smarty->assign('unlockMax', $unlockMax);
    $smarty->display('unlock.tpl');

  } else {
    NSSError($smarty->getConfigVariable('ErrorAdminOnly'),"Administrators only");
    $smarty->display('error.tpl');
  }
} else {
  NSSError($smarty->getConfigVariable('ErrorAdminOnly'),"Administrators only");
  $smarty->display('error.tpl');
}

?>
