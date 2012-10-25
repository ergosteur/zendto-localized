%define version 4.10
%define release 5
%define name    zendto

%define is_fedora %(test -e /etc/fedora-release && echo 1 || echo 0)

Name:        %{name}
Version:     %{version}
Release:     %{release}
Summary:     Web-based File Transfer and Storage System
Group:       Networking/WWW
License:     GPL
Vendor:      Julian Field www.zend.to
Packager:    Julian Field <ZendTo@Zend.To>
URL:         http://zend.to/
%if %is_fedora
Requires:    php >= 5, clamav, httpd, mysql-server, clamav-server
%else
Requires:    php >= 5, clamav, httpd, mysql-server, clamd
%endif
Source:      ZendTo-%{version}-%{release}.tgz
BuildRoot:   %{_tmppath}/%{name}-root
BuildArchitectures: noarch

%description
ZendTo is a web-based package that allows for the easy transfer of large
files both into and out of your organisation, without users outside
your organisation needing any usernames or passwords to be able to send
files to you. It also of couse allows your own internal users to send
files to anyone with an email address. All submissions are scanned for
viruses but are otherwise unrestricted.

It cannot be used by external users to distribute files to other external
users, and therefore cannot be abused to distribute illegal software or
other files outside of your organisation. It also cannot be abused by
outside spammers to automatically "spam" everyone in your organisation
with file notifications.

It is specifically designed to look after itself once installed and
maintain itself automatically. Customising the user interface is very
simple.

It is very easy to use, and is effectively a modern web-based replacement
for old "anonymous ftp" methods.

It also now includes an additional package MyZendTo which is rather like
an easy web-based filestore, in which you can send files to other people
if you wish to, but they are primarily there for your own use.
%prep
#%setup -n ZendTo-%{version}-%{release}

%build

%install
mkdir -p $RPM_BUILD_ROOT
mkdir -p ${RPM_BUILD_ROOT}/opt
tar xzf ${RPM_SOURCE_DIR}/ZendTo-%{version}-%{release}.tgz -C ${RPM_BUILD_ROOT}/opt
mv ${RPM_BUILD_ROOT}/opt/ZendTo-%{version}-%{release} ${RPM_BUILD_ROOT}/opt/zendto
chown apache ${RPM_BUILD_ROOT}/opt/zendto/cache
chown apache ${RPM_BUILD_ROOT}/opt/zendto/templates_c
chown apache ${RPM_BUILD_ROOT}/opt/zendto/myzendto.templates_c
chgrp apache ${RPM_BUILD_ROOT}/opt/zendto/cache
chgrp apache ${RPM_BUILD_ROOT}/opt/zendto/templates_c
chgrp apache ${RPM_BUILD_ROOT}/opt/zendto/myzendto.templates_c
chmod g+w    ${RPM_BUILD_ROOT}/opt/zendto/cache
chmod g+w    ${RPM_BUILD_ROOT}/opt/zendto/templates_c
chmod g+w    ${RPM_BUILD_ROOT}/opt/zendto/myzendto.templates_c

