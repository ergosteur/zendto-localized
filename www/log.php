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
require_once(NSSDROPBOX_LIB_DIR."Smartyconf.php");
require_once(NSSDROPBOX_LIB_DIR."NSSDropbox.php");

$LogLength = 1000; // How many lines of log to show

if ( $theDropbox = new NSSDropbox($NSSDROPBOX_PREFS) ) {
  
  $theDropbox->SetupPage();

  if ( $theDropbox->authorizedUser() && $theDropbox->authorizedUserData('grantAdminPriv') ) {
    // Just get the last 1000 lines of the log file, should be enough
    $lines = array_reverse(array_slice(file($NSSDROPBOX_PREFS['logFilePath']), -$LogLength));
    $out = '';
    if ($lines) {
      foreach ($lines as $line) {
        // Encode all special HTML characters so it's safe to view
        $out = $out . htmlentities($line, ENT_QUOTES);
      }
      $smarty->assign('log', $out);
      $smarty->assign('count', $LogLength);
      $smarty->display('log.tpl');

    } else {
      NSSError("Failed to read logfile. Check web server can read it.","File Permissions Error");
      $smarty->display('error.tpl');
    }
  } else {
    NSSError($smarty->getConfigVariable('ErrorAdminOnly'),"Administrators only");
    $smarty->display('error.tpl');
  }
} else {
  NSSError($smarty->getConfigVariable('ErrorAdminOnly'),"Administrators only");
  $smarty->display('error.tpl');
}

?>
