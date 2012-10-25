{include file="header.tpl"}

<script type="text/javascript">
<!--

function validateForm()
{
  if ( document.dropoff.req.value != "" ) {
    return true;
  }
  if ( document.dropoff.senderName.value == "" ) {
    alert("Please enter your name before submitting.");
    document.dropoff.senderName.focus();
    return false;
  }
  if ( document.dropoff.senderOrganization.value == "" ) {
    alert("Please enter your organisation before submitting.");
    document.dropoff.senderOrganization.focus();    return false;
  }  if ( document.dropoff.senderEmail.value == "" ) {
    alert("Please enter your email address before submitting.");
    document.dropoff.senderEmail.focus();    return false;
  }
  
  return true;
}

//-->
</script>
  <form name="dropoff" id="dropoff" method="post"
      action="{$zendToURL}verify.php"
      enctype="multipart/form-data" onsubmit="return validateForm();">
      <input type="hidden" name="Action" value="verify"/>
      <table border="0" cellpadding="4">

        <tr><td width="100%">
          <table class="UD_form" width="100%" cellpadding="4">
            <tr class="UD_form_header"><td colspan="2">
              Information about the Sender
            </td></tr>

{if $verifyFailed}
            <tr><td colspan="2"><b>You did not complete the form, or you failed the "Am I A Real Person?" test.</b></td></tr>
{/if}

            <tr><td colspan="2">If you have been given a "<b>Request Code</b>" then just enter it here and click "Send" at the bottom of this form.</td></tr>
            <tr>
              <td align="right"><b>Request Code:</b></td>
              <td width="60%"><input type="text" id="req" name="req" size="45" value=""/></td>
            </tr>
            <tr><td colspan="2"><hr style="width: 80%;"/></td></tr>
            <tr><td colspan="2">If you do not have a "Request Code" then please complete the rest of this form:</td></tr>

            <tr>
              <td align="right"><b>Your name:</b></td>
{if $isAuthorizedUser}
              <td width="60%"><input type="hidden" id="senderName" name="senderName" value="{$senderName}">{$senderName}</td>
{else}
              <td width="60%"><input type="text" id="senderName" name="senderName" size="45" value="{$senderName}"/><font style="font-size:9px">(required)</font></td>
{/if}
            </tr>

            <tr>
              <td align="right"><b>Your organisation:</b></td>
              <td width="60%"><input type="text" id="senderOrganization" name="senderOrganization" size="45" value="{$senderOrg}"/><font style="font-size:9px">(required)</font></td>
            </tr>
            <tr>
              <td align="right"><b>Your email address:</b></td>
{if $isAuthorizedUser}
              <td width="60%"><input type="hidden" id="senderEmail" name="senderEmail" value="{$senderEmail}">{$senderEmail}</td>
{else}
              <td width="60%"><input type="text" id="senderEmail" name="senderEmail" size="45" value="{$senderEmail}"/><font style="font-size:9px">(required)</font></td>
{/if}
            </tr>

{if ! $isAuthorizedUser}
            <tr>
              <td colspan="2" align="center">
  {if ! $recaptchaDisabled}
                To confirm that you are a <i>real</i> person (and not a computer), please fill in the form below:<br />&nbsp;<br />
                {$recaptchaHTML}
                <br />
  {/if}
                I now need to send you a confirmation email.<br />
                When you get it in a minute or two, click on
                the link in it.
              </td>
            </tr>

            <tr class="footer"><td colspan="2" align="center">
              <script type="text/javascript">
                function submitform() {
                  if (validateForm()) { document.dropoff.submit(); }
                }
              </script>
              {call name=button relative=FALSE href="javascript:submitform();" text="Send confirmation"}
            </tr>
{else}
            <tr class="footer"><td colspan="2" align="center">
              <script type="text/javascript">
                function submitform() {
                  if (validateForm()) { document.dropoff.submit(); }
                }
              </script>
              {call name=button relative=FALSE href="javascript:submitform();" text="Next"}
            </tr>

{/if}

          </table>
        </td></tr>

      </table>
</form>

{include file="footer.tpl"}
