{include file="header.tpl"}

<script type="text/javascript" src="js/dropoff.js"></script>

<script type="text/javascript">

// Email regex
var emailFormatRegex = /(\S+@\S+)/;
var emailBracketRegex = /<(\S+@\S+)>/;
var emailRegex = {$validEmailRegexp};

var presetToName = '{$recipName_1}';
var presetToEmail = '{$recipEmail_1}';

var file_id = 1;
var recipient_id = 1;

$(document).ready(function(){

	// Get the new recipients form and copy it into a new object + remove from the DOM.
	DropOff.addRecipients = $('#addNewRecipient').html();
	$('#addNewRecipient').remove();

});




function addFile() {
  // Are we less than the max number of file uploads allowed by PHP?
  if ( file_id < {$uploadFilesMax} ) {
    if ( $("file_" + file_id).val() != "" ) {
	
	file_id++;
 
	var f = $("<div>", { 'id': 'file_' + file_id, 'class': 'file' });
  	f.append('<label for="file_' + file_id + '">File ' + file_id + ':</label> ');
  	f.append('<input type="file" name="file_' + file_id + '" id="file_' + file_id + '" size="50" onChange="addFile();"/> ');
  	f.append('<label for="desc_' + file_id + '">Description:</label><input type="text" name="desc_' + file_id + '" id="desc_' + file_id + '" size="30"/>');
 
  	$('#uploadFiles').append(f);   
    }
  }
  return 1;
}


function focus(el){
	$($(el)).focus();
}

function validateForm()
{
  if ( file_id < 2 ) {
    alert("Please add at least one file first!");
    return false;
  }
  // JKF if ( recipient_id < 2 ) {
  // JKF   alert("Please add at least one recipient first!");
  // JKF   return false;
  // JKF }
  return true;
}
</script>

{if $isAuthorizedUser}
  <h5>This web page will allow you to drop-off (upload) one or more files
  for anyone (either {#LocalUser#} or others). The recipient will
  receive an automated email containing the information you enter below
  and instructions for downloading the file. Your IP address will be
  logged and sent to the  recipient, as well, for identity confirmation
  purposes.</h5>
{/if}

<form name="dropoff" id="dropoff" method="post" action="{$zendToURL}dropoff.php" enctype="multipart/form-data" onsubmit="return validateForm();">


<input type="hidden" name="APC_UPLOAD_PROGRESS" id="progress_key" value="{$progress_id}"/>
<input type="hidden" name="Action" value="dropoff"/>
<input type="hidden" id="auth" name="auth" value="{$authKey}"/>
<input type="hidden" id="req" name="req" value="{$reqKey}"/>
<input type="hidden" id="senderOrganization" name="senderOrganization" value="{$senderOrg}"/>

<br class="clear" />


<!-- JKF <input type="hidden" name="recipName_1" value="{$recipName_1}" />
<input type="hidden" name="recipEmail_1" value="{$recipEmail_1}" />
<input type="hidden" name="recipient_1" value="recipient_id" />
JKF -->
<input type="hidden" id="recipName_1" name="recipName_1" value="{$senderName}">
<input type="hidden" id="recipEmail_1" name="recipEmail_1" value="{$senderEmail}">
<input type="hidden" id="recipient_1" name="recipient_1" value="1">


<p>
	<label for="note">Short note about the files</label>
	<textarea name="note" id="note" wrap="soft" style="width:99%;height: 50px">{$note}</textarea>
</p>

<b>Choose the File(s) you would like to upload</b>

<div id="uploadFiles">
	<div id="file_1" class="file">
		<label for="file_1">File 1:</label>
		<input type="file" name="file_1" id="file_1" size="50" onchange="addFile();" />
		<label for="desc_1">Description:</label><input type="text" name="desc_1" id="desc_1" size="30"/>
	</div>
</div>

</form>


<div id="uploadDialog">
	<h1>Uploading...</h1>
	<div id="progressContainer">
		<iframe id="progress_frame" scrolling="no" name="progress_frame" src="progress.php?progress_id={$progress_id}" frameborder="0" style="border: none; height: 80px; width: 350px;"></iframe>
	</div>
</div>

<script type="text/javascript">
function submitform() {
  if (validateForm()) {

    window.frames.progress_frame.start('{$progress_id}');
    showUpload();

    // scroll(0,0);
    document.dropoff.submit();
  }
}
</script>

<div class="center"><button onclick="submitform();">Drop off Files</button></div>

{include file="footer.tpl"}
