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
require_once(NSSDROPBOX_LIB_DIR."NSSDropoff.php");

if ( $theDropbox = new NSSDropbox($NSSDROPBOX_PREFS) ) {
  //
  // This page displays usage graphs for the system.
  //
  $theDropbox->SetupPage();

  if ( $theDropbox->authorizedUser() && $theDropbox->authorizedUserData('grantStatsPriv') ) {
    
    switch ( isset($_GET['period'])?$_GET['period']:NULL ) {
    
      case 'month':
        $period = 30;
        break;
      case '90days':
        $period = 90;
        break;
      case 'year':
        $period = 365;
        break;
      case 'decade':
        $period = 3650;
        break;
      case 'week':
      default:
        $period = 7;
        break;
    }
    $smarty->assign('period', $period);
    
  } else {
    NSSError($smarty->getConfigVariable('ErrorAdminOnly'),"Access Denied");
  }
}

$smarty->display('stats.tpl');

?>
