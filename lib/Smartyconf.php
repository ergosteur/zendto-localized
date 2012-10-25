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

// All the code necessary to get Smarty running. This is included once at
// the top of each www php page.
// Set-up include path
$app_root = realpath(dirname(__FILE__).'/../');
include_once(NSSDROPBOX_LIB_DIR."smarty/Smarty.class.php");

// Set-up Smarty template object
$smarty = new smarty;
$smarty->template_dir = $app_root . '/templates';
$smarty->compile_dir  = $app_root . '/templates_c';
$smarty->config_dir   = $app_root . '/config';
$smarty->cache_dir    = $app_root . '/cache';
$smarty->plugins_dir  = $app_root.'/lib/smarty/plugins';

$smarty->configLoad('zendto.conf');

// Set error reporting to fairly quiet
$smarty->error_reporting = E_ALL & ~E_NOTICE;

// This contains the list of all the errors on the page
$pageErrorList = array();
$smarty->assignByRef('errors', $pageErrorList);

// Change comment on these when you're done developing to improve performance
// $smarty->force_compile = true;
// $smarty->caching = true; 

?>
