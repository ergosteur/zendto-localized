<?php

if(isset($_GET['progress_id'])) {
 $id = $_GET['progress_id'];
 $id = preg_replace('/[^a-zA-Z0-9]/', '', $id);

 $status = apc_fetch('upload_'.$id);
 $looped = apc_fetch('looped_'.$id);
 if(isset($status['current'])) {
  // How much was left last time?
  $current = $status['current'];
  $prev = apc_fetch('prev_'.$id);

  $current += (1<<32) * $looped;
  if ($current < $prev) {
    $looped++;
    $current += 1<<32;
  }
  apc_store('looped_'.$id, $looped);
  apc_store('prev_'.$id, $current);

  $percentleft = intval(100.0-$current/$status['total']*100.0+0.5);
  if ($percentleft>100) { $percentleft = 100; }
  if ($percentleft<0  ) { $percentleft = 0;   }
  echo sprintf("%d", $percentleft);
 } else {
  echo '100';
  apc_store('prev_'.$id, 0);
  apc_store('looped_'.$id, 0);
 }
} else {
 die("GET parameter 'progress_id' is missing");
}

?>
