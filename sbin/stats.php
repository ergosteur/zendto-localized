#!/usr/bin/php
<?PHP

function fileCouldBeNaughty(
  $fileInfo
)
{
  //  We can actually check by MIME type.  First, let's see if
  //  it's audio or video:
  $mimeType = strtolower($fileInfo['mimeType']);
  if ( preg_match('/^audio\//',$mimeType) ) {
    return TRUE;
  }
  if ( preg_match('/^video\//',$mimeType) ) {
    return TRUE;
  }
  //  Nope, we don't care about it:
  return FALSE;
}

//

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

   If no email address is provided, the summary is displayed on stdout.

",$argv[0],$argv[0]);
  return 0;
}

if ( ! preg_match('/^\/.+/',$argv[1]) ) {
  echo "ERROR:  You must provide a canonical path to the preferences.php file.\n";
  return 1;
}

include $argv[1];
include_once(NSSDROPBOX_LIB_DIR."Smartyconf.php");
include_once(NSSDROPBOX_LIB_DIR."NSSDropoff.php");

if ( $theDropbox = new NSSDropbox($NSSDROPBOX_PREFS) ) {
  
  //
  // Get all drop-offs for the past 24 hours:
  //
  $newDropoffs = NSSDropoff::dropoffsCreatedToday($theDropbox);
  $totalFiles = 0;
  $totalBytes = 0.0;
  if ( $newDropoffs && ($iMax = count($newDropoffs)) ) {
    
    $i = 0;
    $message = "Dropoff summary for ".timestampForTime( time() - 24 * 60 * 60 )." - ".timestampForTime( time() )." :\n\n";
    $message .= "=========================================================================================\n";
    $message .= sprintf("%-18s %22s  %s\n","Claim ID","Total Files & Size","Sender");
    $message .= "=========================================================================================\n";
    while ( $i < $iMax ) {
      //  Get a file list:
      $files = $newDropoffs[$i]->files();
      if ( $files && ( $fileCount = $files['totalFiles'] ) ) {
        $totalFiles += $fileCount;
        $totalBytes += floatval( $files['totalBytes'] );
        $message .= sprintf("[%s] %3d file%s   %10s  %s <%s>\n",
                        $newDropoffs[$i]->claimID(),
                        $fileCount,
                        ( $fileCount != 1 ? "s" : " " ),
                        NSSFormattedMemSize($files['totalBytes']),
                        $newDropoffs[$i]->senderName(),
                        $newDropoffs[$i]->senderEmail()
                      );
        /*  Check for files that match types we want to watch for:
        $j = 0;
        while ( $j < $fileCount ) {
          if ( fileCouldBeNaughty($files[$j]) ) {
            $message .= sprintf("                                           **** %s (%s)\n",
                          $files[$j]['basename'],
                          $files[$j]['mimeType']
                        );
          }
          $j++;
        }
        */
      }
      $i++;
    }
    
    $percentUsage = exec('/bin/df -k /opt/DropboxData | /bin/grep /opt');
    
    $message .= "=========================================================================================\n";
    $message .= sprintf("%-18s %3d file%s   %10s",
                    sprintf("%d dropoff%s",$iMax,($iMax ? "s" : "")),
                    $totalFiles,
                    ( $totalFiles != 1 ? "s" : " " ),
                    NSSFormattedMemSize($totalBytes)
                  );
    if ( preg_match("/^[\/A-Za-z0-9]+[ ]*[0-9]+[ ]*[0-9]+[ ]*[0-9]+[ ]*([0-9]+\%)/",$percentUsage,$values) ) {
      $message .= "  (".$values[1]." used on /opt)";
    }
    
    if ( count($argv) < 3 ) {
      print "$message\n\n";
    } else {
      $today = getdate(time() - 24 * 60 * 60 );
      mail(
        $argv[2],
        sprintf("Dropoffs for %s %d, %04d",$today['month'],$today['mday'],$today['year']),
        $message);
    }
  }
  
  //  Log the totals to our rrd:
  if ( is_writeable($path = RRD_DATA) ) {
    system(RRDTOOL." update $path N:$iMax:$totalFiles:".$totalBytes / 1024.0);
    
    $command = RRDTOOL." graph ".RRD_DATA_DIR."dropoff_count%d.png --start N-%dD --width 400 --height 125 DEF:var=".RRD_DATA.":dropoff_count:AVERAGE AREA:var#D0D0F080 LINE2:var#8080A0:\"total dropoffs\"";
    system(sprintf($command,7,7));
    system(sprintf($command,30,30));
    system(sprintf($command,90,90));
    system(sprintf($command,365,365));
    system(sprintf($command,3650,3650));
    
    $command = RRDTOOL." graph ".RRD_DATA_DIR."total_files%d.png --start N-%dD --width 400 --height 125 DEF:var=".RRD_DATA.":total_files:AVERAGE AREA:var#D0D0F080 LINE2:var#8080A0:\"total files\"";
    system(sprintf($command,7,7));
    system(sprintf($command,30,30));
    system(sprintf($command,90,90));
    system(sprintf($command,365,365));
    system(sprintf($command,3650,3650));
    
    //  Regenerate the graphs:
    $command = RRDTOOL." graph ".RRD_DATA_DIR."files_per_dropoff%d.png --start N-%dD --width 400 --height 125 DEF:var=".RRD_DATA.":files_per_dropoff:AVERAGE AREA:var#D0D0F080 LINE2:var#8080A0:\"files per dropoff\"";
    system(sprintf($command,7,7));
    system(sprintf($command,30,30));
    system(sprintf($command,90,90));
    system(sprintf($command,365,365));
    system(sprintf($command,3650,3650));
    
    $command = RRDTOOL." graph ".RRD_DATA_DIR."total_size%d.png --start N-%dD --width 400 --height 125 DEF:var=".RRD_DATA.":total_kb:AVERAGE CDEF:gb=var,1048576,/ AREA:gb#D0D0F080 LINE2:gb#8080A0:\"total amount of data / GB\"";
    system(sprintf($command,7,7));
    system(sprintf($command,30,30));
    system(sprintf($command,90,90));
    system(sprintf($command,365,365));
    system(sprintf($command,3650,3650));
  }
  
}

?>
