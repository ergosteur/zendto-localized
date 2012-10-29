{include file="header.tpl"}

{if $cameFromEmail}
<h4>Veuillez entrer l'ID de partage et la clé que vous avez reçu par courriel.<br/>Please enter the claim id and passcode that were emailed to you.
{else}
<h4>Veuillez entrer l'ID de partage et la clé.<br/>Please enter the claim id and passcode.
{/if}
{if $isAuthorizedUser}
Si l'expéditeur vous a donné un clé pour le partage, veuillez<br/>If the sender gave you a passcode for the claim, please enter it.
{/if}
</h4>

<center>
  <form name="pickup" method="post" action="{$zendToURL}pickup.php">
    <input type="hidden" name="auth" value="{$auth}"/>
    <table class="UD_form" cellpadding="4">
<!--
      <tr class="UD_form_header">
        <td colspan="2">Téléchargement de fichier/File Pick-Up</td>
      </tr>
-->
      <tr>
        <td align="right"><b>ID partage/Claim ID:</b></td>
        <td><input type="text" id="claimID" name="claimID" size="16" value="{$claimID}"/></td>
      </tr>
      <tr>
        <td align="right"><b>Clé/Passcode:</b></td>
        <td><input type="text" name="claimPasscode" size="16" value=""/></td>
      </tr>
      <tr class="UD_form_footer">
        <td colspan="2" align="center"><input type="submit" name="pickup" value="Accéder au partage/Pick-up the File(s)"/></td>
      </tr>
    </table>
  </form>
</center>

{include file="footer.tpl"}
