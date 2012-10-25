#!/usr/bin/php
<?PHP

$today = getdate();

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
include_once(NSSDROPBOX_LIB_DIR."Smartyconf.php");
include_once(NSSDROPBOX_LIB_DIR."NSSDropoff.php");

if ( $theDropbox = new NSSDropbox($NSSDROPBOX_PREFS) ) {
  //  Get all of the dropoffs:
  $qResult = $theDropbox->database->DBDropoffsAllRev();
  if ( $qResult && ($iMax = count($qResult)) ) {
    
    //  Start date?
    if ( preg_match('/^([0-9]{4})\-([0-9]{2})\-([0-9]{2})/',$qResult[0]['created'],$matches) ) {
      
      $theCount = 1;
      $theDate = $matches[1].'-'.$matches[2].'-'.$matches[3];
      $theTime = mktime(0,0,0,$matches[2],$matches[3] + 1,$matches[1]);
      $theSet = $qResult[0]['rowID'];
      
      //  Create the RRD file if necessary:
      if ( ! file_exists(RRD_DATA) ) {
        print "Initial creation of RRD file (start date of $theDate)\n";
        //
        // Three data sources, maintained on a daily basis.  First archive holds one year of daily
        // values, second archive holds ten-years worth of monthly averages.
        //
        system( RRDTOOL.' create '.RRD_DATA.' --start '.$matches[1].$matches[2].$matches[3].
                ' --step 86400 '.
                'DS:dropoff_count:GAUGE:90000:0:U '.
                'DS:total_files:GAUGE:90000:0:U '.
                'DS:total_kb:GAUGE:90000:0:U '.
                'DS:files_per_dropoff:COMPUTE:total_files,dropoff_count,/ '.
                'RRA:AVERAGE:0.5:1:365 '.
                'RRA:AVERAGE:0.5:30:120' );
      }
      
      //  Now we essentially need to loop over the whole list of dropoffs; figure
      //  out how many correspond to "theDate" and build a list of rowIDs that
      //  we'll check for file count and total size:
      $i = 1;
      while ( $i < $iMax ) {
        if ( strpos($qResult[$i]['created'],$theDate) === 0 ) {
          $theSet .= ','.$qResult[$i]['rowID'];
          $theCount++;
        } else {
          //  Finish and add the last day's data point:
          if ( $theSet ) {
            doRRDUpdate($theDropbox->database,$theCount,$theTime,$theSet);
          }
          
          //  Get set for the next day:
          preg_match('/^([0-9]{4})\-([0-9]{2})\-([0-9]{2})/',$qResult[$i]['created'],$matches);

          if ( $matches[1] >= $today['year'] && $matches[2] >= $today['mon'] && $matches[3] >= $today['mday'] ) {
            $theSet = NULL;
            break;
          }
          $theDate = $matches[1].'-'.$matches[2].'-'.$matches[3];
          $theTime = mktime(0,0,0,$matches[2],$matches[3] + 1,$matches[1]);
          $theSet = $qResult[$i]['rowID'];
          $theCount = 1;
        }
        $i++;
      }
      //  Finish and add the last day's data point:
      if ( $theSet ) {
        doRRDUpdate($theDropbox->database,$theCount,$theTime,$theSet);
      }
      
    }
  }
  
  
}


function doRRDUpdate(
  $database,
  $count,
  $time,
  $set
)
{
  //  Get file stats:
  print "$set\n";
  // JKF $qResult = $database->arrayQuery('SELECT COUNT(*),SUM(lengthInBytes) FROM file WHERE dID IN ('.$set.')',SQLITE_NUM);
  $qResult = $database->DBDataForRRD($set);
  if ( $qResult ) {
    $cmd = sprintf("%s update %s %d:%d:%d:%.1f",
                    RRDTOOL,
                    RRD_DATA,
                    $time,
                    $count,
                    $qResult[0],
                    $qResult[1] / 1024.0 );
    print "$cmd\n\n";
    system($cmd);
  }
}

?>
