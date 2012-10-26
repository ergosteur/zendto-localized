{include file="header.tpl"}

{if $cameFromEmail}
<h4>Please enter the claim id and claim passcode that were emailed to you.
{else}
<h4>Please enter the claim id and claim passcode.
{/if}
{if $isAuthorizedUser}
If the sender gave you a passcode for the claim, please enter it.
{/if}
</h4>

<center>
  <form name="pickup" method="post" action="{$zendToURL}pickup.php">
    <input type="hidden" name="auth" value="{$auth}"/>
    <table class="UD_form" cellpadding="4">
<!--
      <tr class="UD_form_header">
        <td colspan="2">File Pick-Up</td>
      </tr>
-->
      <tr>
        <td align="right"><b>Claim ID:</b></td>
        <td><input type="text" id="claimID" name="claimID" size="16" value="{$claimID}"/></td>
      </tr>
      <tr>
        <td align="right"><b>Claim Passcode:</b></td>
        <td><input type="text" name="claimPasscode" size="16" value=""/></td>
      </tr>
      <tr class="UD_form_footer">
        <td colspan="2" align="center"><input type="submit" name="pickup" value="Pick-up the File(s)"/></td>
      </tr>
    </table>
  </form>
</center>

{include file="footer.tpl"}
