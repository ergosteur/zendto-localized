{include file="header.tpl"}

<script type="text/javascript">
<!--

var   file_id = 1;
var   recipient_id = 1;

function addFile()
{
  // Are we less than the max number of file uploads allowed by PHP?
  if ( file_id < {$uploadFilesMax} ) {
    if ( document.getElementById("file_" + file_id).value ) {
      var uploadRow = document.getElementById("file_matrix").insertRow(3 + 2 * (file_id - 1));
      var descRow   = document.getElementById("file_matrix").insertRow(4 + 2 * (file_id - 1));
    
      if ( uploadRow && descRow ) {
        var label = uploadRow.insertCell(0);
        var upload = uploadRow.insertCell(1);
        var blank = descRow.insertCell(0);
        var desc = descRow.insertCell(1);
      
        file_id++;
      
        label.innerHTML = '<b>File ' + file_id + ':</b>';
        upload.innerHTML = '<input type="file" name="file_' + file_id + '" id="file_' + file_id + '" size="50" onChange="addFile();"/>';
      
        blank.innerHTML = '&nbsp;';      desc.innerHTML = 'Description:&nbsp;<input type="text" name="desc_' + file_id + '" id="desc_' + file_id + '" size="30"/>';
      }
    }
  }
  return 1;
}

function addRecipient()
{
  if ( document.getElementById('recipEmail_' + recipient_id).value ) {
    var newRow = document.getElementById("recipient_matrix").insertRow(1 + recipient_id);
    
    if ( newRow ) {
      var label = newRow.insertCell(0);
      var name = newRow.insertCell(1);
      
      recipient_id++;
      
      label.innerHTML = '<b>Recipient ' + recipient_id + ':</b>';
      name.innerHTML = '<table border="0">' +
                       '  <tr>' +
                       '    <td align="right">Name:</td>' +
                       '    <td><input type="text" id="recipName_' + recipient_id + '" name="recipName_' + recipient_id + '" size="30" value=""/></td>' +
                       '  </tr>' +
                       '  <tr>' +
                       '    <td align="right">Email:</td>' +
                       '    <td>' +
                       '      <input type="text" id="recipEmail_' + recipient_id + '" name="recipEmail_' + recipient_id + '" size="30" value=""/>' +
                       '      <input type="hidden" name="recipient_' + recipient_id + '" value="' + recipient_id + '"/>' +
                       '    </td>' +
                       '  </tr>' +
                       '</table>';
    }
  }
}

function validateForm()
{
  if ( document.getElementById("file_1").value == "" ) {
    alert("Please select at least one file in Section 4 before submitting.");
    return false;
  }
  if ( document.dropoff.recipEmail_1.value == "" ) {
    alert("Please enter the recipient's email address in Section 2 before submitting.");
    return false;
  }
  return true;
}
//-->
</script>

