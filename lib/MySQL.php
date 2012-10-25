<?php
//
// ZendTo
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
// This is the Sql class for SQLite
//

Class Sql {

public $database = NULL;
// never used: public $MySQLrecipientQuery = NULL;
public $_newDatabase = FALSE;

public function __construct( $prefs, $aDropbox ) {
  $this->DBConnect($prefs);
  // never used: $this->MySQLrecipientQuery = $prefs['MySQLrecipientQuery'];
}  

//
// Database functions that work on NSSDropbox objects
//

private function DBConnect ( $prefs ) {
  $this->database = new mysqli($prefs['MySQLhost'],
                               $prefs['MySQLuser'],
                               $prefs['MySQLpassword'],
                               $prefs['MySQLdb']);
  if ( ! $this->database ) {
    NSSError("Could not open MySQL database on ".$prefs['MySQLhost'],"Database Error");
    return FALSE;
  }
  // Want to auto-commit except when I'm manually doing a transaction,
  // so don't set this after all.
  //// Switch off auto-commit and do transactions manually
  //mysqli_autocommit($this->database, FALSE);
  return TRUE;
}

public function DBCreateReq() {
  return TRUE;
}

// Needs to be public for people upgrading from Dropbox 2
public function DBCreateAuth() {
  return TRUE;
}

// Needs to be public for people upgrading from earlier versions
public function DBCreateUser() {
  return TRUE;
}

// Needs to be public for people upgrading from earlier versions
public function DBCreateRegexps() {
  return TRUE;
}

// Needs to be public for people upgrading from earlier versions
public function DBCreateLoginlog() {
  return TRUE;
}

// Needs to be public for people upgrading from earlier versions
public function DBCreateLibraryDesc() {
  return TRUE;
}

public function DBAddLoginlog($user) {
  $query = sprintf("INSERT INTO loginlog
                    (username, created)
                    VALUES
                    ('%s','%u')",
                    $this->database->real_escape_string(strtolower($user)),
                    time());
  if (!$this->database->query($query)) {
    return "Failed to add login record";
  }
  return '';
}

public function DBDeleteLoginlog($user) {
  if ($user == "") {
    $query = "DELETE FROM loginlog";
  } else {
    $query = sprintf("DELETE FROM loginlog WHERE username = '%s'",
                     $this->database->real_escape_string(strtolower($user)));
  }
  if ( !$this->database->query($query) ) {
    return "Failed to delete login records";
  }
  return '';
}

public function DBLoginlogLength($user, $since) {
  $query = sprintf("SELECT count(*) FROM loginlog
                    WHERE username = '%s' AND created > '%u'",
                   $this->database->real_escape_string(strtolower($user)),
                   $since);
  $res = $this->database->query($query);
  $line = $res->fetch_array(MYSQLI_NUM);
  return $line[0]; // Return the 1st field of the 1st line of the result
}

public function DBLoginlogAll($since) {
  $res = $this->database->query(
             sprintf("SELECT * FROM loginlog WHERE created > '%u'",
                     $since));
  $i = 0;
  $qResult = array();
  while ($line = $res->fetch_array()) {
    $qResult[$i++] = $line;
  }
  return $qResult;
}


// Delete the old regexps of this type and add the new ones
public function DBOverwriteRegexps($type, &$regexplist) {

  $now = time();

  if (!$this->DBStartTran()) {
    return "Failed to BEGIN transaction block while updating regexps";
  }

  // Delete the old ones
  if (!$this->database->query(
              sprintf("DELETE FROM regexps WHERE type = %d", $type))) {
    if (!$this->DBRollbackTran()) {
      return "Failed to ROLLBACK after aborting deletion of old regexps";
    }
    return "Failed to delete old regexps";
  }

  // Add the new ones
  foreach ($regexplist as $re) {
    $query = sprintf("INSERT INTO regexps
                      (type, re, created)
                      VALUES
                      (%d,'%s','%u')",
                      $type,
                      $this->database->real_escape_string($re),
                      $now);
    if (!$this->database->query($query)) {
      if (!$this->DBRollbackTran()) {
        return "Failed to ROLLBACK after aborting addition of regexp";
      }
      return "Failed to add regexp";
    }
  }

  // Yay! Success!
  $this->DBCommitTran();
  return '';
}

// List all the regexps matching the given type number
public function DBReadRegexps($type) {
  $res = $this->database->query(
            sprintf("SELECT re,created FROM regexps WHERE type = %d",
                    $type));
  $i = 0;
  $qResult = array();
  while ($line = $res->fetch_array()) {
    $qResult[$i++] = $line;
  }
  return $qResult;
}


public function DBReadLocalUser( $username ) {
  $res = $this->database->query(
            sprintf("SELECT * FROM usertable WHERE username = '%s'",
                    $this->database->real_escape_string($username)));
  $i = 0;
  $qResult = array();
  while ($line = $res->fetch_array()) {
    $qResult[$i++] = $line;
  }
  return $qResult;
}

// Add a new user to the local authentication table.
// Returns an error string, or '' on success.
// Checks to ensure user does not exist first!
public function DBAddLocalUser( $username, $password, $email, $name, $org, $quota ) {
  if ($this->DBReadLocalUser($username)) {
    return "Aborting adding user $username as that user already exists";
  }

  if (!$this->DBStartTran()) {
    return "Failed to BEGIN transaction block while adding user $username";
  }

  // Use username as a default value for real name in case it's not set
  if ($name == '') {
    $name = $username;
  }

  if (preg_match('/^[yYtT1]/', MYZENDTO)) {
    $query = sprintf("INSERT INTO usertable
                    (username, password, mail, displayname, organization, quota)
                    VALUES
                    ('%s','%s','%s','%s','%s',%f)",
                    $this->database->real_escape_string($username),
                    md5($password),
                    $this->database->real_escape_string($email),
                    $this->database->real_escape_string($name),
                    $this->database->real_escape_string($org),
                    $quota);
  } else {
    $query = sprintf("INSERT INTO usertable
                    (username, password, mail, displayname, organization)
                    VALUES
                    ('%s','%s','%s','%s','%s')",
                    $this->database->real_escape_string($username),
                    md5($password),
                    $this->database->real_escape_string($email),
                    $this->database->real_escape_string($name),
                    $this->database->real_escape_string($org));
  }

  if (!$this->database->query($query)) {
    if (!$this->DBRollbackTran()) {
      return "Failed to ROLLBACK after aborting addition of user $username";
    }
    return "Failed to add user $username";
  }

  // Yay! Success!
  $this->DBCommitTran();
  return '';
}

// Delete a user from the local authentication table.
// Returns '' on success.
public function DBDeleteLocalUser ( $username ) {
  if (!$this->DBReadLocalUser($username)) {
    return "Aborting deleting user $username as that user does not exist";
  }

  $this->database->query(
    sprintf("DELETE FROM usertable WHERE username = '%s'",
            $this->database->real_escape_string($username)));
  return '';
}

// Update an existing user's password.
// Returns '' on success.
public function DBUpdatePasswordLocalUser ( $username, $password ) {
  if (!$this->DBReadLocalUser($username)) {
    return "Aborting updating user $username as that user does not exist";
  }

  $query = sprintf("UPDATE usertable
                    SET password='%s'
                    WHERE username='%s'",
                    md5($password),
                    $this->database->real_escape_string($username));
  if (!$this->database->query($query)) {
    return "Failed to update password for user $username";
  }
  return '';
}

public function DBUserQuota( $username ) {
  global $NSSDROPBOX_PREFS;
  $res = $this->database->query("SELECT quota FROM usertable WHERE username = '".$this->database->real_escape_string($username)."'");
  $line = $res->fetch_array(MYSQLI_NUM);
//printf("line is $line\nline0 is ".$line[0]."\nline00 is ".$line[0][0]."\n");
  $quota = $line[0];
  return ($quota >= 1) ? $quota : $NSSDROPBOX_PREFS['defaultMyZendToQuota'];
}

public function DBRemainingQuota ( $username ) {
  $res = $this->database->query(
            sprintf("SELECT SUM(lengthInBytes) AS usedQuotaInBytes FROM dropoff LEFT JOIN file ON dropoff.rowID=file.dID WHERE dropoff.authorizeduser='%s'",
              $this->database->real_escape_string($username)
              )
            );
  $line = $res->fetch_array(MYSQLI_NUM);
  return $this->DBUserQuota($username) - $line[0];
}

public function DBUpdateQuotaLocalUser ( $username, $quota ) {
  if (!$this->DBReadLocalUser($username)) {
    return "Aborting updating user $username as that user does not exist";
  }

  $query = sprintf("UPDATE usertable
                    SET quota=%f
                    WHERE username='%s'",
                    $quota,
                    $this->database->real_escape_string($username));
  if (!$this->database->query($query)) {
    return "Failed to update quota for user $username";
  }
  return '';
}

public function DBListLocalUsers () {
  $res = $this->database->query("SELECT * FROM usertable ORDER BY username");
  $i = 0;
  $extant = array();
  while ($line=$res->fetch_array()) {
    $extant[$i++] = $line;
  }
  return $extant;
}


public function DBListClaims ( $claimID, &$extant ) {
  $res = $this->database->query("SELECT * FROM dropoff WHERE claimID = '".$this->database->real_escape_string($claimID)."'");
  $i = 0;
  $extant = array();
  while ($line=$res->fetch_array()) {
    $extant[$i++] = $line;
  }
  return $extant;
}

public function DBWriteReqData( $dropbox, $hash, $srcname, $srcemail, $srcorg, $destname, $destemail, $note, $subject, $expiry ) {
    if ( ! $this->DBStartTran() ) {
      $dropbox->writeToLog("failed to BEGIN transaction block while adding req for $srcemail");
      return '';
    }
    $query = sprintf("INSERT INTO reqtable
                      (Auth,SrcName,SrcEmail,SrcOrg,DestName,DestEmail,Note,Subject,Expiry)
                      VALUES
                      ('%s','%s','%s','%s','%s','%s','%s','%s',%d)",
                     $this->database->real_escape_string($hash),
                     $this->database->real_escape_string($srcname),
                     $this->database->real_escape_string($srcemail),
                     $this->database->real_escape_string($srcorg),
                     $this->database->real_escape_string($destname),
                     $this->database->real_escape_string($destemail),
                     $this->database->real_escape_string($note),
                     $this->database->real_escape_string($subject),
                     $expiry);
    if ( ! $this->database->query($query) ) {
      //  Exit gracefully -- dump database changes
      $dropbox->writeToLog("error while adding $hash to reqtable");
      if ( ! $this->DBRollbackTran() ) {
        $dropbox->writeToLog("failed to ROLLBACK after botched addition of $hash to reqtable");
      }
      return '';
    }
    $this->DBCommitTran();
    return $hash;
}

public function DBReadReqData( $hash ) {
    $res = $this->database->query(
                  sprintf("SELECT * FROM reqtable WHERE Auth = '%s'",
                          $this->database->real_escape_string($hash)));
    $i = 0;
    $recordlist = array();
    while ($line=$res->fetch_array()) {
      $recordlist[$i++] = $line;
    }
    return $recordlist;
}

public function DBDeleteReqData( $authkey ) {
    $this->database->query(
      sprintf("DELETE FROM reqtable WHERE Auth = '%s'", $this->database->real_escape_string($authkey)));
}

public function DBPruneReqData( $old ) {
    $this->database->query(
      sprintf("DELETE FROM reqtable WHERE Expiry < '%d'", $old));
}

public function DBWriteAuthData( $dropbox, $hash, $name, $email, $org, $expiry,
                          $filename, $claimID ) {
    if ( ! $this->DBStartTran() ) {
      $dropbox->writeToLog("failed to BEGIN transaction block while adding $filename to dropoff $claimID");
      return '';
    }
    $query = sprintf("INSERT INTO authtable
                      (Auth,FullName,Email,Organization,Expiry)
                      VALUES
                      ('%s','%s','%s','%s',%d)",
                     $this->database->real_escape_string($hash),
                     $this->database->real_escape_string($name),
                     $this->database->real_escape_string($email),
                     $this->database->real_escape_string($org),
                     $expiry);
    if ( ! $this->database->query($query) ) {
      //  Exit gracefully -- dump database changes
      $dropbox->writeToLog("error while adding $hash to authtable");
      if ( ! $this->DBRollbackTran() ) {
        $dropbox->writeToLog("failed to ROLLBACK after botched addition of $hash to authtable");
      }
      return '';
    }
    $this->DBCommitTran();
    return $hash;
}

public function DBReadAuthData( $authkey ) {
    $res = $this->database->query(
                  sprintf("SELECT * FROM authtable WHERE Auth = '%s'",
                          $this->database->real_escape_string($authkey)));
    $i = 0;
    $recordlist = array();
    while ($line=$res->fetch_array()) {
      $recordlist[$i++] = $line;
    }
    return $recordlist;
}

public function DBDeleteAuthData( $authkey ) {
    $this->database->query(
      sprintf("DELETE FROM authtable WHERE Auth = '%s'", $this->database->real_escape_string($authkey)));
}

public function DBPruneAuthData( $old ) {
    $this->database->query(
      sprintf("DELETE FROM authtable WHERE Expiry < '%d'", $old));
}

public function DBDropoffsForMe( $targetEmail ) {
  $res = $this->database->query(
           "SELECT d.rowID,d.* FROM dropoff d,recipient r WHERE d.rowID = r.dID AND r.recipEmail = '".$this->database->real_escape_string($targetEmail)."' ORDER BY d.created DESC");
  $i = 0;
  $qResult = array();
  // JKF 20120322 Added isset() test to catch a PHP fatal error
  if (isset($res)) {
    while ($line = $res->fetch_array()) {
      $qResult[$i++] = $line;
    }
  }
  return $qResult;
}

public function DBDropoffsFromMe( $authSender, $targetEmail ) {
  $res = $this->database->query(
           sprintf("SELECT * FROM dropoff WHERE authorizedUser = '".$this->database->real_escape_string($authSender)."' %s ORDER BY created DESC",
               ( $targetEmail ? ("OR senderEmail = '".$this->database->real_escape_string($targetEmail)."'") : "")
             ));
  $i = 0;
  $qResult = array();
  while ($line = $res->fetch_array()) {
    $qResult[$i++] = $line;
  }
  return $qResult;
}

public function DBDropoffsTooOld( $targetDate ) {
  $res = $this->database->query(
           "SELECT * FROM dropoff WHERE created < '$targetDate' ORDER BY created");
  $i = 0;
  $qResult = array();
  while ($line = $res->fetch_array()) {
    $qResult[$i++] = $line;
  }
  return $qResult;
}

public function DBDropoffsToday( $targetDate ) {
  $res = $this->database->query(
           "SELECT * FROM dropoff WHERE created >= '$targetDate' ORDER BY created");
  $i = 0;
  $qResult = array();
  while ($line = $res->fetch_array()) {
    $qResult[$i++] = $line;
  }
  return $qResult;
}

public function DBDropoffsAll() {
  $res = $this->database->query(
           "SELECT * FROM dropoff ORDER BY created DESC");
  $i = 0;
  $qResult = array();
  while ($line = $res->fetch_array()) {
    $qResult[$i++] = $line;
  }
  return $qResult;
}

public function DBDropoffsAllRev() {
  $res = $this->database->query(
           "SELECT * FROM dropoff ORDER BY created");
  $i = 0;
  $qResult = array();
  while ($line = $res->fetch_array()) {
    $qResult[$i++] = $line;
  }
  return $qResult;
}

function DBFilesByDropoffID( $dropoffID ) {
  $res = $this->database->query(
           sprintf("SELECT * FROM file WHERE dID = %d ORDER by basename",
             $this->database->real_escape_string($dropoffID)));
  $i = 0;
  $qResult = array();
  while ($line = $res->fetch_array()) {
    $qResult[$i++] = $line;
  }
  return $qResult;
}

public function DBDataForRRD( $set ) {
  $res = $this->database->query(
           sprintf("SELECT COUNT(*),SUM(lengthInBytes) FROM file WHERE dID IN (%s)", $set));
  $line = $res->fetch_row();
  return $line;
}

public function DBBytesOfDropoff( $dropoffID ) {
  $res = $this->database->query(
           sprintf("SELECT SUM(lengthInBytes) FROM file WHERE dID = %d",
             $this->database->real_escape_string($dropoffID)));
  $line = $res->fetch_array(MYSQLI_NUM);
  return $line[0];
  //$i = 0;
  //$recordlist = array();
  //while ($line=$res->fetch_array()) {
  //  $recordlist[$i++] = $line;
  //}
  //return $recordlist;
}

public function DBAddFile2( $d, $dropoffID, $tmpname, $filename,
                    $contentLen, $mimeType, $description, $claimID ) {
  if ( ! $this->DBStartTran() ) {
    $d->writeToLog("failed to BEGIN transaction block while adding $filename to dropoff $claimID");
    return false;
  }

  $query = sprintf("INSERT INTO file (dID,tmpname,basename,lengthInBytes,mimeType,description) VALUES (%d,'%s','%s',%.0f,'%s','%s')",
             $dropoffID,
             $this->database->real_escape_string(basename($tmpname)),
             $this->database->real_escape_string(paramPrepare($filename)),
             $contentLen,
             $this->database->real_escape_string($mimeType),
             // 20120518 Not sure if this paramPrepare should be here
             $this->database->real_escape_string(paramPrepare($description))
          );
  if ( ! $this->database->query($query) ) {
    //  Exit gracefully -- dump database changes and remove the dropoff
    //  directory:
    $d->writeToLog("error while adding $filename to dropoff $claimID");
    if ( ! $this->DBRollbackTran() ) {
      $d->writeToLog("failed to ROLLBACK after botched addition of $filename to dropoff $claimID");
    }
    return false;
  }
  return $this->DBCommitTran();
}

public function DBFileList( $dropoffID, $fileID ) {
  $res = $this->database->query(
           sprintf("SELECT * FROM file WHERE dID = %d AND rowID = %d",
             $this->database->real_escape_string($dropoffID),
             $this->database->real_escape_string($fileID)
           ));
  $i = 0;
  $qResult = array();
  while ($line = $res->fetch_array()) {
    $qResult[$i++] = $line;
  }
  return $qResult;
}

public function DBExtantPickups( $dropoffID ) {
  $res = $this->database->query(
           sprintf("SELECT count(*) FROM pickup WHERE dID = %d",
             $this->database->real_escape_string($dropoffID)
           ));
  $i = 0;
  $qResult = array();
  while ($line = $res->fetch_array()) {
    $qResult[$i++] = $line;
  }
  return $qResult;
}

public function DBAddToPickupLog( $d, $dropoffID, $authorizedUser, $emailAddr,
                                  $remoteAddr, $timeStamp, $claimID ) {
  $query = sprintf("INSERT INTO pickup (dID,authorizedUser,emailAddr,recipientIP,pickupTimestamp) VALUES (%d,'%s','%s','%s','%s')",
             $this->database->real_escape_string($dropoffID),
             $this->database->real_escape_string($authorizedUser),
             $this->database->real_escape_string($emailAddr),
             $this->database->real_escape_string($remoteAddr),
             $this->database->real_escape_string($timeStamp)
           );
  if ( ! $this->database->query($query) ) {
    $d->writeToLog("unabled to add pickup record for claimID ".$claimID);
  }
}

public function DBRemoveDropoff( $d, $dropoffID, $claimID ) {
      if ( $this->DBStartTran() ) {
        $query = sprintf("DELETE FROM pickup WHERE dID = %d",$this->database->real_escape_string($dropoffID));
        if ( ! $this->database->query($query) ) {
          if ( $doLogEntries ) {
            $d->writeToLog("error in '$query'");
          }
          $this->DBRollbackTran();
          return FALSE;
        }

        $query = sprintf("DELETE FROM file WHERE dID = %d",$this->database->real_escape_string($dropoffID));
        if ( ! $this->database->query($query) ) {
          if ( $doLogEntries ) {
            $d->writeToLog("error in '$query'");
          }
          $this->DBRollbackTran();
          return FALSE;
        }

        $query = sprintf("DELETE FROM recipient WHERE dID = %d",$this->database->real_escape_string($dropoffID));
        if ( ! $this->database->query($query) ) {
          if ( $doLogEntries ) {
            $d->writeToLog("error in '$query'");
          }
          $this->DBRollbackTran();
          return FALSE;
        }

        $query = sprintf("DELETE FROM dropoff WHERE claimID = '%s'",$this->database->real_escape_string($claimID));
        if ( ! $this->database->query($query) ) {
          if ( $doLogEntries ) {
            $d->writeToLog("error in '$query'");
          }
          $this->DBRollbackTran();
          return FALSE;
        }

        if ( ! $this->DBCommitTran() ) {
          if ( $doLogEntries ) {
            $d->writeToLog("error trying to COMMIT removal of claimID ".$claimID);
          }
          $this->DBRollbackTran();
          return FALSE;
        }
        return TRUE;
      }
      return FALSE;
}

public function DBFilesForDropoff ( $dropoffID ) {
  $res = $this->database->query(
           sprintf("SELECT * FROM file WHERE dID = %d ORDER by basename",
             $this->database->real_escape_string($dropoffID)
           ));
  $i = 0;
  $qResult = array();
  while ($line = $res->fetch_array()) {
    $qResult[$i++] = $line;
  }
  return $qResult;
}

public function DBPickupsForDropoff ( $dropoffID ) {
  $res = $this->database->query(
           sprintf("SELECT * FROM pickup WHERE dID = %d ORDER by pickupTimestamp",
             $this->database->real_escape_string($dropoffID)
           ));
  $i = 0;
  $qResult = array();
  while ($line = $res->fetch_array()) {
    $qResult[$i++] = $line;
  }
  return $qResult;
}

public function DBDropoffsForClaimID ( $claimID ) {
  $res = $this->database->query(
           sprintf("SELECT * FROM dropoff WHERE claimID = '%s'",
             $this->database->real_escape_string($claimID)
           ));
  $i = 0;
  $qResult = array();
  while ($line = $res->fetch_array()) {
    $qResult[$i++] = $line;
  }
  return $qResult;
}

public function DBRecipientsForDropoff ( $rowID ) {
  $res = $this->database->query(
           sprintf("SELECT recipName,recipEmail FROM recipient WHERE dID = %d",
             $this->database->real_escape_string($rowID)
           ));
  $i = 0;
  $qResult = array();
  while ($line = $res->fetch_array()) {
    $qResult[$i++] = $line;
  }
  return $qResult;
}

public function DBStartTran () {
  return $this->database->query('BEGIN');
}

public function DBRollbackTran () {
  return $this->database->query('ROLLBACK');
}

public function DBCommitTran () {
  return $this->database->query('COMMIT');
}

public function DBAddDropoff ( $claimID, $claimPasscode, $authorizedUser,
                               $senderName, $senderOrganization, $senderEmail,
                               $remoteIP, $confirmDelivery, $now, $note ) {
  $query = sprintf("INSERT INTO dropoff
                    (claimID,claimPasscode,authorizedUser,senderName,
                     senderOrganization,senderEmail,senderIP,
                     confirmDelivery,created,note)
                    VALUES
                    ('%s','%s','%s','%s', '%s','%s','%s', %d,'%s','%s')",
             $this->database->real_escape_string($claimID),
             $this->database->real_escape_string($claimPasscode),
             $this->database->real_escape_string($authorizedUser),
             $this->database->real_escape_string($senderName),
             $this->database->real_escape_string($senderOrganization),
             $this->database->real_escape_string($senderEmail),
             $this->database->real_escape_string($remoteIP),
             ( $confirmDelivery ? 1 : 0 ),
             $now,
             $this->database->real_escape_string($note)
           );
  if ( $this->database->query($query) ) {
    return $this->database->insert_id;
  }
  return FALSE;
}

public function DBAddRecipients ( $recipients, $dropoffID ) {
  foreach ( $recipients as $recipient ) {
    $query = sprintf("INSERT INTO recipient
                      (dID,recipName,recipEmail)
                      VALUES
                      (%d,'%s','%s')",
               $this->database->real_escape_string($dropoffID),
               $this->database->real_escape_string($recipient[0]),
               $this->database->real_escape_string($recipient[1]));
    if ( ! $this->database->query($query) ) {
      return FALSE;
    }
  }
  return TRUE;
}

public function DBAddFile1 ( $dropoffID, $tmpname, $basename, $bytes,
                            $mimeType, $description ) {
  $query = sprintf("INSERT INTO file
                    (dID,tmpname,basename,lengthInBytes,mimeType,description)
                    VALUES
                    (%d,'%s','%s',%.0f,'%s','%s')",
             $dropoffID,
             $this->database->real_escape_string($tmpname),
             $this->database->real_escape_string($basename),
             $bytes,
             $this->database->real_escape_string($mimeType),
             $this->database->real_escape_string($description));
  if ( ! $this->database->query($query) ) {
    return FALSE;
  }
  return TRUE;
}

// Get a mapping from library filename to description
public function DBGetLibraryDescs () {
  $res = $this->database->query(
           "SELECT filename,description FROM librarydesc");
  $i = 0;
  $qResult = array();
  while ($line = $res->fetch_array()) {
    // Only set it if it's not already set and we're not setting it to blank
    if (!isset($qResult[$line[0]]) && isset($line[1])) {
      $qResult[trim($line[0])] = trim($line[1]);
    }
    // $qResult[$line[0]] = $line[1];
  }
  return $qResult;
}

// Update the mapping from filename to description
public function DBUpdateLibraryDescription ( $file, $desc ) {
  $file = trim($file);
  $desc = trim($desc);
  $res = $this->database->query(
             sprintf("SELECT COUNT(*) FROM librarydesc WHERE filename='%s'",
               $this->database->real_escape_string($file)));
  $line = $res->fetch_array();

  if ($line[0]>=1) {
    // Entry for this filename already exists, so UPDATE
    $query = sprintf("UPDATE librarydesc SET description='%s' WHERE filename='%s'",
               $this->database->real_escape_string($desc),
               $this->database->real_escape_string($file));
    $query = $this->database->query($query);
  } else {
    // Entry for this filename does not exist, so INSERT
    $query = sprintf("INSERT INTO librarydesc (filename,description) VALUES ('%s','%s')",
               $this->database->real_escape_string($file),
               $this->database->real_escape_string($desc));
    $query = $this->database->query($query);
  }
}

}

?>
