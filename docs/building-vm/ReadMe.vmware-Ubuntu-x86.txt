Welcome to the Virtual Machine distribution of ZendTo.

This is a 32-bit Ubuntu 10 server running ZendTo.

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

Give the new VM a name, such as "ZendTo-x86".
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

You can log in at the text console with username "zendto" and password
"zendto". Once you have logged in for the first time, please change this
password with the "passwd" command.

You can become the "root" user with the command
    sudo su -

First put your own settings into /etc/postfix/main.cf. The ones you need
to change are labelled with "ZendTo" in that file.
Then put *only* your internet domain name into /etc/mailname. This file
must only be 1 line.
Then put the short hostname (default is "zendto") in /etc/hostname.
Then edit /etc/hosts and put in your full hostname in the obvious places.

Then set your timezone using the guide at zend.to/timezone.php.

Because users can log in with their username and password, you should
set up an SSL certificate for the web server, Apache. You don't *have*
to do this, but it is strongly advised.
You can get a certificate for free from www.startssl.com.
You need to place the "key" (without any passphrase!) in
     /etc/ssl/private/zendto-ssl.key
You need to place the certificate file in
     /etc/ssl/certs/zendto-ssl.crt
***
   IF your zendto-ssl.key file has a passphrase attached, you can remove it
   like this:
   mv zendto-ssl.key zendto-ssl.pass.key
   openssl rsa -in zendto-ssl.pass.key -out zendto-ssl.key
   - enter your passphrase once when prompted.
***
After you have done that, add a line to /etc/apache2/sites-enabled/000-zendto
just below the "<VirtualHost" line that says this:
     Redirect / https://zendto.yourdomain.com/
where your should put the full hostname of the ZendTo server.

If you wish to use MyZendTo as well, then you will need to just create
another website on your server whose DocumentRoot is set to
"/opt/zendto/myzendto.www". Get everything else working first before you
start trying this as well.

Then you need to setup /opt/zendto/config/* for your site.

