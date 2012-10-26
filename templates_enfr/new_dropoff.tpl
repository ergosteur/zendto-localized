{include file="header.tpl"}

<script type="text/javascript" src="js/dropoff.js"></script>

<script type="text/javascript">
<!--

// Email regex
var emailFormatRegex = /(\S+@\S+)/;
var emailBracketRegex = /<(\S+@\S+)>/;
var emailRegex = {$validEmailRegexp};

var presetToName = '{$recipName_1}';
var presetToEmail = '{$recipEmail_1}';

var maxFileSize = '{$maxBytesForFileInt}';
var maxTotalSize = '{$maxBytesForDropoffInt}';

// Library information
var usingLibrary = '{$usingLibrary}';
var library = {$library};

var file_id = 2;
var recipient_id = 1;

var fileSizeObject = {};

var unload_msg = "{#ErrorCancelUpload#}";
var ignore_unload = true;

$(document).ready(function(){

	function doBeforeUnload() {
               if(ignore_unload) return; // Let the page unload

               if(window.event)
                      window.event.returnValue = unload_msg; // IE
               else
                      return unload_msg; // FF
        }

        if(window.body)
               window.body.onbeforeunload = doBeforeUnload; // IE
        else
               window.onbeforeunload = doBeforeUnload; // FF

	if(presetToName != "" && presetToEmail != "") {
		// If the preset fields are set, add the recipient.
		addRecipient(presetToName, presetToEmail);
	}

	// Check to see if maxFileSize & maxTotalFileSize is set.
	// If so we need to convert to ints from strings.
	if(maxFileSize != '') maxFileSize = parseInt(maxFileSize);
	if(maxTotalSize != '') maxTotalSize = parseInt(maxTotalSize);

	// Live event for removing recipients.
	// Live allows us to bind to any instance of .emailButton a.remove now or in the future (even if the element doesn't exist yet.)
	$('.emailButton a.remove').live('click', function(){
		recipient_id--;
		$(this).parent().remove();
	});
	
	// If the user hits enter in this field, add the recipient.
	$('#recipEmail').live('keyup', function(e) {
		if(e.keyCode == 13) addSingleRecipient();
	});

	
	$('#addRecipients').bind('click', function(){
		// Bind facebox on reveal to focus recipName
		$(document).bind('reveal.facebox', function() { $('#recipName').focus() });
		
		// Show the dialog.
		$.facebox(DropOff.addRecipients);
	});

	// Bind an event to facebox's close event
	$(document).bind('afterClose.facebox', function(){
		// Focus on the 'note' element
		$('#note').focus();
	});

	
	$('#addMultipleRecipients').live('click', function(){
		addMultiple();
		return false;
	});
	
	$('#showSingleDialog, #showMultipleDialog').live('click', function(){
	
		switch($(this).attr('id')){
		
			case "showSingleDialog":
				$('#showSingleDialog').removeClass('greyButton');
				$('#showMultipleDialog').addClass('greyButton');
				
				$( "#sendMultiple" ).fadeOut(200, function(){
					$( "#sendSingle" ).fadeIn(200);
				});
				
				
			break;
			
			case "showMultipleDialog":
				$('#showMultipleDialog').removeClass('greyButton');
				$('#showSingleDialog').addClass('greyButton');	
				
				$('#sendSingle').fadeOut(200, function(){
					$( "#sendMultiple" ).fadeIn(200);
				});
				
						
			break;
		
		
		}
		
	});
	
	// Get the new recipients form and copy it into a new object + remove from the DOM.
	DropOff.addRecipients = $('#addNewRecipient').html();
	$('#addNewRecipient').remove();



	// Code for dealing with selecting already uploaded files.
	if(usingLibrary == "1"){
		$('.file_select').live('change', function(){
			var selectedOption = $(this).children('option:selected');
       		 	var desc = (typeof selectedOption.data('info') == "undefined") ? "" : selectedOption.data('info').description;
	
			// Set the description
	                $(this).siblings('input[type="text"]').val(desc);
	
   	             if ( $("select#file_select_" + file_id).children('option:selected').val() != "-1" ) {
   	             	 insertNewFileRow();
   	             }
   	
			// If the value is -1 ('select a file') ungrey the file select, otherwise grey it out.
			if(selectedOption.val() == "-1"){
				$(this).siblings('input[type="file"]').removeAttr('disabled');
			} else {
				$(this).siblings('input[type="file"]').attr('disabled', true);
			}
        	});
	} else {
		$('.file_select').remove();
	}

	function populateSelectFileFields(target){
		if(target == null) target = ".file_select";
		$.each(library, function(i, v){
			//console.log(v);
			$(target).append(
				$('<option>', { html: v.filename, 'val': v.where }).data('info', v)
			);
		});
	}

	populateSelectFileFields();

});


