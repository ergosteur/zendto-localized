{include file="header.tpl"}

<p>Votre demande de partage de fichier a été envoyé à {$toName} ({$toEmail}).</p>
<p>Si vous ne désirez pas attendre la réception du courriel de demande, suivez les étapes suivantes:</p>
<ol>
  <li>Allez à <a href="{$zendToURL}">{$zendToURL}</a></li>
  <li>Sélectionnez "Créer un partage"</li>
  <li>Entrez le code de demande "<b><tt>{$reqKey}</tt></b>"</li>
  <li>Cliquez sur le bouton pour valider</li>
</ol>
<p>Vous pouvez fermer cette fenêtre.</p>
<hr/>
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
