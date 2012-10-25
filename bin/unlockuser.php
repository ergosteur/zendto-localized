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
  
   %s <ZendTo preferences.php file> -a | <username>
  
   The ZendTo preferences.php file path should be canonical, not relative.
   (It must start with a \"/\")
   Alternatively, do
     export ZENDTOPREFS=<full file path of preferences.php>
     %s -a | <username>

   Specify either '-a' or the username to unlock.
   '-a' unlocks all users.

",$argv[0],$argv[0]);
  } else {
    printf("
  usage:
  
   %s -a | <username>
  
   The ZendTo preferences.php file path is pointed to by the environment
   variable ZENDTOPREFS, which is currently set to
   %s

   Specify either '-a' or the username to unlock.
   '-a' unlocks all users.

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
require_once(NSSDROPBOX_LIB_DIR."NSSDropbox.php");

if ( $theDropbox = new NSSDropbox($NSSDROPBOX_PREFS,FALSE,TRUE) ) {
  
  $user = $argv[2];

  if ($user == "-a") {
    printf("Unlocking all users\n");
    $res = $theDropbox->database->DBDeleteLoginlog("");
  } else {
    printf("Unlocking user ".$user);
    printf(sprintf(", deleting %d records\n",$theDropbox->database->DBLoginlogLength($user,0)));
    $res = $theDropbox->database->DBDeleteLoginlog($user);
  }
  if ($res) {
    printf("Failed with error: $res\n");
    exit(1);
  }
  exit(0);
}

?>