function populateSelectFileFields(target){
	if(target == null) target = ".file_select";
        //console.log(target);
		$.each(library, function(i, v){
                	//console.log(v);
                        $(target).append(
                                $('<option>', { html: v.filename, 'val': v.where }).data('info', v)
                );
	});
}

function showFileSize(el) {
    var input, file;

    if (typeof window.FileReader !== 'function') {
        // Fail silently, browser doesn't support FileReader api.
        return true;
    }

    input = document.getElementById(el);

    if (!input) {
        // console.log("couldn't find the fileinput element.");
    } else if (!input.files) {
        //console.log("This browser doesn't seem to support the `files` property of file inputs.");
    } else if (!input.files[0]) {
         //console.log("Please select a file before clicking 'Load'");
    } else {
	if($('#overallFileSize').length == 0) $('form#dropoff').append('<div id="overallFileSize"></div>');
        
	file = input.files[0];
	fileSizeObject[el] = file.size;
	if(file.size > maxFileSize){
	     alert('The file you selected for upload named "' + file.name + '" is too large (' + formatFileSize(file.size) + ') for the maximum size allowed (' + formatFileSize(maxFileSize) + ')');
	     $(input).val("");
	     return false;
	}
         //console.log("File " + file.name + " is " + file.size + " bytes in size");
    }
    
    // Check to see if the overall size is too large


    $(input).siblings('.fileSize').html('(' + formatFileSize(file.size)  + ')');

    if( checkOverallSize() ) {
	return file.size;
    } else {
	$(input).val('');
	return false;
    }
}

function formatFileSize(size){
	var outputSize = sdp(((size / 1024) / 1024), 2);

	return outputSize + "MB";
}

function sdp(num, dec) {
	var result = Math.round(num*Math.pow(10,dec))/Math.pow(10,dec);
	return result;
}

function checkOverallSize(){
	var totalSize = 0;
	//console.log(fileSizeObject);
	for(var i in fileSizeObject){
		totalSize += fileSizeObject[i];
		//console.log(fileSizeObject[i]);
	}

	if(totalSize > maxTotalSize){
		alert('You have exceeded the maximum filesize you can send in one dropoff (' + formatFileSize(totalSize) + ').');
		return false;
	} else {
		$('#overallFileSize').html(formatFileSize(totalSize) + " / " + formatFileSize(maxTotalSize));
	}
	return true;
}

function addFile(el) {
  // Are we less than the max number of file uploads allowed by PHP?
  if ( file_id < {$uploadFilesMax} ) {
    if ( $("file_" + file_id).val() != "" ) {

	var fileSize = showFileSize(el);

	if( fileSize ){
		insertNewFileRow(); 
	}   
    }
  }
  return 1;
}

function insertNewFileRow(){
	file_id++;

	var f = $("<div>", { 'id': 'div_file_' + file_id, 'class': 'file' });
	f.append('<label for="file_' + file_id + '">File ' + file_id + ':</label> ');
	f.append('<input type="file" name="file_' + file_id + '" id="file_' + file_id + '" size="50" onChange="addFile(\'file_' + file_id + '\');"/> ');
 	if(usingLibrary == "1") f.append('<select id="file_select_' + file_id + '" name="file_select_' + file_id + '" class="file_select"><option value="-1">or select a file</option></select> ');
	f.append('<label for="desc_' + file_id + '">Description:</label><input type="text" name="desc_' + file_id + '" id="desc_' + file_id + '" size="30"/>');
	f.append(' <span class="fileSize"></span>');		

	$('#uploadFiles').append(f);

	// Populate the select
	populateSelectFileFields("#file_select_" + file_id);
}

function addSingleRecipient(){

    var currentName = $('#recipName').val();
    var currentEmail = $('#recipEmail').val();

	addRecipient(currentName, currentEmail);
}

