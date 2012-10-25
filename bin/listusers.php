#!/usr/bin/php
<?PHP

// Have they put --help or -help in the command-line args?
if (preg_match('/-help/i',$argv[1].' '.$argv[2])) {
  $help = 1;
}

if (count($argv)==1 && getenv('ZENDTOPREFS')) {
  array_splice($argv, 1, 0, getenv('ZENDTOPREFS'));
}

if ( $help || count($argv) != 2 ) {
  $prefs=getenv('ZENDTOPREFS');
  if ($prefs=='') {
    printf("
  usage:
  
   %s <ZendTo preferences.php file>
  
   The ZendTo preferences.php file path should be canonical, not relative.
   (It must start with a \"/\")
   Alternatively, do
     export ZENDTOPREFS=<full file path of preferences.php>
     %s

   It will output a tab-separated line for each user showing:
      Username E-mail address Full name Organisation

   In addition, if you are using MyZendTo, it will add this to the line:
      Quota Remaining quota
",$argv[0],$argv[0]);
  } else {
    printf("
  usage:
  
   %s
  
   The ZendTo preferences.php file path is pointed to by the environment
   variable ZENDTOPREFS, which is currently set to
   %s

   It will output a tab-separated line for each user showing:
      Username E-mail address Full name Organisation

   In addition, if you are using MyZendTo, it will add this to the line:
      Quota Remaining quota
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
include_once(NSSDROPBOX_LIB_DIR."NSSUtils.php");

if ( $theDropbox = new NSSDropbox($NSSDROPBOX_PREFS, FALSE, TRUE) ) {
  
  $qResult = $theDropbox->database->DBListLocalUsers();

  for ($i=0; $i<count($qResult); $i++) {
    $u = $qResult[$i];
    //$quota = $theDropbox->database->DBUserQuota($u['username']);
    printf($u['username']   ."\t".$u['mail']        ."\t".
           $u['displayname']."\t".$u['organization']."\t");
    if (preg_match('/^[yYtT1]/', MYZENDTO)) {
      $quotaLeft = $theDropbox->database->DBRemainingQuota($u['username']);
      printf("%.0f (%s)\t%.0f (%s)",
             $u['quota'], NSSFormattedMemSize($u['quota']),
             $quotaLeft,  NSSFormattedMemSize($quotaLeft));
    }
    printf("\n");
  }
}

?>