chmod +x     ${RPM_BUILD_ROOT}/opt/zendto/sbin/UPGRADE/*php
chmod +x     ${RPM_BUILD_ROOT}/opt/zendto/sbin/UPGRADE/*sh
chmod +x     ${RPM_BUILD_ROOT}/opt/zendto/bin/*php

mkdir -p ${RPM_BUILD_ROOT}/var/zendto
chgrp apache ${RPM_BUILD_ROOT}/var/zendto
chmod g+w ${RPM_BUILD_ROOT}/var/zendto

mkdir -p ${RPM_BUILD_ROOT}/etc/cron.d
echo '5 0 * * * root /usr/bin/php /opt/zendto/sbin/cleanup.php /opt/zendto/config/preferences.php' > ${RPM_BUILD_ROOT}/etc/cron.d/zendto
echo '2 2 * * * root /usr/bin/php /opt/zendto/sbin/rrdInit.php /opt/zendto/config/preferences.php' >> ${RPM_BUILD_ROOT}/etc/cron.d/zendto
echo '2 4 * * * root /usr/bin/php /opt/zendto/sbin/rrdUpdate.php /opt/zendto/config/preferences.php' >> ${RPM_BUILD_ROOT}/etc/cron.d/zendto

mkdir -p ${RPM_BUILD_ROOT}/etc/profile.d
echo '[ -f /opt/zendto/config/preferences.php ] && export ZENDTOPREFS=/opt/zendto/config/preferences.php' > ${RPM_BUILD_ROOT}/etc/profile.d/zendto.sh
echo '# zendto initialization script (csh)' > ${RPM_BUILD_ROOT}/etc/profile.d/zendto.csh
echo 'if ( -f /opt/zendto/config/preferences.php ) then' >> ${RPM_BUILD_ROOT}/etc/profile.d/zendto.csh
echo '  setenv ZENDTOPREFS /opt/zendto/config/preferences.php' >> ${RPM_BUILD_ROOT}/etc/profile.d/zendto.csh
echo 'endif' >> ${RPM_BUILD_ROOT}/etc/profile.d/zendto.csh
chmod a+rx ${RPM_BUILD_ROOT}/etc/profile.d/zendto.sh
chmod a+rx ${RPM_BUILD_ROOT}/etc/profile.d/zendto.csh

%clean
rm -rf ${RPM_BUILD_ROOT}

%pre

%post
# Construct /var/zendto
mkdir -p /var/zendto
chown root.apache /var/zendto
chmod 0775 /var/zendto
for F in incoming dropoffs rrd library
do
  if [ \! -d /var/zendto/$F/ ]; then
    mkdir -p /var/zendto/$F
    chown apache.apache /var/zendto/$F
    chmod 0755 /var/zendto/$F
  fi
done
if [ \! -f /var/zendto/zendto.log ]; then
  :> /var/zendto/zendto.log
  chown apache.apache /var/zendto/zendto.log
  chmod u=rw,g=rw,o=r /var/zendto/zendto.log
fi
cp /opt/zendto/www/images/notfound.png /var/zendto/rrd/notfound.png
chmod a+r /var/zendto/rrd/notfound.png

# Clean the caches in case Smarty has been upgraded
rm -rf /opt/zendto/templates_c/* >/dev/null 2>&1
rm -rf /opt/zendto/myzendto.templates_c/* >/dev/null 2>&1
rm -rf /opt/zendto/cache/* >/dev/null 2>&1

service crond reload

if [ $1 = 1 ]; then
  # We are being installed, not upgraded (that would be 2)
  echo
  echo Now add a new website to your Apache configuration with the
  echo DocumentRoot set to "/opt/zendto/www/".
  echo
  echo For technical support, please go to www.zend.to.
  echo
fi

%preun
if [ $1 = 0 ]; then
  # We are being deleted, not upgraded
  service crond reload
  echo 'You can delete all the files created by ZendTo by deleting the'
  echo 'directory /var/zendto.'
fi
exit 0

%postun
if [ "$1" -ge "1" ]; then
  # We are being upgraded or replaced, not deleted
  # Clean the caches in case Smarty has been upgraded
  rm -rf /opt/zendto/templates_c/* >/dev/null 2>&1
  rm -rf /opt/zendto/myzendto.templates_c/* >/dev/null 2>&1
  rm -rf /opt/zendto/cache/* >/dev/null 2>&1
  service crond reload
  echo 'Please ensure your /opt/zendto/config/* files are up to date.'
fi
exit 0

%files
%attr(755,root,root) %dir /opt/zendto
%config(noreplace) %attr(775,apache,apache) %dir /opt/zendto/cache
%config(noreplace) %attr(775,apache,apache) %dir /opt/zendto/templates_c
%config(noreplace) %attr(775,apache,apache) %dir /opt/zendto/myzendto.templates_c
/opt/zendto/cache/This.Dir.Must.Be.Writeable.By.Apache
/opt/zendto/templates_c/This.Dir.Must.Be.Writeable.By.Apache
/opt/zendto/myzendto.templates_c/This.Dir.Must.Be.Writeable.By.Apache
/opt/zendto/lib
/opt/zendto/www
%config(noreplace) %attr(755,root,root)     %dir /opt/zendto/www/css
%config(noreplace) %attr(755,root,root)     %dir /opt/zendto/www/images/swish
/opt/zendto/myzendto.www
%config(noreplace) %attr(755,root,root)     %dir /opt/zendto/myzendto.www/css
%doc /opt/zendto/docs
%doc /opt/zendto/README
%doc /opt/zendto/GPL.txt
%doc /opt/zendto/ChangeLog

/opt/zendto/sql

%attr(755,root,root) %dir /opt/zendto/config
%config(noreplace) %attr(644,root,apache) /opt/zendto/config/zendto.conf
%config(noreplace) %attr(640,root,apache) /opt/zendto/config/preferences.php

%attr(755,root,root) %dir /opt/zendto/templates
%config(noreplace) %attr(644,root,root) /opt/zendto/templates/about.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates/claimid_box.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates/delete.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates/dropoff_email.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates/dropoff_list.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates/error.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates/footer.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates/functions.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates/header.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates/log.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates/login.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates/logout.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates/main_menu.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates/new_dropoff.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates/no_download.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates/pickup_email.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates/pickup_list_all.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates/pickup_list.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates/pickupcheck.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates/progress.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates/request_email.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates/request_sent.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates/request.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates/resend.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates/show_dropoff.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates/stats.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates/unlock.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates/verify_email.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates/verify_sent.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates/verify.tpl
%attr(755,root,root) %dir /opt/zendto/templates-v3
%config(noreplace) %attr(644,root,root) /opt/zendto/templates-v3/about.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates-v3/claimid_box.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates-v3/delete.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates-v3/dropoff_email.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates-v3/dropoff_list.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates-v3/error.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates-v3/footer.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates-v3/functions.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates-v3/header.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates-v3/login.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates-v3/logout.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates-v3/main_menu.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates-v3/new_dropoff.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates-v3/no_download.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates-v3/pickup_email.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates-v3/pickup_list_all.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates-v3/pickup_list.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates-v3/progress.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates-v3/request.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates-v3/request_email.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates-v3/request_sent.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates-v3/show_dropoff.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates-v3/stats.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates-v3/unlock.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates-v3/verify_email.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates-v3/verify_sent.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/templates-v3/verify.tpl


%attr(755,root,root) %dir /opt/zendto/myzendto.templates
%config(noreplace) %attr(644,root,root) /opt/zendto/myzendto.templates/about.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/myzendto.templates/claimid_box.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/myzendto.templates/delete.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/myzendto.templates/dropoff_email.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/myzendto.templates/error.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/myzendto.templates/footer.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/myzendto.templates/functions.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/myzendto.templates/header.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/myzendto.templates/log.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/myzendto.templates/login.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/myzendto.templates/logout.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/myzendto.templates/new_dropoff.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/myzendto.templates/no_download.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/myzendto.templates/pickup_email.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/myzendto.templates/pickup_list_all.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/myzendto.templates/pickup_list.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/myzendto.templates/progress.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/myzendto.templates/show_dropoff.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/myzendto.templates/stats.tpl
%config(noreplace) %attr(644,root,root) /opt/zendto/myzendto.templates/unlock.tpl

%attr(755,root,root) %dir /opt/zendto/sbin
%attr(755,root,root) /opt/zendto/sbin/cleanup.php
%attr(755,root,root) /opt/zendto/sbin/stats.php
%attr(755,root,root) /opt/zendto/sbin/genCookieSecret.php
%attr(755,root,root) /opt/zendto/sbin/rrdInit.php
%attr(755,root,root) /opt/zendto/sbin/rrdUpdate.php
%attr(755,root,root) /opt/zendto/sbin/setphpini.pl

%attr(755,root,root) %dir /opt/zendto/sbin/UPGRADE
%doc /opt/zendto/sbin/UPGRADE/README.FIRST.txt
%attr(755,root,root) /opt/zendto/sbin/UPGRADE/addAuthTable.php
%attr(755,root,root) /opt/zendto/sbin/UPGRADE/addLoginlogTable.php
%attr(755,root,root) /opt/zendto/sbin/UPGRADE/addNotesColumn.sh
%attr(755,root,root) /opt/zendto/sbin/UPGRADE/addReqTable.php
%attr(755,root,root) /opt/zendto/sbin/UPGRADE/addUserTable.php
%attr(755,root,root) /opt/zendto/sbin/UPGRADE/addRegexpsTable.php
%attr(755,root,root) /opt/zendto/sbin/UPGRADE/fixDropoffTable.php

%attr(755,root,root) %dir /opt/zendto/bin
%attr(755,root,root) /opt/zendto/bin/adduser.php
%attr(755,root,root) /opt/zendto/bin/deleteuser.php
%attr(755,root,root) /opt/zendto/bin/listusers.php
%attr(755,root,root) /opt/zendto/bin/setpassword.php
%attr(755,root,root) /opt/zendto/bin/setquota.php
%attr(755,root,root) /opt/zendto/bin/unlockuser.php
%doc /opt/zendto/bin/README.txt

/etc/cron.d/zendto
%attr(755,root,root) /etc/profile.d/zendto.sh
%attr(755,root,root) /etc/profile.d/zendto.csh

%changelog
* Thu Dec 08 2011 Julian Field <jules@zend.to>
- Added var library directory
* Thu Aug 11 2011 Julian Field <jules@zend.to>
- Added files for Resend functionality
* Sat Jul 16 2011 Julian Field <jules@zend.to>
- Updated UI for MyZendTo, including quota support
* Fri Apr 15 2011 Julian Field <jules@zend.to>
- Added more dependencies, wish CentOS would release v6!
* Wed Mar 30 2011 Julian Field <jules@zend.to>
- Moved existing templates to templates-v3 and added new templates
* Mon Feb 21 2011 Julian Field <jules@zend.to>
- Added "Send a Request"
* Wed Feb 09 2011 Julian Field <jules@zend.to>
- Added progress bars
* Fri Aug 06 2010 Julian Field <jules@zendto.com>
- Added profile.d files
* Tue Jul 27 2010 Julian Field <jules@zendto.com>
- Added addLoginlogTable.php and unlockuser.php
* Sat Jul 24 2010 Julian Field <jules@zendto.com>
- Added MyZendTo application to the package
* Sun Jul 18 2010 Julian Field <jules@zendto.com>
- Added zendto/bin and all Local Authenticator files
* Thu Jul 08 2010 Julian Field <jules@zendto.com>
- 1st edition

