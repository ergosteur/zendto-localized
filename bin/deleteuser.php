#!/usr/bin/php
<?PHP

if (count($argv)==2 && getenv('ZENDTOPREFS')) {
  array_splice($argv, 1, 0, getenv('ZENDTOPREFS'));
}

if ( count($argv) != 3 ) {
  $prefs=getenv('ZENDTOPREFS');
  if ($prefs=='') {
    printf("
  usage:
  
   %s <ZendTo preferences.php file> '<username>'
  
   The ZendTo preferences.php file path should be canonical, not relative.
   (It must start with a \"/\")
   Alternatively, do
     export ZENDTOPREFS=<full file path of preferences.php>
     %s '<username>'

",$argv[0],$argv[0]);
  } else {
    printf("
  usage:

   %s '<username>'
  
   The ZendTo preferences.php file path is pointed to by the environment
   variable ZENDTOPREFS, which is currently set to
   %s

",$argv[0],$prefs);
  }
  return 0;
}

if ( ! preg_match('/^\/.+/',$argv[1]) ) {
  echo "ERROR:  You must provide a canonical path to the preference file.\n";
  return 1;
}

include $argv[1];
require_once(NSSDROPBOX_LIB_DIR."Smartyconf.php");
include_once(NSSDROPBOX_LIB_DIR."NSSDropoff.php");

if ( $theDropbox = new NSSDropbox($NSSDROPBOX_PREFS, FALSE, TRUE) ) {
  $username = $argv[2];

  $result = $theDropbox->database->DBDeleteLocalUser($username);

  if ($result == '') {
    printf("User $username deleted.\n");
    return 0;
  } else {
    printf("Failed: $result\n");
    return 1;
  }
}

?>
