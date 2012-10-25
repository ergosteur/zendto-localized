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

require_once(NSSDROPBOX_LIB_DIR."NSSDropbox.php");
require_once(NSSDROPBOX_LIB_DIR."NSSUtils.php");
require_once(NSSDROPBOX_LIB_DIR."Timestamp.php");

/*!
  @class Req
*/
class Req {

  //  Instance data:
  private $_dropbox = NULL;
  
  private $_auth;
  private $_words;
  private $_senderName;
  private $_senderEmail;
  private $_senderOrg;
  private $_recipName;
  private $_recipEmail;
  private $_note;
  private $_subject;
  private $_formInitError = NULL;
  
  /*!
    @function __construct
    
    Object constructor.  First of all, if we were passed a query result hash
    in $qResult, then initialize the instance using data from the SQL query.
    Otherwise, we need to look at the disposition of the incoming form data:
    
    * The only GET-type form we do comes from the email notifications we
      send to notify recipients.  So the presence of claimID (and possibly
      claimPasscode) in $_GET means we can init as though the user were
      making a pickup.
    
    * If there a POST-type form and a claimID exists in $_POST, then
      try to initialize using that claimID.
    
    * Otherwise, we need to see if the POST-type form data has an action
      of "dropoff" -- if it does, then attempt to create a ~new~ dropoff
      with $_FILES and $_POST.
    
    A _lot_ of state stuff going on in here; might be ripe for simplification
    in the future.
  */
  public function __construct(
    $aDropbox,
    $recipEmail,
    $qResult = FALSE
  )
  {
    $this->_dropbox = $aDropbox;
    
    if ( ! $qResult ) {
      //  Try to create a new one from form data:
      $this->_formInitError = $this->initWithFormData($recipEmail);
    } else {
      NSSError("This form cannot be called like this, please return to the main menu.");
    }
  }

  /*
    These are all accessors to get the value of all of the dropoff
    parameters.  Note that there are no functions to set these
    parameters' values:  an instance is immutable once it's created!
    
    I won't document each one of them because the names are
    strategically descriptive *grin*
  */
  public function dropbox() { return $this->_dropbox; }
  public function dropoffID() { return $this->_dropoffID; }
  public function authorizedUser() { return $this->_authorizedUser; }
  public function words() { return $this->_words; }
  public function senderName() { return $this->_senderName; }
  public function senderEmail() { return $this->_senderEmail; }
  public function recipName() { return $this->_recipName; }
  public function recipEmail() { return $this->_recipEmail; }
  public function confirmDelivery() { return $this->_confirmDelivery; }
  public function created() { return $this->_created; }
  public function recipients() { return $this->_recipients; }
  public function formInitError() { return $this->_formInitError; }
  

