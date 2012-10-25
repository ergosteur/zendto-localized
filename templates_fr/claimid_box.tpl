{include file="header.tpl"}

{if $cameFromEmail}
<h4>Veuillez entrer l'ID de partage et la clé que vous avez reçu par courriel.
{else}
<h4>Veuillez entrer l'ID de partage et la clé.
{/if}
{if $isAuthorizedUser}
Si l'expéditeur vous a donné un clé pour le partage, veuillez
If the sender gave you a passcode for the claim, please enter it.
{/if}
</h4>

<center>
  <form name="pickup" method="post" action="{$zendToURL}pickup.php">
    <input type="hidden" name="auth" value="{$auth}"/>
    <table class="UD_form" cellpadding="4">
<!--
      <tr class="UD_form_header">
        <td colspan="2">Téléchargement de fichier</td>
      </tr>
-->
      <tr>
        <td align="right"><b>ID partage:</b></td>
        <td><input type="text" id="claimID" name="claimID" size="16" value="{$claimID}"/></td>
      </tr>
      <tr>
        <td align="right"><b>Clé:</b></td>
        <td><input type="text" name="claimPasscode" size="16" value=""/></td>
      </tr>
      <tr class="UD_form_footer">
        <td colspan="2" align="center"><input type="submit" name="pickup" value="Accéder au partage"/></td>
      </tr>
    </table>
  </form>
</center>

{include file="footer.tpl"}