{if $isAuthorizedUser}
  <h5>This web page will allow you to drop-off (upload) one or more files
  for anyone (either {#LocalUser#} or others). The recipient will
  receive an automated email containing the information you enter below
  and instructions for downloading the file. Your IP address will be
  logged and sent to the  recipient, as well, for identity confirmation
  purposes.</h5>
{else}
  <h5>This web page will allow you to drop-off (upload) one or more
  files for {#LocalUser#}. The recipient will receive an automated
  email containing the information you enter below and instructions for
  downloading the file. Your IP address will be logged and sent to the
  recipient, as well, for identity confirmation purposes.</h5>
{/if}

<table border="0"><tr valign="top">
  <td>
    <!-- Left-hand side with all the drop-off information -->
    <form name="dropoff" id="dropoff" method="post"
     action="{$zendToURL}dropoff.php" enctype="multipart/form-data"
     onsubmit="return validateForm();">
      <input type="hidden" name="APC_UPLOAD_PROGRESS" id="progress_key" value="{$progress_id}"/>
      <input type="hidden" name="Action" value="dropoff"/>
      <input type="hidden" id="auth" name="auth" value="{$authKey}"/>
      <input type="hidden" id="req" name="req" value="{$reqKey}"/>
      <input type="hidden" id="senderOrganization" name="senderOrganization" value="{$senderOrg}"/>
      <table border="0" cellpadding="4">

        <!-- First box about the sender -->
        <tr><td width="100%">
          <table class="UD_form" width="100%" cellpadding="4">
            <tr class="UD_form_header"><td colspan="2">
              1. Information about the Sender
            </td></tr>
            <tr>
              <td align="right"><b>Your name:</b></td>
              <td width="60%">{$senderName}</td>
            </tr>
            <tr>
              <td align="right"><b>Your organisation:</b></td>
              <td width="60%">{$senderOrg}</td>
            </tr>
            <tr>
              <td align="right"><b>Your email address:</b></td>
              <td width="60%">{$senderEmail}</td>
            </tr>
            <tr>
              <td colspan="2" align="right"><input type="checkbox" name="confirmDelivery" checked="checked"/>Send an email to me when the recipient picks up the file(s).</td>
            </tr>
          </table></td>
        </tr>

        <!-- Second box about the recipients -->
        <tr>
          <td width="100%">
            <table id="recipient_matrix" class="UD_form" width="100%" cellpadding="4">
              <tr class="UD_form_header"><td colspan="2">
                2. Information about the Recipient
              </td></tr>
              <tr>
                <td><b>Recipient 1:</b></td>
                <td><table border="0">
                      <tr>
                        <td align="right">Name:</td>
                        <td><input type="text" id="recipName_1" name="recipName_1" size="30" value="{$recipName_1}"/></td>
                      </tr>
                      <tr>
                        <td align="right">Email:</td>
                        <td><input type="text" id="recipEmail_1" name="recipEmail_1" size="30" value="{$recipEmail_1}"/>
                            <input type="hidden" name="recipient_1" value="1"/></td>
                      </tr>
                    </table>         
                </td>
              </tr>
              <tr>
                <td colspan="2" align="right">{call name=button relative=FALSE href="javascript:addRecipient();" text="Add Extra Recipient"}</td>
              </tr>
              <tr>
                <td colspan="2" align="right"><input type="checkbox" name="informRecipients" checked="checked"/>Inform the recipients there is a drop-off from me.</td>
              </tr>
              <tr>
                <td colspan="2" align="center"><hr></td>
              </tr>
              <tr><td colspan="2" align="left"><b>Upload a CSV or text file containing addresses:</b></td>
              </tr>
              <tr>
                <td>&nbsp;</td>
                <td align="left"><input type="file" name="recipient_csv" size="50" /></td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- Third box containing the short note to recipients -->
        <tr>
          <td width="100%">
            <table class="UD_form" width="100%" cellpadding="4">
              <tr class="UD_form_header">
                <td>3. Short Note to the Recipients</td>
              </tr>
              <tr>
                <td><textarea name="note" id="note" wrap="soft" style="width: 425px; height: 100px">{$note}</textarea></td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- Fourth box about the files to drop off -->
        <tr>
          <td width="100%">
            <table id="file_matrix" class="UD_form" width="100%" cellpadding="4">
              <tr class="UD_form_header">
                <td colspan="2">4. Choose the File(s) you would like to Upload</td>
              </tr>
              <tr>
                <td><b>File 1:</b></td>
                <td><input type="file" name="file_1" id="file_1" size="50" onchange="addFile();"/></td>
              </tr>
              <tr>
                <td>&nbsp;</td>
                <td>Description:&nbsp;<input type="text" name="desc_1" id="desc_1" size="30"/></td>
              </tr>
              <tr class="footer">
                <td colspan="2">Maximum upload of 2,000 MBytes please.</td>
              </tr>
              <tr class="footer">
                <td colspan="2" align="center">
                  <script type="text/javascript">
                    function submitform() {
                      if (validateForm()) {
                        document.getElementById("progress").style.visibility="visible";
{if $useRealProgressBar}
                        window.frames.progress_frame.start('{$progress_id}');
{/if}
                        // scroll(0,0);
                        document.dropoff.submit();
                      }
                    }
                  </script>
                  {call name=button relative=FALSE href="javascript:submitform();" text="Drop-off the File(s)"}
                </td>
              </tr>
            </table>
          </td>
        </tr>

      </table>
    </form>
  </td>

  <!-- Right-hand side red warning box -->
  <td align="center">
    <br>
    <br>
    <div style="width:300px;padding:4px;border:2px solid #C01010;background:#FFF0F0;color:#C01010;text-align:justify;">
      <b>PLEASE NOTE</b>
      <br>
      <br>
      Files uploaded to {#ServiceTitle#} are scanned for viruses.  But still
      exercise the same degree of caution as you would with any other file
      you download.  Users are also <b>strongly encouraged</b> to encrypt
      any files containing sensitive information (e.g. personal non-public
      information, PNPI) before sending them via {#ServiceTitle#}!  See <a
      href="http://www.udel.edu/pnpi/tools/" style="color:#C01010;">this
      page</a> for information on encryption.
      <br>
      <br>
      If you are attaching a file containing the dropoff recipients'
      addresses, the file should be:
      <ul>
        <li>A plain text file with a single email address per line</li>
        <li>A spreadsheet in CSV format (e.g. exported by Excel)</li>
      </ul>
    </div>

{if $useRealProgressBar}
    <div id="progress" style="visibility:hidden;width:300px;padding:4px;border:2px solid #C01010;background:#FFF0F0;color:#C01010;valign:top;">
      <b>Upload Progress:</b><br/>
      <iframe id="progress_frame" name="progress_frame" src="progress.php?progress_id={$progress_id}" frameborder="0" style="border: none; height: 60px; width: 300px; background:#FFF0F0">
      </iframe>
    </div> 
{else}
    <div id="progress" style="visibility:hidden;width:300px;height:68px;padding:4px;border:2px solid #C01010;background:#FFFFFF;color:#C01010;valign:top;">
      <center><img src="../images/progress-bar.gif"></center>
    </div> 
{/if}

  </td>
</tr></table>

{include file="footer.tpl"}
