{$toName}

Ceci est une demande de la part de {$fromName}{if $fromOrg}, {$fromOrg}{/if}.

Veuillez cliquer le lien ci-dessous et téléchargez le fichier que je vous ai demandé
{if $note}Plus d'information est disponible dans la note suivante.{/if}

{$URL}

Si vous désirez contacter {$fromName}, répondez à ce courriel.

{if $note}* Note *
{$note}
{/if}

-------

{$toName}

This is a request from {$fromName}{if $fromOrg} of {$fromOrg}{/if}.

Please click on the link below and drop off the file or files I have requested.
{if $note}More information is in the note below.{/if}

{$URL}

If you wish to contact {$fromName}, just reply to this email.

{if $note}* Note *
{$note}
{/if}

-- 
{$fromName}
{$fromEmail}
{$fromOrg}
