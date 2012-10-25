This is an automated message sent to you by the {#ServiceTitle#} service. 
    
{$senderName} ({$senderEmail}) has dropped-off {if $fileCount eq 1}a file{else}{$fileCount} files{/if} for you.

IF YOU TRUST THE SENDER, and are expecting to receive a file from them, you may choose to retrieve the drop-off by clicking the following link (or copying and pasting it into your web browser):

  {$zendToURL}pickup.php?claimID={$claimID}&claimPasscode={$claimPasscode}&emailAddr=__EMAILADDR__

If you wish to contact the sender, just reply to this email.

{if $note ne ""}The sender has left you a note:

{$note}

{/if}
Full information about the drop-off:

    Claim ID:          {$claimID}
    Claim Passcode:    {$claimPasscode}
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
