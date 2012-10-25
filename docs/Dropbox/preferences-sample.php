<?PHP
//
// Dropbox 2.1
// Copyright (C) 2006 Jeffrey Frey, frey at udel dot edu
//
// Based on the original PERL dropbox written by Doke Scott.
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
// You MUST be sure that the entire content of this file is contained within
// PHP delimiters, otherwise cookies and other HTTP header stuff will not
// work properly!
//

//
// We need to define some standard paths that the rest of the PHP
// files may end up using.
//
//    NSSDROPBOX_BASE_DIR     Location of the dropbox package
//    NSSDROPBOX_LIB_DIR      Location of the dropbox library
//    NSSDROPBOX_DATA_DIR     Location of the dropbox data directory,
//                            which will hold the SQLite database for the
//                            dropbox and the directory of uploads.
//
// It is VERY important to note that the NSSDROPBOX_DATA_DIR must be setup
// with write-permission granted to the user associated with the system's
// web server.  I suggest a data directory structured akin to:
//
//    /tmp/dropbox
//      |- /tmp/dropbox/dropbox.sqlite
//      |- /tmp/dropbox/dropoffs
//      |- /tmp/dropbox/dropbox.log
// 
// so all files that the web server must be able to access are co-located.
//
define('NSSDROPBOX_BASE_DIR','/opt/NSSDropbox/');
define('NSSDROPBOX_LIB_DIR','/opt/NSSDropbox/lib/');
define('NSSDROPBOX_DATA_DIR','/opt/NSSDropbox/data/');

//
// Buttons can be skinned by adding a new directory in image/ and
// using its name as the value for NSSTHEME here.  We
// include several button themes in the package:
//
define('NSSTHEME','default');
//define('NSSTHEME','flat');
//define('NSSTHEME','flat2');
//define('NSSTHEME','blue');
//define('NSSTHEME','algae');
//define('NSSTHEME','duracell');

//
// Preferences are stored as a hashed array.  Inline comments
// indicate what everything is for.
//
$NSSDROPBOX_PREFS = array(
  //
  // This is generically a dropbox package, but why not use
  // something a bit more descriptive:
  //
  'dropboxName'           => 'Dropbox Service',
  //
  // Our DNS domain, for knowing when email addresses are "internal" or
  // "external" to us:
  //
  'dropboxDomain'         => 'mydomain.org',
  //
  // The dropbox directory indicates where this package should
  // create actual dropboxes and store the uploads.\
  //
  'dropboxDirectory'      => NSSDROPBOX_DATA_DIR.'dropoffs',
  //
  // Filepath for the SQLite database used to hold the drop-offs and
  // pick-ups.  Probably a good idea to keep it in the same directory
  // as the incoming and dropbox directories.
  //
  'dropboxDatabase'       => NSSDROPBOX_DATA_DIR.'dropbox.sqlite',
  //
  // How long should uploads stick around before we drop them?
  //
  'numberOfDaysToRetain'  => 14,
  //
  // Who exactly are authenticated users?  We use two labels to
  // describe such users:
  //
  'authUserFormalDesc'    => 'MyDomain Organization',
  'authUserShortDesc'     => 'MyDomainOrg',
  //
  // What email address should be used as "from" on messages sent
  // by the system?
  //
  'emailSenderAddr'       => 'Dropbox <dropbox@mydomain.org>',
  //
  // Maximum size for a single file as well as max size for an entire
  // drop-off (sum over all files).  Note that your sysadmin may have
  // to modify the following directives in the system's php.ini file
  // augment the upload capabilities:
  //
  //    file_uploads
  //    upload_max_filesize
  //    upload_tmp_dir
  //    post_max_size
  //    max_input_time
  //
  //
  'maxBytesForFile'       => 10.0 * 1024 * 1024,
  'maxBytesForDropoff'    => 20.0 * 1024 * 1024,
  //
  // Where's our log file?
  //
  'logFilePath'           => NSSDROPBOX_DATA_DIR.'dropbox.log',
  //
  // What name should be use for our cookie?
  //
  // We also include a special value within authentication cookies
  // to attempt to make it more secure.  There's a function defined
  // for generating MD5-checksum-style hex strings in NSSDropbox.php
  // that are nice for this purpose -- see NSSGenerateCookieSecret().
  //
  'cookieName'            => 'mydomain-dropbox-session',
  'cookieSecret'          => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
  'cookieTTL'             => 900,
  //
  // We'll use the LDAP authenticator with two of our LDAP servers
  // and the appropriate UD base DN.
  //
  'authenticator'         => 'LDAP',
  'authLDAPBaseDN'        => 'o=mydomain.org',
  'authLDAPServers'       => array(
                                'ldap1.mydomain.org',
                                'ldap2.mydomain.org'
                             ),
  //
  // Poor-man's administrator setup -- any of these usernames should
  // be granted special viewing privileges:
  //
  'authAdmins'            => array(
                                'user1',
                                'user2'
                             ),
  //
  // Contact into (HTML) that should be in the footer of each page:
  //
  'contactInfo'           => 'Dropbox Service &copy; 2007',
  //
  // Should the recipients be displayed on pickup pages?  This setting
  // is mainly there for security purposes, I suppose.  Admins will
  // always see the recipient list.
  //
  'showRecipsOnPickup'    => FALSE,
  //
  // Only allow HTTPS -- no transmission in the clear!
  //
  'demandHTTPS'           => TRUE
);

//
// This global array contains terms that will be replaced automatically
// throughout the interface:
//
$DROPBOX_DICTIONARY = array(
  'Authentication'        => 'Authentication',
  'Username'              => 'Username',
  'username'              => 'username',
  'username-regex'        => '/^([a-z0-9][a-z0-9\_\.]*)$/'
);

?>
