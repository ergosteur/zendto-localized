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
require_once(NSSDROPBOX_LIB_DIR."Verify.php");

if ( $theDropbox = new NSSDropbox($NSSDROPBOX_PREFS) ) {
  
  $theDropbox->SetupPage();

  if ( $_POST['Action'] == "verify" ) {
    //
    // Posted form data indicates that a dropoff form was filled-out
    // and submitted.
    //

    // If the request key is provided, then pull it out and look it up.
    // If it's a real one, then just redirect straight out to the pre-
    // populated New Dropoff page by simulating them clicking the link
    // in the email they will receive.

    if ( $_POST['req'] != '' ) {
      $reqKey = $_POST['req'];
      $reqKey = preg_replace('/[^a-zA-Z0-9]/', '', $reqKey);
      $reqKey = strtolower(substr($reqKey, 0, 12)); // Get 1st 3 words
      $recordlist = $theDropbox->database->DBReadReqData($reqKey);
      if ( $recordlist && count($recordlist) ) {
        // Key exists in database, so use it.
        Header( "HTTP/1.1 302 Moved Temporarily" );
        Header( 'Location: '.$NSSDROPBOX_URL.'req.php?req='.$reqKey );
        exit(0);
      } else {
        if ( ! $theDropbox->authorizedUser() ) {
          NSSError($smarty->getConfigVariable('ErrorRequestUsed'),"Verify error");
        } else {
          NSSError($smarty->getConfigVariable('ErrorRequestUsedLogin'),"Request Code error");
        }
      }
    }


    //
    // If posted form data is around, creating a new dropoff instance
    // creates a new dropoff using said form data.
    //

    if ( ! $theDropbox->authorizedUser() ) {
      $reCaptchaPrivateKey = $theDropbox->recaptchaPrivateKey();
      if ($reCaptchaPrivateKey != 'disabled') {
        $resp = recaptcha_check_answer ($reCaptchaPrivateKey,
                                        $_SERVER["REMOTE_ADDR"],
                                        $_POST["recaptcha_challenge_field"],
                                        $_POST["recaptcha_response_field"]);
      }

      if (($reCaptchaPrivateKey == 'disabled' || $resp->is_valid) && ( $theVerify = new Verify($theDropbox) )) {
        // They passed the Captcha so send them on their way if at all possible!
        if ($theVerify->formInitError() != "") {
          NSSError($theVerify->formInitError(),"Verify error");
          $smarty->display('error.tpl');
        } else {
          if (! $theVerify->sendVerifyEmail()) {
            NSSError("Sending the verification email failed.","Email error");
          }
          $smarty->assign('autoHome', TRUE);
          $smarty->display('verify_sent.tpl');
        }
        exit;
      }
      // If they reached here, they failed the Captcha test
      $smarty->assign('verifyFailed', TRUE);
    } else {
      // They are an authorised user so don't need a Captcha
      if ( $theVerify = new Verify($theDropbox) ) {
        if ($theVerify->formInitError() != "") {
          NSSError($theVerify->formInitError(),"Verify error");
          $smarty->display('error.tpl');
        } else {
          // The for worked, go for it!
          $theDropbox->SetupPage();

          $authFullName     = $theDropbox->authorizedUserData("displayName");
          $authEmail        = $theDropbox->authorizedUserData("mail");
          $authOrganization = paramPrepare($_POST['senderOrganization']);
          $authOrganization = preg_replace('/[^a-zA-Z0-9\.\-\_\+\"\'\@\/\:\&\, ]/', '', $authOrganization);

          $smarty->assign('senderName', $authFullName);
          $smarty->assign('senderOrg', $authOrganization);
          $smarty->assign('senderEmail', strtolower($authEmail));
          $smarty->assign('recipEmailNum', 1);

          # Generate unique ID required for progress bars status
          $smarty->assign('progress_id', uniqid(""));
          $smarty->assign('useRealProgressBar', $theDropbox->useRealProgressBar());
          $smarty->assign('note','');
          $smarty->assign('maxBytesForFileInt', $theDropbox->maxBytesForFile());
          $smarty->assign('maxBytesForDropoffInt', $theDropbox->maxBytesForDropoff());

          // If we are using a library of files, fill the structures it needs.
          if ($theDropbox->authorizedUser() && $theDropbox->usingLibrary()) {
            $library = $theDropbox->getLibraryDescs();
            $smarty->assign('library', $library);
            $smarty->assign('usingLibrary', ($library==='[]')?FALSE:TRUE);
          } else {
            $smarty->assign('usingLibrary', FALSE);
            $smarty->assign('library', '[]');
          }

          $smarty->display('new_dropoff.tpl');
        }
      } else {
        $smarty->display('error.tpl');
      }
      exit;
    }
  }

  //
  // We need to present the dropoff sender form.  This page will include some
  // JavaScript that does basic checking of the form prior to submission
  // as well as the code to handle the attachment of multiple files.
  // After all that, we start the page body and write-out the HTML for
  // the form.
  //
  // If the user is authenticated then some of the fields will be
  // already-filled-in (sender name and email).
  //

  //if ( $_POST['Action'] == "verify" ) {
  //  // We wouldn't be here if they hadn't failed
  //  $smarty->assign('verifyFailed', TRUE);
  //}

  //$smarty->assign('senderName', ($theDropbox->authorizedUser() ? $theDropbox->authorizedUserData("displayName") : htmlentities(stripslashes($_POST['senderName']))));
  //$smarty->assign('senderOrg', ($theDropbox->authorizedUser() ? $theDropbox->authorizedUserData("organization") : htmlentities(stripslashes($_POST['senderOrganization']))));
  //$smarty->assign('senderEmail', ($theDropbox->authorizedUser() ? strtolower($theDropbox->authorizedUserData("mail")) : htmlentities(stripslashes($_POST['senderEmail']))));
  $smarty->assign('senderName', ($theDropbox->authorizedUser() ? $theDropbox->authorizedUserData("displayName") : (isset($_POST['senderName'])?htmlentities(paramPrepare($_POST['senderName'])):NULL)));
  $smarty->assign('senderOrg', ($theDropbox->authorizedUser() ? $theDropbox->authorizedUserData("organization") : (isset($_POST['senderOrganization'])?htmlentities(paramPrepare($_POST['senderOrganization'])):NULL)));
  $smarty->assign('senderEmail', ($theDropbox->authorizedUser() ? strtolower($theDropbox->authorizedUserData("mail")) : (isset($_POST['senderEmail'])?htmlentities(paramPrepare($_POST['senderEmail'])):NULL)));

  if ( ! $theDropbox->authorizedUser() ) {
    $reCaptchaPublicKey= $theDropbox->recaptchaPublicKey();
    if ($reCaptchaPublicKey == 'disabled') {
      $smarty->assign('recaptchaDisabled', TRUE);
    } else {
      $smarty->assign('recaptchaHTML', recaptcha_get_html($reCaptchaPublicKey,"",$_SERVER['HTTPS']));
    }
  }
  $smarty->display('verify.tpl');
}

?>
