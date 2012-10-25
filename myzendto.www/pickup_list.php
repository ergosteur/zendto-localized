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

if ( $theDropbox = new NSSDropbox($NSSDROPBOX_PREFS,TRUE) ) {
  //
  // This page handles the listing of an authenticated user's 
  // dropoffs.  If the user is NOT authenticated, then an error
  // is presented.
  //

  $theDropbox->SetupPage();

  // Read the sort order from the page
  $cookieName = $theDropbox->cookieName() . 'MyDropoffsSortOrder';
  $sortOrder = $_POST['sortOrder'];
  if (!$sortOrder) {
    $sortOrder = $_COOKIE[$cookieName];
  }
  if (!$sortOrder) {
    $sortOrder = "ffile";
  }
  setcookie($cookieName, $sortOrder, time()+120*24*60*60, // long time!
            '/', '', FALSE);

  if ( $theDropbox->authorizedUser() ) {
    //
    // Returns an array of all NSSDropoff instances belonging to
    // this user.
    //
    $allDropoffs = NSSDropoff::dropoffsForCurrentUser($theDropbox);
    if ($sortOrder == "rdate" && count($allDropoffs) > 0) {
      $allDropoffs = array_reverse($allDropoffs);
    }
    //
    // Start the web page and add some Javascript to automatically
    // fill-in and submit a pickup form when a dropoff on the page
    // is clicked.
    //
    $iMax = count($allDropoffs);
    $smarty->assign('countDropoffs', $iMax);
    $smarty->assign('remainingQuota', NSSFormattedMemSize($theDropbox->database->DBRemainingQuota($theDropbox->authorizedUser())));
    
    if ( $allDropoffs && $iMax>0 ) {
      $outputDropoffs = array();
      $i = 0;
      $dropoffsbyFile = array();
      $dropoffsbyID   = array();
      $dropoffsbySize = array();
      foreach($allDropoffs as $dropoff) {
        $outputDropoffs[$i] = array();
        $outputDropoffs[$i]['claimID'] = $dropoff->claimID();
        $outputDropoffs[$i]['claimPasscode'] = $dropoff->claimPasscode();
        $outputDropoffs[$i]['senderName'] = $dropoff->senderName();
        $outputDropoffs[$i]['senderEmail'] = $dropoff->senderEmail();
        $outputDropoffs[$i]['createdDate'] = timeForDate($dropoff->created());
        $outputDropoffs[$i]['formattedBytes'] = $dropoff->formattedBytes();
        // Customer doesn't want note, but list of filenames instead
        // $note = $dropoff->note();
        // $note = preg_replace('/\s+/',' ',$note);
        $files = $dropoff->dropbox()->database->DBFilesByDropoffID($dropoff->dropoffID());
        $filenames = array();
        foreach ($files as $f) {
          $filenames[] = $f['basename'];
        }
        $note = implode(', ', $filenames);
        // If note is too long, take first 40 + '...' + last 40 characters
        $notelen = strlen($note);
        if ($notelen>80) {
          $firsthalf = $notelen/2 - 2;
          if ($firsthalf>40) {
            $firsthalf = 40;
          }
          $lasthalf = $notelen/2 + 2;
          if ($lasthalf>40) {
            $lasthalf = 40 + 2;
          }
          $note = substr($note, 0, $firsthalf) . '...' .
                  substr($note, $notelen-$lasthalf);
        }
        $outputDropoffs[$i]['note'] = $note;
        // Store the 2 indexes needed to sort by filename instead of date
        $dropoffsbyID[$dropoff->claimID()] = $outputDropoffs[$i];
        $dropoffsbySize[floor($dropoff->bytes())] = $outputDropoffs[$i];
        $filesbyID[$dropoff->claimID()] = $note; // We will sort by this
        $i++;
      }
      if (preg_match('/file/', $sortOrder)) {
        // Sorting by file, so do all the data re-arranging
        $sortedDropoffs = array();
        if ($sortOrder == 'rfile') {
          arsort($filesbyID); // This will sort the hash by key i.e. claimID
        } else {
          asort($filesbyID); // This will sort the hash by key i.e. claimID
        }
        foreach ($filesbyID as $claimID => $filenames) {
          if ($claimID) { // Bit of defensive error trapping
            $sortedDropoffs[] = $dropoffsbyID[$claimID];
          }
        }
        $smarty->assignByRef('dropoffs', $sortedDropoffs);
      } else {
        // Already sorted by date, do nothing
        $smarty->assignByRef('dropoffs', $outputDropoffs);
      }
    }
  } else {
    NSSError("This feature is only available to users who have logged-in to the system.","Access Denied");
  }

  // This lets us maintain state in the form.
  // If we are coming from a different form then use a cookie.
  $smarty->assign('sortOrder', $sortOrder);

  $smarty->display('pickup_list.tpl');
}

?>
