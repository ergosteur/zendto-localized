#!/usr/bin/php
<?PHP

if ( count($argv) < 2 ) {
  printf("
  usage:
  
   %s <zendto preference file>
  
   The zendto preference file path should be canonical, not relative.

",$argv[0]);
  return 0;
}

if ( ! preg_match('/^\/.+/',$argv[1]) ) {
  echo "ERROR:  You must provide a canonical path to the preference file.\n";
  return 1;
}

include $argv[1];
require_once(NSSDROPBOX_LIB_DIR."Smartyconf.php");
include_once(NSSDROPBOX_LIB_DIR."NSSDropoff.php");

if ( $theDropbox = new NSSDropbox($NSSDROPBOX_PREFS) ) {
  
  printf("Extending the database schema to add the sender verification table\n");
  $theDropbox->setupDatabaseAuthTable();
  printf("Done\n");
}

?>
