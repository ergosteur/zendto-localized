These are the utilities for maintaining the local SQL-based authentication
table of users and passwords.

You can get the usage details of each script by just running it with no
parameters on the command-line.

adduser.php     - Add a new user to the table
deleteuser.php  - Remove a user from the table
listusers.php   - List all the details of the users (except their passwords)
                  Run with "--help" to describe the output format
setpassword.php - Change the password for a user
setquota.php    - Change the quota for a user (MyZendTo only)
unlockuser.php  - Unlock a user who has had too many failed logins

To save you having to put the full location of the ZendTo preferences.php
file in every command, you can set the shell environment variable
ZENDTOPREFS instead, like this for example:
    export ZENDTOPREFS=/opt/zendto/config/preferences.php
Then the commands will automatically find the preferences.php file.
