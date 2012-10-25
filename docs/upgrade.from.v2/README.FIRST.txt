Upgrading your database from the udel.edu version of "Dropbox" to my new
"ZendTo" releases.

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
goes wrong. You need to import your www/preferences.php into the new
config/preferences.php file.

3. Rename Your dropbox.sqlite File
==================================
mv /var/zendto/dropbox.sqlite /var/zendto/zendto.sqlite

4. Add the "AuthTable" Table to the Database
============================================
Do this by running
  php addAuthTable.php /opt/zendto/config/preferences.php
where you should replace the last command-line argument with the full
absolute path to your ZendTo preferences.php file.

5. Add the "note" Column to the "dropoff" Table in the Database
===============================================================
Do this by running
   sh addNotesColumn.sh /var/zendto/zendto.sqlite /opt/zendto/config/preferences.php
where you should replace the first argument with the full path of your
zendto.sqlite file (you found this in step 2, above). You should replace
the second argument with the full path of your ZendTo preferences.php
file (you found this in step 2, above).

6. Done
=======
You have now finished upgrading the Dropbox/ZendTo database.
Next look in the /opt/zendto/config directory for the new preferences.php
and zendto.conf files, which will need customising for your site.
