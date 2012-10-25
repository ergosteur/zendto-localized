#!/usr/bin/php
<?PHP

if ($_ENV['ZENDTOPREFS']) {
  array_splice($argv, 1, 0, $_ENV['ZENDTOPREFS']);
}

if ( count($argv) < 2 ) {
  printf("
  usage:
  
   %s <ZendTo preferences.php file>
  
   The ZendTo preferences.php file path should be canonical, not relative.
   Alternatively, do
     export ZENDTOPREFS=<full file path of preferences.php>
     %s

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
  
  printf("Extending the database schema to add the loginlog table\n");
  $theDropbox->setupDatabaseLoginlogTable();
  printf("Done\n");
}

?>
