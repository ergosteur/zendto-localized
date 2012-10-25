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
public $_newDatabase = FALSE;

public function __construct( $prefs, $aDropbox ) {
  $this->DBConnect($prefs['SQLiteDatabase'], $aDropbox);
}  

//
// Database functions that work on NSSDropbox objects
//

private function DBConnect ( $sqlitename, $aDropbox ) {
  if ( ! file_exists($sqlitename) ) {
    if ( ! ($this->database = new SQLiteDatabase($sqlitename,0666)) ) {
      NSSError("Could not create the new database.","Database Error");
      return FALSE;
    }
    //  It was a new file, so we need to create tables in the database
    //  right now, too!
    if ( ! $this->DBSetupDatabase($aDropbox) ) {
      NSSError("Could not create the tables in the new database.","Database Error");
      return FALSE;
    }
    //  This was a new database:
    $this->_newDatabase = TRUE;
  } else {
    if ( ! ($this->database = new SQLiteDatabase($sqlitename)) ) {
      NSSError("Could not open the database.","Database Error");
      return FALSE;
    }
    // If the librarydesc table doesn't exist, create it!
    $query = $this->database->query("SELECT name FROM sqlite_master WHERE type='table' and name='librarydesc'"); 
    if ($query->numRows()<1){ 
      /* table does not exist...create it or do something... */ 
      if ( ! $this->DBCreateLibraryDesc() ) {
        $dropbox->writeToLog("Failed to add librarydesc table to database");
        NSSError($errorMsg,"Database Error creating librarydesc table");
        return FALSE;
      }
    }
  }
  return TRUE;
}

// Create the database as we need to do this for SQLite
private function DBSetupDatabase($dropbox) {
    if ( $this->database ) {

      if ( ! $this->DBCreate()) {
        NSSError($errorMsg,"Database Error");
        return FALSE;
      }

      if ( ! $this->DBCreateReq() ) {
        $dropbox->writeToLog("Failed to add reqtable to database");
        NSSError($errorMsg,"Database Error creating reqtable");
        return FALSE;
      }

      if ( ! $this->DBCreateAuth() ) {
        $dropbox->writeToLog("Failed to add authtable to database");
        NSSError($errorMsg,"Database Error creating authtable");
        return FALSE;
      }

      if ( ! $this->DBCreateUser() ) {
        $dropbox->writeToLog("Failed to add usertable to database");
        NSSError($errorMsg,"Database Error creating usertable");
        return FALSE;
      }

      if ( ! $this->DBCreateRegexps() ) {
        $dropbox->writeToLog("Failed to add regexps to database");
        NSSError($errorMsg,"Database Error creating regexps table");
        return FALSE;
      }

      if ( ! $this->DBCreateLoginlog() ) {
        $dropbox->writeToLog("Failed to add loginlog table to database");
        NSSError($errorMsg,"Database Error creating loginlog table");
        return FALSE;
      }

      if ( ! $this->DBCreateLibraryDesc() ) {
        $dropbox->writeToLog("Failed to add librarydesc table to database");
        NSSError($errorMsg,"Database Error creating librarydesc table");
        return FALSE;
      }

      $dropbox->writeToLog("initial setup of database complete");

      return TRUE;
    }
    return FALSE;
}



private function DBCreate () {
      if ( ! $this->database->queryExec(
"CREATE TABLE dropoff (
  claimID             character varying(16) not null,
  claimPasscode       character varying(16),
  
  authorizedUser      character varying(16),
  
  senderName          character varying(32) not null,
  senderOrganization  character varying(32),
  senderEmail         text not null,
  senderIP            character varying(255) not null,
  confirmDelivery     boolean default FALSE,
  created             timestamp with time zone not null,
  note                text
);",$errorMsg) ) {
        return FALSE;
      }

      if ( ! $this->database->queryExec(
"CREATE TABLE recipient (
  dID                 integer not null,
  
  recipName           character varying(32) not null,
  recipEmail          text not null
);",$errorMsg) ) {
        return FALSE;
      }

      if ( ! $this->database->queryExec(
"CREATE TABLE file (
  dID                 integer not null,
  
  tmpname             text not null,
  basename            text not null,
  lengthInBytes       bigint not null,
  mimeType            character varying(32) not null,
  description         text
);",$errorMsg) ) {
        return FALSE;
      }

      if ( ! $this->database->queryExec(
"CREATE TABLE pickup (
  dID                 integer not null,
  
  authorizedUser      character varying(16),
  emailAddr           text,
  recipientIP         character varying(255) not null,
  pickupTimestamp     timestamp with time zone not null
);",$errorMsg) ) {
        return FALSE;
      }

      //  Do the indexes now:

      if ( ! $this->database->queryExec(
"CREATE INDEX dropoff_claimID_index ON dropoff(claimID);",$errorMsg) ) {
        return FALSE;
      }

      if ( ! $this->database->queryExec(
"CREATE INDEX recipient_dID_index ON recipient(dID);",$errorMsg) ) {
        return FALSE;
      }

      if ( ! $this->database->queryExec(
"CREATE INDEX file_dID_index ON file(dID);",$errorMsg) ) {
        return FALSE;
      }

      if ( ! $this->database->queryExec(
"CREATE INDEX pickup_dID_index ON pickup(dID);",$errorMsg) ) {
        return FALSE;
      }

      return TRUE;
}

