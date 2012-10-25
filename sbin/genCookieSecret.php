#!/usr/bin/php
<?PHP

include "/opt/zendto/lib/NSSUtils.php";

printf("You must add this entry to your preferences.php file:\n\n  'cookieSecret' => '%s'\n\n",NSSGenerateCookieSecret());

?>
