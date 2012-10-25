{include file="header.tpl"}

<p>The request for a Drop-off has been sent to {$toName} at {$toEmail}.</p>
<p>If the recipient wants to send files to you before their request arrives,
   they should</p>
<ol>
  <li>Go to <a href="{$zendToURL}">{$zendToURL}</a></li>
  <li>Select "Drop-off Files"</li>
  <li>Enter the request code "<b><tt>{$reqKey}</tt></b>"</li>
  <li>Click on the "Next" button</li>
</ol>
<p>You may close this window.</p>

{include file="footer.tpl"}
