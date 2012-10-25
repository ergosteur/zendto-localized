<?PHP
//
// ZendTo
// Copyright (C) 2006 Jeffrey Frey, frey at udel dot edu
// Copyright (C) 2010 Julian Field, Jules at ZendTo dot com 
//
// Based on the original PERL dropbox written by Doke Scott.
// Developed by Julian Field.
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//
//

/*!
  @function NSSError
  
  Generic error output routine.  If there's a remote IP in the $_SERVER
  global then we'll figure on HTML output.  Otherwise, we just do standard
  textual output.
*/
function NSSError(
  $text,
  $title = NULL
)
{
  global $smarty;
  global $pageErrorList;

  if ( $_SERVER['REMOTE_ADDR'] ) {
    $pageErrorList[] = array('title'=>$title, 'text'=>$text);
  } else {
    printf("ERROR: %s%s%s\n",($title ? $title : ""),($title ? " : " : ""),$text);
  }
}



/*!
  @function NSSFormattedMemSize
  
  Creates a string the gives a more human-readable memory size description.
  If $bytes is less than 1K then it returns $bytes plus the word "bytes";
  otherwise, the result is a floating-point value with one digit past the
  decimal and the appropriate label (KB, MB, or GB).
*/
function NSSFormattedMemSize(
  $bytes
)
{
  static $NSSFormattedMemSize_Formats = array ( "%d bytes" , "%.1f KB" , "%.1f MB" , "%.1f GB" , "%.1f TB" );
  
  if ( floor($bytes) < 0.0 ) {
    //  Grrr...stupid 32-bit nonsense.  Convert to the positive
    //  value float-wise:
    $bytes = (floor($bytes) & 0x7FFFFFFF) + 2147483648.0;
  } else if ( floor($bytes) < 1.0 ) {
    return "0 bytes";
  } else if ( floor($bytes) < 2.0 ) {
    return "1 byte";
  }

  
  $unitIdx = floor(log($bytes = abs($bytes)) / log(1024));
  $unitIdx = ( ($unitIdx < count($NSSFormattedMemSize_Formats)) ? $unitIdx : count(NSSFormattedMemSize_Formats) );
  return sprintf($NSSFormattedMemSize_Formats[$unitIdx],($unitIdx ? $bytes / pow(1024.0,$unitIdx) : $bytes));
}



/*!
  @fucntion NSSGenerateCode
  
  Generate a random, alphanumeric code string.  The length is by-default 16
  characters.
  
  The characters are chosen from the $NSSGenerateCode_CharSet variable at
  indices dictated by $codeLength sequential calls to the PHP mt_rand()
  random number generator.
*/
function NSSGenerateCode(
  $codeLength = 16
)
{
  static $NSSGenerateCode_CharSet = "abcdefghijkmnopqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789";
  $code = "";
  $count = 16;
  $size = strlen($NSSGenerateCode_CharSet) - 1;
  while ( $count-- ) {
    $code .= substr($NSSGenerateCode_CharSet,mt_rand(0,$size),1);
  }
  return $code;
}



/*!
  @function NSSGenerateCookieSecret
  
  Generates a 32-character hexadecimal string (a'la an MD5 checksum) to use
  in HTML cookies.  Two methods for this:  dump a 1024 byte chunk of random
  memory out of /dev/random and compute its MD5 checksum; or, use the built-in
  extended random generator to create the 16 bytes.
*/
function NSSGenerateCookieSecret()
{
  if ( !file_exists("/dev/random") || !($sum = exec("/bin/dd if=/dev/random bs=1024 count=1 2>/dev/null | md5sum | sed 's/ .*$//'")) ) {
    $sum = "";
    $count = 16;
    while ( $count-- ) {
      $sum .= sprintf("%02X",mt_rand(0,255));
    }
  }
  return $sum;
}


/*!
  @function rmdir_r
  
  Recursive directory removal.
*/
function rmdir_r(
  $path
)
{
  if ( is_dir($path) ) {
    foreach ( glob($path."/*") as $file ) {
      if ( $file != "." && $file != ".." ) {
        if ( is_dir($file) ) {
          rmdir_r($file);
        } else if ( !unlink($file) ) {
          return FALSE;
        }
      }
    }
    if ( rmdir($path) ) {
      return TRUE;
    }
  }
  return FALSE;
}

/* Strip slashes except when get_magic_quotes is enabled. */
function paramPrepare(
  $value
)
{
  return get_magic_quotes_gpc()?stripslashes($value):$value;
}

?>
