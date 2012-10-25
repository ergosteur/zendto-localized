%define version 1.0
%define release 1
%define name    zendto-repo

Name:        %{name}
Version:     %{version}
Release:     %{release}
Summary:     Yum Repository Setup for ZendTo
Group:       Networking/WWW
License:     GPL
Vendor:      Julian Field http://zend.to
Packager:    Julian Field <support@Zend.To>
URL:         http://zend.to/
BuildRoot:   %{_tmppath}/%{name}-root
BuildArchitectures: noarch

%description
This configures yum so that it will install the package "zendto".
%install
mkdir -p $RPM_BUILD_ROOT
mkdir -p ${RPM_BUILD_ROOT}/etc/yum.repos.d
cat <<EOF > ${RPM_BUILD_ROOT}/etc/yum.repos.d/zendto.repo
[ZendTo]
name=ZendTo
baseurl=http://zend.to/yum/noarch
enabled=1
gpgcheck=0
EOF
chmod 0644 ${RPM_BUILD_ROOT}/etc/yum.repos.d/zendto.repo

%clean
rm -rf ${RPM_BUILD_ROOT}

%files
%attr(644,root,root) /etc/yum.repos.d/zendto.repo

%changelog
* Sat Mar 19 2011 Julian Field <jules@zend.to>
- 1st edition
