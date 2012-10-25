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
  @function timestampForTime
  
  Returns a textual timestamp corresponding to the number of
  seconds since the epoch that is passed to us.
*/
function timestampForTime(
  $timeSinceEpoch = 0
)
{
  return strftime('%Y-%m-%d %H:%M:%S%z',$timeSinceEpoch);
}



/*!
  @function timeForTimestamp
  
  Parse a timestamp and return the number of seconds since the
  epoch.
*/
function timeForTimestamp(
  $timestamp = '1970-01-01 00:00:00-00'
)
{
  return strtotime($timestamp);
}



/*!
  @function dateForTimestamp
  
  Parse a timestamp and return an array of values akin to what
  the PHP function getDate() returns -- which makes sense, since
  it calls getDate()!
*/
function dateForTimestamp(
  $timestamp = '1970-01-01 00:00:00-00'
)
{
  return getDate(timeForTimestamp($timestamp));
}



/*!
  @function timeForDate
  
  Given an array of values as returned by the getDate() function,
  returns the corresponding number of seconds since the epoch.
*/
function timeForDate(
  $aDate = NULL
)
{
  if ( $aDate ) {
    return mktime($aDate['hours'],$aDate['minutes'],$aDate['seconds'],$aDate['mon'],$aDate['mday'],$aDate['year']);
  }
  return 0;
}



/*!
  @function timestampForDate
  
  Given an array of values as returned by the getDate() function,
  returns a textual timestamp.
*/
function timestampForDate(
  $aDate = NULL
)
{
  return timestampForTime(timeForDate($aDate));
}

?>