public function DBCreateReq() {
      if ( ! $this->database->queryExec(
"CREATE TABLE reqtable (
  Auth        character varying(64) not null,
  SrcName  character varying(32),
  SrcEmail text not null,
  SrcOrg   character varying(32),
  DestName   character varying(32),
  DestEmail  text not null,
  Note        text not null,
  Subject     text not null,
  Expiry      bigint not null
);",$errorMsg) ) {
        return FALSE;
      }
      return TRUE;
}

// Needs to be public for people upgrading from Dropbox 2
public function DBCreateAuth() {
      if ( ! $this->database->queryExec(
"CREATE TABLE authtable (
  Auth         character varying(64) not null,
  FullName     character varying(32),
  Email        text not null,
  Organization character varying(32),
  Expiry       bigint not null
);",$errorMsg) ) {
        return FALSE;
      }
      return TRUE;
}

// Needs to be public for people upgrading from earlier versions
public function DBCreateUser() {
      if ( ! $this->database->queryExec(
"CREATE TABLE usertable (
  username     character varying(64) not null,
  password     character varying(64) not null,
  mail         character varying(256) not null,
  displayname  character varying(256) not null,
  organization character varying(256),
  quota        real
);",$errorMsg) ) {
        return FALSE;
      }
      if ( ! $this->database->queryExec(
"CREATE INDEX usertable_username_index ON usertable(username);",$errorMsg) ) {
        return FALSE;
      }
      return TRUE;
}

// Needs to be public for people upgrading from earlier versions
public function DBCreateRegexps() {
      if ( ! $this->database->queryExec(
"CREATE TABLE regexps (
  type         integer not null,
  re           text not null,
  created      bigint not null
);",$errorMsg) ) {
        return FALSE;
      }
      if ( ! $this->database->queryExec(
"CREATE INDEX regexpstable_type_index ON regexps(type);",$errorMsg) ) {
        return FALSE;
      }
      return TRUE;
}

// Needs to be public for people upgrading from earlier ZendTos
public function DBCreateLoginlog() {
      if ( ! $this->database->queryExec(
"CREATE TABLE loginlog (
  username     character varying(64) not null,
  created      bigint not null
);",$errorMsg) ) {
        return FALSE;
      }
      return TRUE;
}

// Needs to be public for people upgrading from earlier ZendTo
public function DBCreateLibraryDesc() {
      if ( ! $this->database->queryExec(
"CREATE TABLE librarydesc (
  filename    character varying(255) not null,
  description character varying(255)
);",$errorsg) ) {
        return FALSE;
      }
      return TRUE;
}

public function DBAddLoginlog($user) {
  $query = sprintf("INSERT INTO loginlog
                    (username, created)
                    VALUES
                    ('%s',%d)",
                    sqlite_escape_string(strtolower($user)),
                    time());
  if (!$this->database->queryExec($query)) {
    return "Failed to add login record";
  }
  return '';
}

public function DBDeleteLoginlog($user) {
  if ($user == "") {
    $query = "DELETE FROM loginlog";
  } else {
    $query = sprintf("DELETE FROM loginlog WHERE username = '%s'",
                     sqlite_escape_string(strtolower($user)));
  }
  if ( !$this->database->queryExec($query) ) {
    return "Failed to delete login records";
  }
  return '';
}

public function DBLoginlogLength($user, $since) {
  $query = sprintf("SELECT count(*) FROM loginlog
                    WHERE username = '%s' AND created > %d",
                   sqlite_escape_string(strtolower($user)),
                   $since);
  $res = $this->database->singleQuery($query);
  if (!$res) {
    return "Failed to read anything from loginlog";
  }
  return $res[0];
}

