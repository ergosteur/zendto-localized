{include file="header.tpl"}

<script type="text/javascript">
<!--

function validateForm()
{
  if ( document.req.recipName.value == "" ) {
    alert("Please enter the recipient's name in Section 2 before submitting.");
    return false;
  }
  if ( document.req.recipEmail.value == "" ) {
    alert("Please enter the recipient's email address in Section 2 before submitting.");
    return false;
  }
  return true;
}
//-->
</script>

  <h5>This web page will allow you to send a request to another person
  requesting that they drop-off (upload) one or more files for you.
  The recipient will
  receive an automated email containing the information you enter below
  and instructions for uploading the file.</h5>

<table border="0"><tr valign="top">
  <td>
    <!-- Left-hand side with all the drop-off information -->
    <form name="req" id="req" method="post"
     action="{$zendToURL}req.php" enctype="multipart/form-data"
     onsubmit="return validateForm();">
      <input type="hidden" name="Action" value="send"/>
      <table border="0" cellpadding="4">

        <!-- First box about the sender -->
        <tr><td width="100%">
          <table class="UD_form" width="100%" cellpadding="4">
            <tr class="UD_form_header"><td colspan="2">
              1. From:
            </td></tr>
            <tr>
              <td align="right"><b>Your name:</b></td>
              <td width="60%">{$senderName}</td>
            </tr>
            <tr>
              <td align="right"><b>Your organisation:</b></td>
              <td width="60%"><input type="text" id="senderOrg" name="senderOrg" size="30" value="{$senderOrg}"/></td>
            </tr>
            <tr>
              <td align="right"><b>Your email address:</b></td>
              <td width="60%">{$senderEmail}</td>
            </tr>
          </table></td>
        </tr>

        <!-- Second box about the recipients -->
        <tr>
          <td width="100%">
            <table id="recipient_matrix" class="UD_form" width="100%" cellpadding="4">
              <tr class="UD_form_header"><td colspan="2">
                2. To:
              </td></tr>
              <tr>
                <td align="right"><b>Name:</b></td>
                <td width="60%"><input type="text" id="recipName" name="recipName" size="30" value=""/></td>
              </tr>
              <tr>
                <td align="right"><b>Email:</b></td>
                <td width="60%"><input type="text" id="recipEmail" name="recipEmail" size="30" value=""/>
                    <input type="hidden" name="recipient" value="1"/></td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- Third box containing the short note to recipients -->
        <tr>
          <td width="100%">
            <table class="UD_form" width="100%" cellpadding="4">
              <tr class="UD_form_header"><td colspan="2">
                3. Content
              </td></tr>
              <tr>
                <td align="right"><b>Subject:</b></td>
                <td width="80%"><input type="text" id="subject" name="subject" size="60" value=""/></td>
              </tr>
              <tr>
                <td colspan="2">This note will also be included in the resulting drop-off sent to you:<br/><textarea name="note" id="note" wrap="soft" style="width: 425px; height: 100px"></textarea></td>
              </tr>
            </table>
          </td>
        </tr>

        <tr class="footer">
          <td width="100%" align="center">
            <script type="text/javascript">
              function submitform() {
                if (validateForm()) {
                  document.req.submit();
                }
              }
            </script>
            {call name=button relative=FALSE href="javascript:submitform();" text="Send the Request"}
          </td>
        </tr>
      </table>
    </form>
  </td>
</tr></table>

{include file="footer.tpl"}