  /*!
    @function initWithFormData
    
    This monster routine examines POST-type form data coming from our verify
    form, validates all of it, and writes a new authtable record.
    
    The validation is done primarily on the email addresses that are involved,
    and all of that is documented inline below.  We also have to be sure that
    the user didn't leave any crucial fields blank.
    
    If any errors occur, this function will return an error string.  But
    if all goes according to plan, then we return NULL!
  */
  private function initWithFormData(
            $recipEmail
  )
  {
    global $NSSDROPBOX_URL;
    
    // They are an authenticated user so try to get their name and email
    // from the authentication system.
    $senderName = $this->_dropbox->authorizedUserData("displayName");
    if ( ! $senderName ) {
      $senderName = paramPrepare($_POST['senderName']);
    }
    $senderEmail = strtolower($this->_dropbox->authorizedUserData("mail"));
    if ( ! $senderEmail ) {
      $senderEmail = paramPrepare($_POST['senderEmail']);
    }
    $senderOrganization = paramPrepare($_POST['senderOrganization']);
    $recipName = paramPrepare($_POST['recipName']);
    # This is now read from a parameter passed to us
    #$recipEmail = stripslashes(strtolower($_POST['recipEmail']));
    // SLASH $note = stripslashes($_POST['note']);
    $note = $_POST['note'];
    $subject = paramPrepare($_POST['subject']);
    
    // Sanitise the data
    $senderName = preg_replace('/[^a-zA-Z0-9\.\-\_\+\"\'\@\/\:\&\, ]/', '', $senderName);
    $senderEmail = preg_replace('/[^a-zA-Z0-9\.\-\_\+\"\'\@\/\:\&\, ]/', '', $senderEmail);
    $senderOrganization = preg_replace('/[^a-zA-Z0-9\.\-\_\+\"\'\@\/\:\&\, ]/', '', $senderOrganization);
    $recipName = preg_replace('/[^a-zA-Z0-9\.\-\_\+\"\'\@\/\:\&\, ]/', '', $recipName);
    $recipEmail = preg_replace('/[^a-zA-Z0-9\.\-\_\+\"\'\@\/\:\&\, ]/', '', $recipEmail);

    if ( ! $senderName ) {
      return "You must specify your name in the form.  Use the back button in your browser to go back and fix this omission before trying again.";
    }
    if ( ! $senderEmail ) {
      return "You must specify your own email address in the form.  Use the back button in your browser to go back and fix this omission before trying again.";
    }
    if ( ! $recipName ) {
      return "You must specify the recipient's name in the form. Use the back button in your browser to go back and fix this omission before trying again.";
    }
    if ( ! $recipEmail ) {
      return "You must specify the recipient's email address in the form. Use the back button in your browser to go back and fix this omission before trying again.";
    }
    if ( ! preg_match($this->_dropbox->validEmailRegexp(),$senderEmail,$emailParts) ) {
      return "Your email address you entered was invalid.  Use the back button in your browser to go back and fix this omission before trying again.";
    }
    $senderEmail = $emailParts[1]."@".$emailParts[2];
    if ( ! preg_match($this->_dropbox->validEmailRegexp(),$recipEmail,$emailParts) ) {
      return "The recipient's email address you entered was invalid.  Use the back button in your browser to go back and fix this omission before trying again.";
    }
    $recipEmail = $emailParts[1]."@".$emailParts[2];
    
    // Check the length of the subject.
    $subjectlength = strlen($subject);
    $maxlen = $this->_dropbox->maxsubjectlength();
    if ($subjectlength>$maxlen) {
      return sprintf($smarty->getConfigVariable('ErrorSubjectTooLong'),
                     $subjectlength, $maxlen);
    }

    // The subject line of the files will be a "Re: +subject"
    $reSubject = trim($subject);
    if (!preg_match('/^Re:/i', $reSubject)) {
      $reSubject = 'Re: ' . $reSubject;
    }

    //  Insert into database:
    $words = $this->_dropbox->WriteReqData($senderName, $senderEmail, $senderOrganization, $recipName, $recipEmail, $note, $reSubject);

    if ( $words == '') {
      return "Database failure writing request information. Please contact your system administrator.";
    } else {
      $this->_words       = $words;
      $this->_auth        = preg_replace('/[^a-zA-Z0-9]/', '', $words);
      $this->_senderName  = $senderName;
      $this->_senderEmail = $senderEmail;
      $this->_senderOrg   = $senderOrg;
      $this->_recipName   = $recipName;
      $this->_recipEmail  = $recipEmail;
      $this->_note        = $note;
      $this->_subject     = $subject;
    }
    return "";
  }

  public function sendReqEmail ()
  {
    global $smarty;
    global $NSSDROPBOX_URL;

    //  Construct the email notification and deliver:
    $smarty->assign('fromName',  $this->_senderName);
    $smarty->assign('fromEmail', $this->_senderEmail);
    $smarty->assign('fromOrg',   $this->_senderOrg);
    $smarty->assign('toName',    $this->_recipName);
    $smarty->assign('toEmail',   $this->_recipEmail);
    $smarty->assign('note',      $this->_note);
    $smarty->assign('subject',   trim($this->_subject));
    $smarty->assign('URL', $NSSDROPBOX_URL.'req.php?req='.$this->_auth);
    $emailSubject = trim($this->_subject);
 
    $success = $this->_dropbox->deliverEmail(
                 $this->_recipEmail,
                 $this->_senderEmail,
                 $emailSubject,
                 $smarty->fetch('request_email.tpl'));
    if ( $success ) {
      $this->_dropbox->writeToLog(sprintf("Dropoff request email delivered successfully from %s to %s",$this->_senderEmail, $this->_recipEmail));
    } else {
      $this->_dropbox->writeToLog(sprintf("Dropoff request email not delivered successfully from %s to %s",$this->_senderEmail, $this->_recipEmail));
      return FALSE;
    }

    // Everything worked and the mail was sent!
    return TRUE;
  }
        
}

?>