public function DBLoginlogAll($since) {
  $recordlist = $this->database->arrayQuery(
                  sprintf("SELECT * FROM loginlog WHERE created > %d",
                          $since),
                  SQLITE_ASSOC
                );
  return $recordlist;
}

// Delete the old regexps of this type and add the new ones
public function DBOverwriteRegexps($type, &$regexplist) {
  $now = time();
  if (!$this->DBStartTran()) {
    return "Failed to BEGIN transaction block while updating regexps";
  }

  // Delete the old ones
  if ( ! $this->database->queryExec(
                sprintf("DELETE FROM regexps WHERE type = %d", $type))) {
    if (!$this->DBRollbackTran()) {
      return "Failed to ROLLBACK after aborting addition of regexp";
    }
    return "Failed to add regexp";
  }

  // Add the new ones
  foreach ($regexplist as $re) {
    $query = sprintf("INSERT INTO regexps
                      (type, re, created)
                      VALUES
                      (%d,'%s',%d)",
                      $type,
                      sqlite_escape_string($re),
                      $now);
    if (!$this->database->queryExec($query)) {
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
  $res = $this->database->arrayQuery(
         sprintf("SELECT re,created FROM regexps WHERE type = %d",
                 $type),
         SQLITE_ASSOC);
  return $res;
}


public function DBReadLocalUser( $username ) {
    $recordlist = $this->database->arrayQuery(
                  sprintf("SELECT * FROM usertable WHERE username = '%s'",
                          sqlite_escape_string($username)),
                  SQLITE_ASSOC
                );
    return $recordlist;
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
                    sqlite_escape_string($username),
                    md5($password),
                    sqlite_escape_string($email),
                    sqlite_escape_string($name),
                    sqlite_escape_string($org),
                    $quota);
  } else {
    $query = sprintf("INSERT INTO usertable
                    (username, password, mail, displayname, organization)
                    VALUES
                    ('%s','%s','%s','%s','%s')",
                    sqlite_escape_string($username),
                    md5($password),
                    sqlite_escape_string($email),
                    sqlite_escape_string($name),
                    sqlite_escape_string($org));
  }

  if (!$this->database->queryExec($query)) {
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

  $this->database->arrayQuery(
    sprintf("DELETE FROM usertable WHERE username = '%s'",
            sqlite_escape_string($username)),
            SQLITE_ASSOC);
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
                    sqlite_escape_string($username));
  if (!$this->database->queryExec($query)) {
    return "Failed to update password for user $username";
  }
  return '';
}

public function DBUserQuota ( $username ) {
  global $NSSDROPBOX_PREFS;
  $result = $this->database->arrayQuery(
           sprintf("SELECT quota FROM usertable WHERE username = '%s'",
             sqlite_escape_string($username)
           )
         );
  return ($quota >= 1) ? $quota : $NSSDROPBOX_PREFS['defaultMyZendToQuota'];
}

public function DBRemainingQuota ( $username ) {
  $result = $this->database->arrayQuery(
            sprintf("SELECT SUM(lengthInBytes) AS usedQuotaInBytes FROM dropoff LEFT JOIN file ON dropoff.rowID=file.dID WHERE dropoff.authorizeduser='%s'",
              sqlite_escape_string($username)
              )
            );
  return $this->DBUserQuota($username) - $result[0][0];
}

public function DBUpdateQuotaLocalUser ( $username, $quota ) {
  if (!$this->DBReadLocalUser($username)) {
    return "Aborting updating user $username as that user does not exist";
  }

  $query = sprintf("UPDATE usertable
                    SET quota=%f
                    WHERE username='%s'",
                    $quota,
                    sqlite_escape_string($username));
  if (!$this->database->queryExec($query)) {
    return "Failed to update quota for user $username";
  }
  return '';
}

public function DBListLocalUsers () {
  return $this->database->arrayQuery(
           "SELECT * FROM usertable ORDER BY username",
           SQLITE_ASSOC
         );
}

public function DBListClaims ( $claimID, &$extant ) {
  $extant = $this->database->arrayQuery("SELECT * FROM dropoff WHERE claimID = '".sqlite_escape_string($claimID)."'");
}

