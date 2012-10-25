{include file="header.tpl"}

  <form name="pickupcheck" method="post" action="{$zendToURL}pickup.php">
      <input type="hidden" name="Action" value="Pickup"/>
      <input type="hidden" name="claimID" value="{$claimID}"/>
      <input type="hidden" name="claimPasscode" value="{$claimPasscode}"/>
      <input type="hidden" name="emailAddr" value="{$emailAddr}"/>
      <input type="hidden" name="auth" value="{$auth}"/>

      <table border="0" cellpadding="4">

            <tr class="UD_form_header"><td colspan="2">
              <h4>Please prove you are a person</h4>
            </td></tr>

            <tr>
              <td colspan="2" align="center">
                To confirm that you are a <i>real</i> person (and not a computer), please fill in the form below:<br />&nbsp;<br />
                {$recaptchaHTML}
                <br />
              </td>
            </tr>

            <tr class="footer"><td colspan="2" align="center">
              {call name=button href="javascript:document.pickupcheck.submit();" width="100%" text="Pickup Files"}
            </tr>

      </table>
  </form>

{include file="footer.tpl"}
