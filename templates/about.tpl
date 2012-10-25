{include file="header.tpl"}

<div style="text-align:justify;"><a href="images/dropbox-icon.pdf"><img src="images/dropbox-icon.png" align="left" border="0" alt="[dropbox]"/></a>
<h4>About the {#ServiceTitle#} Service...</h4>

Email messages with large attachments can wreak havoc on email servers and
end-users' computers.  Downloading such email messages can take hours on
a slow Internet connection and block any sending or receiving of messages
during that time.  In some cases, the download will fail repeatedly,
breaking the recipient's ability to receive mail at all.  Also, Internet
email clients add considerably to the size of the file being sent. For
example, saving an Outlook message with an attachment adds
up to 40% to the file's size. To share files larger than 1MB, use the
{#ServiceTitle#} to temporarily make a file (or files) available to
another user across the Internet, in a secure and efficient manner.<br/>
<br/>
There are two distinct kinds of users that will be accessing the
{#ServiceTitle#} system:  <i>inside</i> users, who are associated
with {#OrganizationType#} running the service, and <i>outside</i> users,
which encompasses the rest of the Internet.<br/>
<br/>
An <i>inside</i> user is allowed to send a drop-off
to anyone, whether he or she be an <i>inside</i>
or <i>outside</i> user.  An <i>outside</i> user is only allowed to
send a drop-off to an <i>inside</i> user.
That prompts the question:  what is a drop-off?

<div style="border:1px solid #C0C0C0;background:#E0E0E0;margin:12px;padding:4px;">
  <b><i>drop-off</i></b>:  one or more files uploaded to the {#ServiceTitle#} as a single item for delivery to a person or people
</div>

There are two ways in which a user can dropoff multiple files at once:

<ul>
  <li>Attach each file individually on the dropoff page</li>
  <li>Archive and compress the files into a single package and attach
  the resulting archive file on the dropoff page.  There are many ways
  to archive and compress files:
    <ul>
      <li>Mac users can select the files in the Finder and <i>Compress</i> (see the <i>File</i> menu)</li>
      <li>Windows users can create a "compressed folder" or use WinZip</li>
      <li>Linux/Unix users, give the <tt>zip</tt> utility a try</li>
    </ul>
  </li>
</ul>

<b>Creating a Drop-off</b><br/>
<blockquote style="text-align:justify;border-bottom:2px dotted #C0C0C0;">
When a user creates a drop-off, they enter some identifying
information about themself (name, organisation, and email
address); identifying information about the recipient(s) (name and email
address); and choose what files should be uploaded to make the drop-off.
If the files are successfully uploaded, an email is sent to the recipient(s)
explaining that a drop-off has been made.  This email also provides a link
to access the drop-off.
Other information (the Internet address and/or
omputer name from which the drop-off was created, for example) is retained,
to help the recipient(s) check the identity of the sender.<br/>
<br/>
</blockquote>

<b>Making a Pick-up</b><br/>
<blockquote style="text-align:justify;border-bottom:2px dotted #C0C0C0;">
There are two ways to pick-up files that have been dropped-off:
<ul>
  <li>All users can click on the link provided in the notification email they were sent.</li>
  <li>An inside user, once logged-in to the system, can display their "Inbox" which is a list of all drop-offs waiting for them.  Once logged-in, an inside user is able to access drop-offs without needing the email message.</li>
</ul>
When viewing a drop-off, the user will see quite a few things:
<ul>
  <li>The list of files that were uploaded</li>
  <li>The sender and recipient information that the sender entered when the drop-off was created</li>
  <li>The computer name and/or address from which the drop-off was created</li>
  <li>Optionally a list of pick-ups that have been made</li>
</ul>
The recipient has {$keepForDays} days to pick-up the files.  Each night, drop-offs that are older than {$keepForDays} days are removed from the system.<br/>
<br/>
</blockquote>

Please note that the uploaded files are scanned for viruses, but the
recipient should still exercise as much caution in downloading and
opening them as is appropriate.  This can be as easy as verifying with
the sender mentioned in the notification email that he or she indeed made
the drop-off.  One can also check the computer name/address that was
logged when the drop-off was created, to be sure that it is appropriate
to the sender's Internet domain; IP addresses <i>can</i> be faked, though,
so the former identity verification is really the most reliable.<br/>
<br/>

</div>

<hr/>

<h4>Resumable Downloading of Files</h4>

Most web browsers support <i>resumable downloads</i>.  Imagine this
scenario:  you're sitting at your local coffee shop, downloading a 100 MByte
PDF that a student uploaded to {#ServiceTitle#} for you.
Suddenly, someone a few tables away starts watching the latest movie
trailer (well, attempting to, anyway) and your wireless connection drops
&mdash; you were 95MB into the download, and now you have to start over!
Not so, if your browser supports <i>resumable downloads</i>; in which
case, the browser requests only the remaining 5MB of the file.<br/>
<br/>
{#ServiceTitle#} features support for the server-side components
of <i>resumable download</i> technology under the HTTP 1.1 standard.
<br/>

<hr/>

<h4>Size Limitations on Uploads</h4>

Being able to upload files larger than 2 GB depends on the browser being used.  The following major browsers have been tested:<br>
<br>

<center>
<table border="1" cellpadding="4" cellspacing="1">
  <tr style="background-color:#2F2F4F;color:white;"><th>&nbsp;</th><th>Browser</th><th>Uploads &gt; 2 GB?</th></tr>
  <tr>
    <td rowspan="4" style="background-color:#2F2F4F;color:white;text-align:center;">M<br/>A<br/>C</td>
    <td><a href="http://www.apple.com/safari">Safari</a> 5.x</td>
    <td style="text-align:center;font-weight:bold;color:#00A000;">YES</td>
  </tr>
  <tr>
    <td><a href="http://www.google.com/chrome">Chrome</a></td>
    <td style="text-align:center;font-weight:bold;color:#00A000;">YES</td>
  </tr>
  <tr>
    <td><a href="http://www.mozilla.com/en-US/firefox/">Firefox</a> 5.x</td>
    <td style="text-align:center;font-weight:bold;color:#00A000;">YES</td>
  </tr>
  <tr>
    <td><a href="http://www.opera.com/">Opera</a> 10.x</td>
    <td style="text-align:center;font-weight:bold;color:#00A000;">YES</td>
  </tr>
  <tr>
    <td rowspan="5" style="background-color:#2F2F4F;color:white;text-align:center;">P<br/>C</td>
    <td><a href="http://www.microsoft.com/ie">Internet Explorer</a> 6</td>
    <td style="text-align:center;font-weight:bold;color:#A00000;">NO</td>
  </tr>
  <tr>
    <td><a href="http://www.microsoft.com/ie">Internet Explorer</a> 7</td>
    <td style="text-align:center;font-weight:bold;color:#A00000;">NO</td>
  </tr>
  <tr>
    <td><a href="http://www.microsoft.com/ie">Internet Explorer</a> 9 64-bit</td>
    <td style="text-align:center;font-weight:bold;color:#00A000;">YES</td>
  </tr>
  <tr>
    <td><a href="http://www.mozilla.com/en-US/firefox/">Firefox</a> 5.x</td>
    <td style="text-align:center;font-weight:bold;color:#00A000;">YES</td>
  </tr>
  <tr>
    <td><a href="http://www.opera.com/">Opera</a> 10</td>
    <td style="text-align:center;font-weight:bold;color:#00A000;">YES</td>
  </tr>
</table>
</center>
<br/>

The {#ServiceTitle#} software itself has limits on the amount of
data that can be uploaded in a single dropoff.  Even for browsers
that support uploads larger than 2 GB, dropoffs may not exceed
{$maxFileSize} per file, or {$maxDropoffSize} total for the entire dropoff.<br/>
<br/>

<br/>If you are having the following issues when dropping-off or picking-up a large file:
<ul>
  <li>Your browser reports a bad or broken connection after downloading a significant portion of the file</li>
  <li>An error page is displayed that indicates you dropped-off no files</li>
</ul>
then you are most likely connected to the Internet via a connection too
slow to move the amount of data in a timely fashion.  Your computer has
approximately 2 hours to fully send or receive a drop-off.<hr/>

<p style="font-size:10px;" align="left"><a href="http://www.php.net/"><img src="images/PHP5.png" align="right" border="0" alt="[php5]"/></a>
Based upon the original Perl UD Dropbox software written by
Doke Scott.  Version {$ztVersion} has been developed by <a
href="mailto:Jules@Zend.To">Julian Field</a>.
</p>

{include file="footer.tpl"}
