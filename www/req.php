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
require "recaptchalib.php";
require_once(NSSDROPBOX_LIB_DIR."Smartyconf.php");
require_once(NSSDROPBOX_LIB_DIR."NSSDropoff.php");
require_once(NSSDROPBOX_LIB_DIR."Req.php");

// This gets called with nothing, in which case we present the form,
// else it gets called with GET['req'], in which case we read the DB
// info and present the dropoff form,
// else it gets called with POST['Action']==send, in which case we send
// the email.

if ( $theDropbox = new NSSDropbox($NSSDROPBOX_PREFS) ) {
  
  $srcname = '';
  $srcemail = '';
  $srcorg = '';
  $destname = '';
  $destemail = '';
  $note = '';
  $subject = '';
  $expiry = 0;

  if (isset($_GET['req'])) {
    // They got this link in an email, so...
    // Read the DB info and present the dropoff form
    $authkey = preg_replace('/[^a-zA-Z0-9]/', '', $_GET['req']);
    $authkey = strtolower(substr($authkey, 0, 12)); // Get 1st 3 words

    if ( ! $theDropbox->ReadReqData($authkey, $srcname, $srcemail, $srcorg, $destname, $destemail,
                $note, $subject, $expiry) ) {
      // Error!
      $theDropbox->SetupPage();
      NSSError($smarty->getConfigVariable('ErrorRequestUsed'),"Request Code Used");
      $smarty->display('error.tpl');
      exit;
    }

    if ($expiry < time()) {
        $theDropbox->SetupPage();
        NSSError($smarty->getConfigVariable('ErrorRequestExpired'),"Request Code Expired");
        $smarty->display('error.tpl');
        exit;
    }

    // Present the new_dropoff form
    $theDropbox->SetupPage();
    $smarty->assign('senderName', $destname);
    $smarty->assign('senderEmail', strtolower($destemail));
    $smarty->assign('recipName_1', $srcname);
    $smarty->assign('recipEmail_1', strtolower($srcemail));
    $smarty->assign('note', $note);
    $smarty->assign('maxBytesForFileInt', $theDropbox->maxBytesForFile());
    $smarty->assign('maxBytesForDropoffInt', $theDropbox->maxBytesForDropoff());
    $smarty->assign('recipEmailNum', 1);
    $smarty->assign('reqKey', $authkey);
    // Generate unique ID required for progress bars status
    $smarty->assign('progress_id', uniqid(""));
    $smarty->assign('useRealProgressBar', $theDropbox->useRealProgressBar());
    // And setup the library of files appropriately
    if ($theDropbox->authorizedUser() && $theDropbox->usingLibrary()) {
      $library = $theDropbox->getLibraryDescs();
      $smarty->assign('library', $library);
      $smarty->assign('usingLibrary', ($library==='[]')?FALSE:TRUE);
    } else {
      $smarty->assign('usingLibrary', FALSE);
      $smarty->assign('library', '[]');
    }
    $smarty->display('new_dropoff.tpl');
    exit;
  }

  // They are either trying to submit or display the "New Request" form,
  // so they must be logged in.
  if ( ! $theDropbox->authorizedUser() ) {
    $theDropbox->SetupPage();
    NSSError($smarty->getConfigVariable('ErrorNotLoggedIn'),"Access Denied");
    $smarty->display('error.tpl');
    exit;
  }

  if ($_POST['Action'] == "send") {
    // Read the contents of the form, and send the email of it all
    // Loop through all the email addresses we were given, creating a new
    // Req object for each one. Then piece together the bits of the output
    // we need to make the resulting web page look pretty.
    $emailAddrs = preg_split('/[;, ]+/', paramPrepare(strtolower($_POST['recipEmail'])), NULL, PREG_SPLIT_NO_EMPTY);
    $wordList = array();
    $emailList = array(); // This is the output list, separate for safety
    foreach ($emailAddrs as $re) {
      $req = new Req($theDropbox, $re);
      if ($req->formInitError() != "") {
        $theDropbox->SetupPage();
        NSSError($req->formInitError(),"Request error");
        $smarty->display('error.tpl');
        exit;
      }
      if (! $req->sendReqEmail()) {
        $theDropbox->SetupPage();
        NSSError("Sending the request email failed.","Email error");
        $smarty->display('error.tpl');
        exit;
      }
      $wordList[] = $req->words();
      $emailList[] = $req->recipEmail();
    }

    // Set up the output page
    $theDropbox->SetupPage();
    //$smarty->assign('autoHome', TRUE);
    $smarty->assign('toEmail', implode(', ', $emailList));
    $smarty->assign('reqKey', implode(', ', $wordList));
    //$smarty->assign('reqKey', $req->words());
    $smarty->display('request_sent.tpl');
    exit;
  }

  // It got presented with nothing except a user who should be logged in,
  // so present the form.
  $senderName  = $theDropbox->authorizedUserData("displayName");
  $senderEmail = $theDropbox->authorizedUserData("mail");
  $senderOrg   = $theDropbox->authorizedUserData("organization");

  $theDropbox->SetupPage('req.recipName');

  $smarty->assign('senderName', $senderName);
  $smarty->assign('senderEmail', $senderEmail);
  $smarty->assign('senderOrg', $senderOrg);
  
  $smarty->display('request.tpl');
}

?>