function addRecipient(currentName, currentEmail){
	currentEmail = $.trim(currentEmail);
	currentName = $.trim(currentName);

	if(emailRegex.test(currentEmail) == false) {
		alert("Please enter a valid email address.");
		return false;
	}

	if ( currentEmail != "" ) {
	  // New data
	  var format = currentName + " (" + currentEmail + ")";
	  
	  var emailTemplate = $("<div>", { 'class':'emailButton', 'html': format });
	  
	  emailTemplate.append($("<a>", { 'class': 'remove', 'style': 'float:right; top:-3px; position:relative' }).append($('<img>', { src: 'images/swish/minus-circle.png', alt: 'Remove selected recipient' })));
	  
	  emailTemplate.append($("<input>", { 'type': 'hidden', 'name': 'recipName_' + recipient_id, 'value': currentName }));
	  emailTemplate.append($("<input>", { 'type': 'hidden', 'name': 'recipEmail_' + recipient_id, 'value': currentEmail }));
	  emailTemplate.append($("<input>", { 'type': 'hidden', 'name': 'recipient_' + recipient_id, 'value': 'recipient_id' }));
	  
	  emailTemplate.insertBefore('#emailHolder a#addRecipients');
	  
	  // alert(emailTemplate.outerWidth());
	  
	  recipient_id++;
	  
	  clearRecipientFields();
	  focus("#recipName");
	}
}

function addMultiple(){
	// Get contents of text field.
	var rawData = $('#multipleRecipients').val();
	var rejectedAddresses = "";
	
	
	if(rawData.length == 0) return;
	
	// Pull out the lines.
	var lines = rawData.split(/\r\n|\r|\n/);
	
	for(recipient in lines){
		
		if( emailFormatRegex.test(lines[recipient]) ) {
		
			var email = emailFormatRegex.exec(lines[recipient]);
		
			var thisEmail = email[1];
			var thisName = lines[recipient].replace(thisEmail, "");
			
			if(emailBracketRegex.test(thisEmail)) {
				em = emailBracketRegex.exec(thisEmail);
				thisEmail = em[1];
			}
			
			if(emailRegex.test(thisEmail) == false){
				rejectedAddresses += lines[recipient] + "\n";
				continue;
			}
			
			addRecipient(thisName, thisEmail);

		} else {
			rejectedAddresses += lines[recipient] + "\n";	
		}
	
	}
	
	$('#multipleRecipients').val(rejectedAddresses);

}


