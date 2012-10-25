#!/bin/sh

DBFILE=$1
PREFS=$2

if [ "x$DBFILE" = "x" -o "x$PREFS" = "x" ]; then
  echo Run me with the path to the ZendTo sqlite database file, and the path to the ZendTo preferences.php file
  exit 1
fi

TEMP=`basename $DBFILE`
TEXT="/tmp/${TEMP}.txt"
echo Dumping $DBFILE to $TEXT
echo '.dump' | sqlite "$DBFILE" > "$TEXT"

echo Now, press Ctrl-Z, then edit $TEXT to change the "CREATE TABLE dropoff"
echo statement:
echo Add a comma to the end of the \"created\" line, and
echo add a new line
echo '  note text'
echo immediately under the \"created\" line.
echo
echo Once you have done that, \"fg\" and press return twice.
read a

#echo Now to add the extra field to all of the existing drop-offs.
php fixDropoffTable.php "$PREFS" > /tmp/DropoffTable.txt
# Delete the dropoff table data and fix the recipients table data
perl -pi -e 's/^INSERT INTO dropoff.*$//i;' "$TEXT"
# s/^(INSERT INTO recipient.*)(\)\;\s*$)/$1,'\'\''$2/i;' "$TEXT"
cat /tmp/DropoffTable.txt >> "$TEXT"

echo Now to save a backup copy of $DBFILE and then create the new version.
mv $DBFILE ${DBFILE}.bak
sqlite "$DBFILE" < "$TEXT"

echo Setting the ownership and permissions.
chown --reference="${DBFILE}.bak" "$DBFILE"
chmod --reference="${DBFILE}.bak" "$DBFILE"

echo All done.
echo Do not worry if the new file is a bit smaller than the old one:
ls -ald ${DBFILE} ${DBFILE}.bak

exit 0

