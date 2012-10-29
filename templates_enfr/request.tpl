{include file="header.tpl"}

<script type="text/javascript">
<!--

function validateForm()
{
  if ( document.req.recipName.value == "" ) {
    alert("Please enter the recipient's name first!");
    return false;
  }
  if ( document.req.recipEmail.value == "" ) {
    alert("Please enter the recipient's email address first!");
    return false;
  }
  return true;
}
//-->
</script>

    <!-- Left-hand side with all the drop-off information -->
    <form name="req" id="req" method="post"
     action="{$zendToURL}req.php" enctype="multipart/form-data"
     onsubmit="return validateForm();">

<h1>Demander un partage/Request a Drop-off</h1>
   <h5>Cette page web vous permettra d'envoyer une demande à un ou plusieursautres personnes
   leur demandant d'envoyer (upload) un ou plusieurs fichiers pour vous.
   Le destinataire recevra un courriel contenant les informations que vous saisissez ci-dessous
   et les instructions pour partager le fichier.</h5>
  <h5>This web page will allow you to send a request to one of more
  other people
  requesting that they send (upload) one or more files for you.
  The recipient will
  receive an automated email containing the information you enter below
  and instructions for uploading the file(s).</h5>

<div class="UILabel">De/From:</div> <br class="clear" />
<div id="fromHolder"><span id="fromName">{$senderName}</span> <span id="fromEmail">({$senderEmail})</span> <span id="fromOrg"><label for="senderOrg">Organisation:</label><input type="text" id="senderOrg" name="senderOrg" size="30" value="{$senderOrg}"/></span></div>

<br class="clear" />
<div class="UILabel">À/To:</div> <br class="clear" />
<div id="emailHolder"> <label for="recipName">Name:</label> <input type="text" id="recipName" name="recipName" size="30" value=""/> <label for="recipEmail">Email(s):</label> <input type="text" id="recipEmail" name="recipEmail" size="30" value=""/></div>

<div class="UILabel"><label for="subject">Objet/Subject:</label></div> <br class="clear" />
<input type="text" id="subject" name="subject" size="60" value=""/>

<br class="clear" /><br class="clear" />

<label for="note">Note: Ceci sera envoyé au destinataire/This will be sent to the recipient.</label><br/><textarea name="note" id="note" wrap="soft" style="width: 425px; height: 100px"></textarea>

<table border="0"><tr valign="top">
  <td>

      <input type="hidden" name="Action" value="send"/>
      <table border="0" cellpadding="4">

        <tr class="footer">
          <td width="100%" align="center">
            <script type="text/javascript">
              function submitform() {
                if (validateForm()) {
                  document.req.submit();
                }
              }
            </script>
            {call name=button relative=FALSE href="javascript:submitform();" text="Envoyer la demande/Send the Request"}
          </td>
        </tr>
      </table>
  </td>
</tr></table>

</form>

{include file="footer.tpl"}
