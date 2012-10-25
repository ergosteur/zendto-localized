These are the instructions for Debian or Ubuntu.
What I would advise is getting hold of the source for your PHP5 for your
distro, then
1. Manually execute the commands in function make-patch().
2. Rebuild your PHP using either your packaging system,
   or just executing the given commands (for Ubuntu).
3. Copy the libphp5.so to /usr/lib/apache/modules or wherever all the
   Apache module *.so files are on your system.
4. You don't need to touch the /usr/bin/php binary or anything except the
   Apache PHP5 modules.

-- 
Jules 2010

#!/bin/bash -ex
##################################################################################
#
#  The default jaunty/karmic on 64bit OS with php5 does not work with HTTP POST of
#  files >2G.  This is instructions for making (and applying) the patch.
#  tracey aug 2009
#
##################################################################################


#  some devel. notes along the way....
#---jaunty 64bit:
#-stock nginx   (php-cgi) cannot POST large files    (bug -- this patch fixes)
#-stock apache2 (mod_php) CAN POST >2G files; **not** <2G (patch *should* fix)

#---jaunty 32bit:
#-segfaulted an entire (stock!) apache process on one curl 7.5G post attempt, wtf?
#-eventually was able to upload 7.5G file to apache2 + jaunty32 (*w/o* fixes)


UBU_NAME=`fgrep CODENAME /etc/lsb-release |cut -f2 -d=`; # eg: "jaunty"
PET=/petabox


##################################################################################
#
# to make "$UBU_NAME-64bit-post-large-files.patch"
#
##################################################################################
function make-patch()
{
cd;
# build and get existing ubuntu patches in place...
# NOTE: CTL-C break after the patches are done and it starts compiling...
apt-get -b source php5-cgi;
cp -pr php5*/ old/;

cd php5*;
perl -i -pe 's/int zend_atoi/long zend_atoi/' Zend/zend_operators.[ch];
perl -i -pe 's/\n/@@@@@@/g' Zend/zend_operators.c;
perl -i -pe 's/(long zend_atoi.*?)int retval/$1long retval/m' Zend/zend_operators.c;
perl -i -pe 's/@@@@@@/\n/g' Zend/zend_operators.c;

perl -i -pe 's/atoi\(content_length\)/atol(content_length)/' `find sapi -name '*.c'`

perl -i -pe 's/\(uint\)( SG\(request_info\))/$1/' `find sapi -name '*.c'`;

perl -i -pe 's/uint post_data_length, raw/uint IGNORE_post_data_length, IGNORE_raw/' main/SAPI.h;
perl -i -pe 's/} sapi_request_info/\tlong post_data_length, raw_post_data_length;\n} sapi_request_info/' main/SAPI.h;
perl -i -pe 's/int read_post_bytes/long read_post_bytes/'    main/SAPI.h;
perl -i -pe 's/int boundary_len=0, total_bytes=0/long total_bytes=0; int boundary_len=0/' main/rfc1867.c;
cd ..;
diff -u -r old/ php5*/ >| $PET/sw/lib/${UBU_NAME}-64bit-post-large-files.patch;
}


##################################################################################
#
# to make the patched php5-cgi debian packages
#
##################################################################################
# make-patch;


if [ "$UBU_NAME" != "jaunty" ]; then
    # hmm i would have expected these all to be in place already...
    apt-get install debhelper bison chrpath freetds-dev libcurl4-openssl-dev libedit-dev libgd2-xpm-dev libgmp3-dev libmhash-dev libpam0g-dev libpspell-dev librecode-dev libsasl2-dev libsnmp-dev libsqlite0-dev libt1-dev libtidy-dev re2c unixodbc-dev;
fi;

mkdir -p /tmp/n/;
cd /tmp/n/;
apt-get source  php5-cgi;
cd php5*;
cp   $PET/sw/lib/${UBU_NAME}-64bit-post-large-files.patch   debian/patches/;
echo             ${UBU_NAME}-64bit-post-large-files.patch >>debian/patches/series;


dpkg-buildpackage -rfakeroot -uc -b -j2;
cd ..;

# to unpack and check if you wish:
# dpkg-deb -c ../php*cgi*.deb;
# mkdir unpackt; cd unpackt; dpkg-deb --fsys-tarfile ../php*cgi*.deb | tar xvf -

# copy the bug-fixed binary to petabox tree:
cp /tmp/n/php5*/cgi-build/sapi/cgi/cgi-bin.php5 $PET/sw/bin/php-cgi.$UBU_NAME;
