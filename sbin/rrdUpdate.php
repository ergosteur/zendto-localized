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

    $command = RRDTOOL." graph ".RRD_DATA_DIR."dropoff_count%d.png --lower-limit 0 --start N-%1\$dD --width 400 --height 125 DEF:var=".RRD_DATA.":dropoff_count:AVERAGE AREA:var#D0D0F080 LINE2:var#8080A0:\"total dropoffs\"";
    system(sprintf($command,7));
    system(sprintf($command,30));
    $command = RRDTOOL." graph ".RRD_DATA_DIR."dropoff_count%d.png --lower-limit 0 --start N-%1\$dD --step 604800 --width 400 --height 125 DEF:var=".RRD_DATA.":dropoff_count:AVERAGE AREA:var#D0D0F080 LINE2:var#8080A0:\"total dropoffs\"";
    system(sprintf($command,90));
    $command = RRDTOOL." graph ".RRD_DATA_DIR."dropoff_count%d.png --lower-limit 0 --start N-%1\$dD --step 1209600 --width 400 --height 125 DEF:var=".RRD_DATA.":dropoff_count:AVERAGE AREA:var#D0D0F080 LINE2:var#8080A0:\"total dropoffs\"";
    system(sprintf($command,365));
    system(sprintf($command,3650));
    
    $command = RRDTOOL." graph ".RRD_DATA_DIR."total_files%d.png --lower-limit 0 --start N-%1\$dD --width 400 --height 125 DEF:var=".RRD_DATA.":total_files:AVERAGE AREA:var#D0D0F080 LINE2:var#8080A0:\"total files\"";
    system(sprintf($command,7));
    system(sprintf($command,30));
    $command = RRDTOOL." graph ".RRD_DATA_DIR."total_files%d.png --lower-limit 0 --start N-%1\$dD --step 604800 --width 400 --height 125 DEF:var=".RRD_DATA.":total_files:AVERAGE AREA:var#D0D0F080 LINE2:var#8080A0:\"total files\"";
    system(sprintf($command,90));
    $command = RRDTOOL." graph ".RRD_DATA_DIR."total_files%d.png --lower-limit 0 --start N-%1\$dD --step 1209600 --width 400 --height 125 DEF:var=".RRD_DATA.":total_files:AVERAGE AREA:var#D0D0F080 LINE2:var#8080A0:\"total files\"";
    system(sprintf($command,365));
    system(sprintf($command,3650));
    
    //  Regenerate the graphs:
    $command = RRDTOOL." graph ".RRD_DATA_DIR."files_per_dropoff%d.png --lower-limit 0 --start N-%1\$dD --width 400 --height 125 DEF:var=".RRD_DATA.":files_per_dropoff:AVERAGE AREA:var#D0D0F080 LINE2:var#8080A0:\"files per dropoff\"";
    system(sprintf($command,7));
    system(sprintf($command,30));
    $command = RRDTOOL." graph ".RRD_DATA_DIR."files_per_dropoff%d.png --lower-limit 0 --start N-%1\$dD --step 604800 --width 400 --height 125 DEF:var=".RRD_DATA.":files_per_dropoff:AVERAGE AREA:var#D0D0F080 LINE2:var#8080A0:\"files per dropoff\"";
    system(sprintf($command,90));
    $command = RRDTOOL." graph ".RRD_DATA_DIR."files_per_dropoff%d.png --lower-limit 0 --start N-%1\$dD --step 1209600 --width 400 --height 125 DEF:var=".RRD_DATA.":files_per_dropoff:AVERAGE AREA:var#D0D0F080 LINE2:var#8080A0:\"files per dropoff\"";
    system(sprintf($command,365));
    system(sprintf($command,3650));
    
    $command = RRDTOOL." graph ".RRD_DATA_DIR."total_size%d.png --lower-limit 0 --start N-%1\$dD --width 400 --height 125 --base 1024 DEF:var=".RRD_DATA.":total_kb:AVERAGE CDEF:gb=var,1048576,/ AREA:gb#D0D0F080 LINE2:gb#8080A0:\"total amount of data / GB\"";
    system(sprintf($command,7));
    system(sprintf($command,30));
    $command = RRDTOOL." graph ".RRD_DATA_DIR."total_size%d.png --lower-limit 0 --start N-%1\$dD --step 604800 --width 400 --height 125 --base 1024 DEF:var=".RRD_DATA.":total_kb:AVERAGE CDEF:gb=var,1048576,/ AREA:gb#D0D0F080 LINE2:gb#8080A0:\"total amount of data / GB\"";
    system(sprintf($command,90));
    $command = RRDTOOL." graph ".RRD_DATA_DIR."total_size%d.png --lower-limit 0 --start N-%1\$dD --step 1209600 --width 400 --height 125 --base 1024 DEF:var=".RRD_DATA.":total_kb:AVERAGE CDEF:gb=var,1048576,/ AREA:gb#D0D0F080 LINE2:gb#8080A0:\"total amount of data / GB\"";
    system(sprintf($command,365));
    system(sprintf($command,3650));

?>