function clearRecipientFields(){
    $('#recipName, #recipEmail').val("");
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
  if ( recipient_id < 2 ) {
    alert("Please add at least one recipient first!");
    return false;
  }
  return true;
}
//-->
</script>

    <div style="padding:4px;border:2px solid #C01010;background:#FFF0F0;color:#C01010;text-align:justify;" class="round">
      <b>PLEASE NOTE</b>
      <br>
      <br>
      Files uploaded to {#ServiceTitle#} are scanned for viruses.  But still
      exercise the same degree of caution as you would with any other file
      you download.  Users are also <b>strongly encouraged</b> to encrypt
      any files containing sensitive information (e.g. personal private
      information) using a tool such as "Winzip" or "Encrypt Files",
      before sending them via {#ServiceTitle#}!
    </div>

{if $isAuthorizedUser}
  <h5>This web page will allow you to drop-off (upload) one or more files
  for anyone (either {#LocalUser#} or others). The recipient will
  receive an automated email containing the information you enter below
  and instructions for downloading the file. Your IP address will also be
  logged and sent to the recipient for identity confirmation
  purposes.</h5>
{else}
  <h5>This web page will allow you to drop-off (upload) one or more
  files for {#LocalUser#}. The recipient will receive an automated
  email containing the information you enter below and instructions for
  downloading the file. Your IP address will also be logged and sent to the
  recipient for identity confirmation purposes.</h5>
{/if}

<form name="dropoff" id="dropoff" method="post" action="{$zendToURL}dropoff.php" enctype="multipart/form-data" onsubmit="return validateForm();">

<!-- First box about the sender -->
<div class="UILabel">From:</div> <br class="clear" />
<div id="fromHolder"><span id="fromName">{$senderName}</span> <span id="fromEmail">({$senderEmail})</span> <span id="fromOrg">{$senderOrg}</span></div>
<div class="fright">
	<input type="checkbox" name="informRecipients" id="informRecipients" checked="checked" /> <label for="informRecipients">Send e-mail message to recipients</label><br />
	<input type="checkbox" name="confirmDelivery" id="confirmDelivery" checked="checked"/> <label for="confirmDelivery">Send an email to me when the recipient picks up the file(s).</label>
</div>

<input type="hidden" name="APC_UPLOAD_PROGRESS" id="progress_key" value="{$progress_id}"/>
<input type="hidden" name="Action" value="dropoff"/>
<input type="hidden" id="auth" name="auth" value="{$authKey}"/>
<input type="hidden" id="req" name="req" value="{$reqKey}"/>
<input type="hidden" id="senderOrganization" name="senderOrganization" value="{$senderOrg}"/>

<br class="clear" />
<div class="UILabel">To:</div> <br class="clear" />
<div id="emailHolder"> <a id="addRecipients" href="#"><img src="images/swish/plus-circle-frame.png" alt="Add recipients" /></a> </div>
<br class="clear" />


<div id="addNewRecipient">
	<h1>Add Recipients</h1>
	<p id="buttonHolder" class="center">
		<button id="showSingleDialog">Add One</button> <button id="showMultipleDialog" class="greyButton">Add Many</button>
	</p>
	
	<!-- Sending to a single recipient -->
	<div id="sendSingle" class="center">
	<div>
		<label for="recipName" class="UILabel">Name:</label>
    	<input type="text" id="recipName" name="recipName" size="30" value="{$recipName_1}"/>
    </div>
    <div> 
    	<label for="recipEmail" class="UILabel">Email:</label>
    	<input type="text" id="recipEmail" name="recipEmail" size="30" value="{$recipEmail_1}"/> 
    </div>
    <button onclick="javascript:addSingleRecipient();">Add Recipient</button>
	</div>
	
	<div id="sendMultiple" class="center">
	<textarea id="multipleRecipients" rows="10" cols="40" placeholder="Bulk add recipients"></textarea>
	<p>One recipient per line, for example: <br /><i>Test User test@domain.com</i></p>
		<div class="center"><button id="addMultipleRecipients">Verify</button></div>	
	</div>
</div>

<p>
	<label for="note">Short note to the Recipients</label>
	<textarea name="note" id="note" wrap="soft" style="width:99%;height: 50px">{$note}</textarea>
</p>

<b>Choose the File(s) you would like to upload</b>

<div id="uploadFiles">
	<div id="div_file_1" class="file">
		<label for="file_1">File 1:</label>
		<input type="file" name="file_1" id="file_1" size="50" onchange="addFile('file_1');" />
		<select id="file_select_1" name="file_select_1" class="file_select">
			<option value="-1">or select a file</option>
		</select>
		<label for="desc_1">Description:</label><input type="text" name="desc_1" id="desc_1" size="30"/>
		<span class="fileSize"></span>
	</div>
	<div id="div_file_2" class="file">
		<label for="file_2">File 2:</label>
		<input type="file" name="file_2" id="file_2" size="50" onchange="addFile('file_2');" />
                <select id="file_select_2" name="file_select_2" class="file_select">
			<option value="-1">or select a file</option>
		</select>
		<label for="desc_2">Description:</label><input type="text" name="desc_2" id="desc_2" size="30"/>
		<span class="fileSize"></span>	
	</div>
</div>

</form>

<script type="text/javascript">
function submitform() {
  if (validateForm()) {
    
{if $useRealProgressBar}
    showUpload();
    window.frames.progress_frame.start('{$progress_id}');
{else}    
	document.getElementById("progress").style.visibility="visible";
{/if}
    // scroll(0,0);
    document.dropoff.submit();
    ignore_unload = false;
  }
}
</script>

{if $useRealProgressBar}
<div id="uploadDialog">
	<h1>Uploading...</h1>
	<div id="progressContainer">
		<iframe id="progress_frame" scrolling="no" name="progress_frame" src="progress.php?progress_id={$progress_id}" frameborder="0" style="border: none; height: 80px; width: 350px;"></iframe>
	</div>
</div>
{else}
    <div id="progress" style="visibility:hidden;width:300px;height:68px;padding:4px;border:2px solid #C01010;background:#FFFFFF;color:#C01010;valign:top;">
      <center><img src="../images/progress-bar.gif"></center>
    </div> 
{/if}

<div class="center"><button onclick="submitform();">Drop off Files</button></div>

{include file="footer.tpl"}

