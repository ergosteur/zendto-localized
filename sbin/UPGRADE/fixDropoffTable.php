#!/usr/bin/php
<?PHP

if ($_ENV['ZENDTOPREFS']) {
  array_splice($argv, 1, 0, $_ENV['ZENDTOPREFS']);
}

if ( count($argv) < 2 ) {
  printf("
  usage:
  
   %s <ZendTo preferences.php file> <email address>
  
   The ZendTo preferences.php file path should be canonical, not relative.
   Alternatively, do
     export ZENDTOPREFS=<full file path of preferences.php>
     %s <email address>

",$argv[0],$argv[0]);
  return 0;
}

if ( ! preg_match('/^\/.+/',$argv[1]) ) {
  echo "ERROR:  You must provide a canonical path to the preference file.\n";
  return 1;
}

include $argv[1];
require_once(NSSDROPBOX_LIB_DIR."Smartyconf.php");
include_once(NSSDROPBOX_LIB_DIR."NSSDropoff.php");

if ( $theDropbox = new NSSDropbox($NSSDROPBOX_PREFS,FALSE,TRUE) ) {
      $qResult = $theDropbox->database()->arrayQuery(
                    "SELECT rowID,* FROM dropoff",
                    SQLITE_ASSOC
                  );
      echo "BEGIN TRANSACTION;\n";
      foreach ($qResult as $q) {
        echo "INSERT INTO dropoff\n";
        echo "( rowID, claimID, claimPasscode, authorizedUser, senderName, senderOrganization, senderEmail, senderIP, confirmDelivery, created, note )\n";
	echo sprintf("VALUES (%d,'%s','%s','%s','%s','%s','%s','%s','%s','%s','%s');\n",
             $q[rowID],
             sqlite_escape_string($q[claimID]),
             sqlite_escape_string($q[claimPasscode]),
             sqlite_escape_string($q[authorizedUser]),
             sqlite_escape_string($q[senderName]),
             sqlite_escape_string($q[senderOrganization]),
             sqlite_escape_string($q[senderEmail]),
             sqlite_escape_string($q[senderIP]),
             sqlite_escape_string($q[confirmDelivery]),
             sqlite_escape_string($q[created]),
             sqlite_escape_string($q[note]));
     }
     echo "COMMIT;\n";
}

?>
