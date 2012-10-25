Welcome to the Virtual Machine distribution of ZendTo.

This is a 32-bit CentOS 5 server running ZendTo.

You have already unpacked the zip file, good.

I will assume you are using VMWare Workstation in this file, so you may
need to adapt the terms a bit for your own system.

Run VMWare Workstation from the Start menu.

Choose File / Import or Export...

Choose "Other" for the type of source.

Browse to the .vmx file in this directory.
Ignore any errors any failing to configure the source image.

Convert all disks and maintain size.

Choose "Other Virtual Machine" for the destination type.

Give the new VM a name, such as "ZendTo-CentOS-x86".
Set the location to the directory where you want to store the new VM.
You may wish to create a new directory in your Documents folder for this.
Choose "VMware Workstation" version "6.5-7.x" for the type of VM to create.

"Import and convert (full-clone)" for your disks.
"Allow virtual disk files to expand" for the Disk Allocation.

Choose 1 NIC, on a Bridged network, connected at power on.

Confirm everything else in the wizard, and it will start building your VM.

Power on your new VM !

---------------------------------------------------------------------------

It will set its network settings automatically using DHCP.

You can log in at the text console with username "root" and password
"zendto". Once you have logged in for the first time, please change this
password with the "passwd" command.

If you need to change the keyboard type, use a command like "loadkeys de"
which would load the German keyboard map.

First put your own settings into /etc/mail/sendmail.mc. The ones you need
to change are labelled with "ZendTo" in that file.

Then set your timezone using the guide at zend.to/timezone.php.

Then put your hostname in /etc/httpd/conf/httpd.conf. Look for the line
containing "zendto" and replace it with your own hostname.

Because users can log in with their username and password, you should
setup an SSL certificate for the web server, Apache. You don't *have*
to do this, but it is strongly advised.
You can get a certificate for free from www.startssl.com.
There is plenty of documentation on the internet covering the subject
of creating a https site on Apache, you can do it all in
/etc/httpd/conf/httpd.conf.
Make sure your SSL key has no passphrase set.
You can remove it like this:
   mv zendto-ssl.key zendto-ssl.pass.key
   openssl rsa -in zendto-ssl.pass.key -out zendto-ssl.key
   - enter your passphrase once when prompted.

Then you need to setup /opt/zendto/config/* for your site.

If you wish to use MyZendTo as well, then you will need to just create
another website on your server whose DocumentRoot is set to
"/opt/zendto/myzendto.www". Get everything else working first before you
start trying this as well.

Then reboot to be sure everything is setup correctly before you try it.

