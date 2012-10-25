#!/usr/bin/perl
use FileHandle;
$key = $ARGV[0];
$val = $ARGV[1];
$file = $ARGV[2];
$in = new FileHandle("<$file");
$out = new FileHandle(">$file.zendto");
print STDERR "Setting $key to $val in $file\n";
$found = 0;
while(<$in>) {
  chomp;
  if (/^\s*$key\s*=/) {
    $found = 1;
    print $out "$key = $val\n";
  } else {
    print $out "$_\n";
  }
}
if (!$found) {
  print $out "$key = $val\n";
}
close $out;
close $in;
unlink $file;
rename "$file.zendto", $file;
exit 0;