public function DBWriteReqData( $dropbox, $hash, $srcname, $srcemail, $srcorg, $destname, $destemail, $note, $subject, $expiry ) {
    if ( ! $this->DBStartTran() ) {
      $dropbox->writeToLog("failed to BEGIN transaction block while adding request for $srcemail");
      return '';
    }
    $query = sprintf("INSERT INTO reqtable
                      (Auth,SrcName,SrcEmail,SrcOrg,DestName,DestEmail,Note,Subject,Expiry)
                      VALUES
                      ('%s','%s','%s','%s','%s','%s','%s','%s',%d)",
                      $hash,
                      sqlite_escape_string($srcname),
                      sqlite_escape_string($srcemail),
                      sqlite_escape_string($srcorg),
                      sqlite_escape_string($destname),
                      sqlite_escape_string($destemail),
                      sqlite_escape_string($note),
                      sqlite_escape_string($subject),
                      $expiry);
    if ( ! $this->database->queryExec($query) ) {
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

public function DBReadReqData( $authkey ) {
    $recordlist = $this->database->arrayQuery(
                  sprintf("SELECT * FROM reqtable WHERE Auth = '%s'",
                          sqlite_escape_string($authkey)),
                  SQLITE_ASSOC
                );
    return $recordlist;
}

public function DBDeleteReqData( $authkey ) {
    $this->database->arrayQuery(
      sprintf("DELETE FROM reqtable WHERE Auth = '%s'", sqlite_escape_string($authkey)),
              SQLITE_ASSOC);
}

public function DBPruneReqData( $old ) {
    $this->database->arrayQuery(
      sprintf("DELETE FROM reqtable WHERE Expiry < '%d'", $old),
              SQLITE_ASSOC);
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
                     $hash,
                     sqlite_escape_string($name),
                     sqlite_escape_string($email),
                     sqlite_escape_string($org),
                     $expiry);
    if ( ! $this->database->queryExec($query) ) {
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
    $recordlist = $this->database->arrayQuery(
                  sprintf("SELECT * FROM authtable WHERE Auth = '%s'",
                          sqlite_escape_string($authkey)),
                  SQLITE_ASSOC
                );
    return $recordlist;
}

public function DBDeleteAuthData( $authkey ) {
    $this->database->arrayQuery(
      sprintf("DELETE FROM authtable WHERE Auth = '%s'", sqlite_escape_string($authkey)),
              SQLITE_ASSOC);
}

public function DBPruneAuthData( $old ) {
    $this->database->arrayQuery(
      sprintf("DELETE FROM authtable WHERE Expiry < '%d'", $old),
              SQLITE_ASSOC);
}

public function DBDropoffsForMe( $targetEmail ) {
  return $this->database->arrayQuery(
           "SELECT d.rowID,d.* FROM dropoff d,recipient r WHERE d.rowID = r.dID AND r.recipEmail = '".sqlite_escape_string($targetEmail)."' ORDER BY d.created DESC",
           SQLITE_ASSOC
         );
}

public function DBDropoffsFromMe( $authSender, $targetEmail ) {
  return $this->database->arrayQuery(
           sprintf("SELECT rowID,* FROM dropoff WHERE authorizedUser = '".sqlite_escape_string($authSender)."' %s ORDER BY created DESC",
               ( $targetEmail ? ("OR senderEmail = '".sqlite_escape_string($targetEmail)."'") : "")
             ),
             SQLITE_ASSOC
           );
}

public function DBDropoffsTooOld( $targetDate ) {
  return $this->database->arrayQuery(
           "SELECT rowID,* FROM dropoff WHERE created < '$targetDate' ORDER BY created",
           SQLITE_ASSOC
         );
}

public function DBDropoffsToday( $targetDate ) {
  return $this->database->arrayQuery(
           "SELECT rowID,* FROM dropoff WHERE created >= '$targetDate' ORDER BY created",
           SQLITE_ASSOC
         );
}

public function DBDropoffsAll() {
  return $this->database->arrayQuery(
           "SELECT rowID,* FROM dropoff ORDER BY created DESC",
           SQLITE_ASSOC
         );
}

public function DBDropoffsAllRev() {
  return $this->database->arrayQuery(
           "SELECT rowID,* FROM dropoff ORDER BY created",
           SQLITE_ASSOC
         );
}

public function DBFilesByDropoffID( $dropoffID ) {
  return $this->database->arrayQuery(
           sprintf("SELECT rowID,* FROM file WHERE dID = %d ORDER by basename",
             sqlite_escape_string($dropoffID)),
           SQLITE_ASSOC
         );
}

public function DBDataForRRD( $set ) {
  $result = $this->database->arrayQuery(
           sprintf("SELECT COUNT(*),SUM(lengthInBytes) FROM file WHERE dID IN (%s)",
             sqlite_escape_string($set)),
           SQLITE_NUM
         );
  return $result[0];
}

public function DBBytesOfDropoff( $dropoffID ) {
  $result = $this->database->arrayQuery(
             sprintf("SELECT SUM(lengthInBytes) FROM file WHERE dID = %d",
               sqlite_escape_string($dropoffID)),
             SQLITE_NUM
           );
  return $result[0][0];
}

public function DBAddFile2( $d, $dropoffID, $tmpname, $filename,
                    $contentLen, $mimeType, $description, $claimID ) {
  if ( ! $this->DBStartTran() ) {
    $d->writeToLog("failed to BEGIN transaction block while adding $filename to dropoff $claimID");
    return false;
  }

  $query = sprintf("INSERT INTO file (dID,tmpname,basename,lengthInBytes,mimeType,description) VALUES (%d,'%s','%s',%.0f,'%s','%s')",
             sqlite_escape_string($dropoffID),
             sqlite_escape_string(basename($tmpname)),
             // SLASH sqlite_escape_string(stripslashes($filename)),
             sqlite_escape_string($filename),
             $contentLen,
             sqlite_escape_string($mimeType),
             // SLASH sqlite_escape_string(stripslashes($description))
             sqlite_escape_string($description)
          );
  if ( ! $this->database->queryExec($query) ) {
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
  return $this->database->arrayQuery(
           sprintf("SELECT * FROM file WHERE dID = %d AND rowID = %d",
             sqlite_escape_string($dropoffID),
             sqlite_escape_string($fileID)
           ),
           SQLITE_ASSOC
         );
}

public function DBExtantPickups( $dropoffID ) {
  return $this->database->arrayQuery(
           sprintf("SELECT count(*) FROM pickup WHERE dID = %d",
             sqlite_escape_string($dropoffID)
           )
         );
}

public function DBAddToPickupLog( $d, $dropoffID, $authorizedUser, $emailAddr,
                                  $remoteAddr, $timeStamp, $claimID ) {
  $query = sprintf("INSERT INTO pickup (dID,authorizedUser,emailAddr,recipientIP,pickupTimestamp) VALUES (%d,'%s','%s','%s','%s')",
             sqlite_escape_string($dropoffID),
             sqlite_escape_string($authorizedUser),
             sqlite_escape_string($emailAddr),
             sqlite_escape_string($remoteAddr),
             sqlite_escape_string($timeStamp)
           );
  if ( ! $this->database->queryExec($query) ) {
    $d->writeToLog("unabled to add pickup record for claimID ".$claimID);
  }
}

public function DBRemoveDropoff( $d, $dropoffID, $claimID ) {
      if ( $this->DBStartTran() ) {
        $query = sprintf("DELETE FROM pickup WHERE dID = %d",sqlite_escape_string($dropoffID));
        if ( ! $this->database->queryExec($query) ) {
          if ( $doLogEntries ) {
            $d->writeToLog("error in '$query'");
          }
          $this->DBRollbackTran();
          return FALSE;
        }

        $query = sprintf("DELETE FROM file WHERE dID = %d",sqlite_escape_string($dropoffID));
        if ( ! $this->database->queryExec($query) ) {
          if ( $doLogEntries ) {
            $d->writeToLog("error in '$query'");
          }
          $this->DBRollbackTran();
          return FALSE;
        }

        $query = sprintf("DELETE FROM recipient WHERE dID = %d",sqlite_escape_string($dropoffID));
        if ( ! $this->database->queryExec($query) ) {
          if ( $doLogEntries ) {
            $d->writeToLog("error in '$query'");
          }
          $this->DBRollbackTran();
          return FALSE;
        }

        $query = sprintf("DELETE FROM dropoff WHERE claimID = '%s'",sqlite_escape_string($claimID));
        if ( ! $this->database->queryExec($query) ) {
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
  return $this->database->arrayQuery(
           sprintf("SELECT rowID,* FROM file WHERE dID = %d ORDER by basename",
             sqlite_escape_string($dropoffID)
           ),
           SQLITE_ASSOC
         );
}

public function DBPickupsForDropoff ( $dropoffID ) {
  return $this->database->arrayQuery(
           sprintf("SELECT * FROM pickup WHERE dID = %d ORDER by pickupTimestamp",
             sqlite_escape_string($dropoffID)
           ),
           SQLITE_ASSOC
         );
}

public function DBDropoffsForClaimID ( $claimID ) {
  return $this->database->arrayQuery(
           sprintf("SELECT rowID,* FROM dropoff WHERE claimID = '%s'",
             sqlite_escape_string($claimID)
           ),
           SQLITE_ASSOC
         );
}

public function DBRecipientsForDropoff ( $rowID ) {
  return $this->database->arrayQuery(
           sprintf("SELECT recipName,recipEmail FROM recipient WHERE dID = %d",
             sqlite_escape_string($rowID)
           ),
           SQLITE_NUM
         );
}

public function DBStartTran () {
  return $this->database->queryExec('BEGIN');
}

public function DBRollbackTran () {
  return $this->database->queryExec('ROLLBACK');
}

public function DBCommitTran () {
  return $this->database->queryExec('COMMIT');
}

public function DBAddDropoff ( $claimID, $claimPasscode, $authorizedUser,
                               $senderName, $senderOrganization, $senderEmail,
                               $remoteIP, $confirmDelivery, $now, $note ) {
  $query = sprintf("INSERT INTO dropoff
                    (claimID,claimPasscode,authorizedUser,senderName,
                     senderOrganization,senderEmail,senderIP,
                     confirmDelivery,created,note)
                    VALUES
                    ('%s','%s','%s','%s', '%s','%s','%s', '%s','%s','%s')",
             sqlite_escape_string($claimID),
             sqlite_escape_string($claimPasscode),
             sqlite_escape_string($authorizedUser),
             sqlite_escape_string($senderName),
             sqlite_escape_string($senderOrganization),
             sqlite_escape_string($senderEmail),
             sqlite_escape_string($remoteIP),
             ( $confirmDelivery ? 't' : 'f' ),
             sqlite_escape_string($now),
             sqlite_escape_string($note)
           );
  if ( $this->database->queryExec($query) ) {
    return $this->database->lastInsertRowid();
  }
  return FALSE;
}

public function DBAddRecipients ( $recipients, $dropoffID ) {
  foreach ( $recipients as $recipient ) {
    $query = sprintf("INSERT INTO recipient
                      (dID,recipName,recipEmail)
                      VALUES
                      (%d,'%s','%s')",
               sqlite_escape_string($dropoffID),
               sqlite_escape_string($recipient[0]),
               sqlite_escape_string($recipient[1]));
    if ( ! $this->database->queryExec($query) ) {
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
             sqlite_escape_string($dropoffID),
             sqlite_escape_string($tmpname),
             sqlite_escape_string($basename),
             sqlite_escape_string($bytes),
             sqlite_escape_string($mimeType),
             sqlite_escape_string($description));
  if ( ! $this->database->queryExec($query) ) {
    return FALSE;
  }
  return TRUE;
}

// Build a mapping from filename to description
public function DBGetLibraryDescs () {
  $query = $this->database->arrayQuery(
            "SELECT filename,description FROM librarydesc",
            SQLITE_NUM
           );
  $result = array();
  foreach ($query as $q) {
    // Only set it if it's not already set and we're setting it non-blank
    if (!isset($result[$q[0]]) && isset($q[1])) {
      // Trim the leading+trailing space from everything
      $result[trim($q[0])] = trim($q[1]);
    }
    // $result[$q[0]] = $q[1];
  }
  return $result;
}

// Update the mapping from filename to description
public function DBUpdateLibraryDescription ( $file, $desc ) {
  $file = trim($file);
  $desc = trim($desc);
  $query = $this->database->arrayQuery(
             sprintf("SELECT COUNT(*) FROM librarydesc WHERE filename='%s'",
               sqlite_escape_string($file)),
             SQLITE_NUM
           );
  if ($query[0][0]>=1) {
    // Entry for this filename already exists, so UPDATE
    $query = sprintf("UPDATE librarydesc SET description='%s' WHERE filename='%s'",
               sqlite_escape_string($desc),
               sqlite_escape_string($file));
    $query = $this->database->queryExec($query);
  } else {
    // Entry for this filename does not exist, so INSERT
    $query = sprintf("INSERT INTO librarydesc (filename,description) VALUES ('%s','%s')",
               sqlite_escape_string($file),
               sqlite_escape_string($desc));
    $query = $this->database->queryExec($query);
  }
}

}

?>
