
Upgrading to use the new "loginFailMax" or "loginFailTime" settings means
you have to add a table to the database. This is done by running the
"./addLoginlogTable.php" command in this directory.

===============================================================================

Upgrading to use the new "emailDomainRegexp" setting with a filename
means you have to add a table to the database. This is done by running
the "./addRegexpTable.php" command in this directory.

===============================================================================

Upgrading to use the new "Local" authenticator means you have to add a
table to the database. This is done by running the "./addUserTable.php"
command in this directory.

===============================================================================


Upgrading your database from the udel.edu version of "Dropbox" to my new
"Dropoff" releases.

1. Stop Apache
==============
Do this by whatever method is right for your system.
You don't want your users messing with the database file while you are
upgrading it.

2. Backup Your dropbox.sqlite File
==================================
In the www directory of your Dropbox/Dropoff installation, the
preferences.php file will tell you where your dropbox.sqlite file is
stored. Take a very safe copy of this, so you can start again if anything
goes wrong.

3. Add the "AuthTable" Table to the Database
============================================
Do this by running
  php addAuthTable.php /opt/dropbox/NSSDropbox-261/www/preferences.php
where you should replace the last command-line argument with the full
absolute path to your Dropoff preferences.php file.

4. Add the "note" Column to the "dropoff" Table in the Database
===============================================================
Do this by running
   sh addNotesColumn.sh /dropbox/dropbox.sqlite /opt/dropbox/NSSDropbox-261/www/preferences.php
where you should replace the first argument with the full path of your
dropbox.sqlite file (you found this in step 2, above). You should replace
the second argument with the full path of your Dropoff preferences.php
file (you found this in step 3, above).

5. Done
=======
You have now finished upgrading the Dropbox/Dropoff database.

