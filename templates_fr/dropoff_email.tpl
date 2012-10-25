---AN ENGLISH MESSAGE FOLLOWS---

Ceci est un message automatisé envoyé par le service {#ServiceTitle#}. 
    
{$senderName} ({$senderEmail}) a partagé {if $fileCount eq 1}un fichier{else}{$fileCount}des fichiers{/if} avec vous.

Si vous faites confiance à l'expéditeur et si vous vous attendiez de recevoir un fichier de leur part, cliquez le lien suivant pour récupérer le fichier:

  {$zendToURL}pickup.php?claimID={$claimID}&claimPasscode={$claimPasscode}&emailAddr=__EMAILADDR__

Vous avez {$retainDays} jours pour récupérer le fichier, après lesquels le lien ci-dessous cessera de fonctionner.  

{if $note ne ""}Note de l'expéditeur:

{$note}

{/if}
Informations complètes à propos du partage:

    ID de partage:     {$claimID}
    Clé:               {$claimPasscode}
    Date du partage:   {$now}

    -- Expéditeur --
      Nom:             {$senderName}
      Organisation:    {$senderOrg}
      Adresse courriel:{$senderEmail}
      Adresse IP:      {$senderIP}  {$senderHost}
      
    -- Fichier{if $fileCount ne 1}s{/if} --
{for $i=1; $i<=$fileCount; $i++}{$f=$files[$i]}
      Nom:             {$f.name}
      Description:     {$f.description}
      Taille:          {$f.size}
      Type de contenu: {$f.type}

{/for}


-------

This is an automated message sent to you by the {#ServiceTitle#} service. 
    
{$senderName} ({$senderEmail}) has dropped-off {if $fileCount eq 1}a file{else}{$fileCount} files{/if} for you.

IF YOU TRUST THE SENDER, and are expecting to receive a file from them, you may choose to retrieve the drop-off by clicking the following link (or copying and pasting it into your web browser):

  {$zendToURL}pickup.php?claimID={$claimID}&claimPasscode={$claimPasscode}&emailAddr=__EMAILADDR__

You have {$retainDays} days to retrieve the drop-off; after that the link above will expire. If you wish to contact the sender, just reply to this email.

{if $note ne ""}The sender has left you a note:

{$note}

{/if}
Full information about the drop-off:

    ID de partage:     {$claimID}
    Clé:               {$claimPasscode}
    Date of Drop-Off:  {$now}

    -- Sender --
      Name:            {$senderName}
      Organisation:    {$senderOrg}
      Email Address:   {$senderEmail}
      IP Address:      {$senderIP}  {$senderHost}
      
    -- File{if $fileCount ne 1}s{/if} --
{for $i=1; $i<=$fileCount; $i++}{$f=$files[$i]}
      Name:            {$f.name}
      Description:     {$f.description}
      Size:            {$f.size}
      Content Type:    {$f.type}

{/for}
