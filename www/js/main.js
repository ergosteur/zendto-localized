function bindLogin(){
	$('#loginLink a').bind('click', function(){
		if(/logout/i.test($(this).attr('href'))) return true;
		// Check for existance
		if( $('#loginForm').length > 0 ) return false;
	
		// Create the login form.
		var loginForm = $('<form>', { id: 'loginForm', 'method':'post', 'action': window.location.protocol + '//' + window.location.host });
		
		var un_label = $('<label>', { 'for': 'uname', html: 'Username:' });
		var uname = $('<input>', { type: 'text', id: 'uname', name: 'uname' });
		
		var pw_label = $('<label>', { 'for': 'password', html: 'Password:' });
		var password = $('<input>', { type: 'password', id: 'password', 'name': 'password' });
		
		var login = $('<input>', { type: 'submit', val: 'Login' });
		
		loginForm.append(un_label, uname, pw_label, password, login);
		
		selectMenuItem($(this).parent());
		
		$('#container').prepend(loginForm);
		
		$(loginForm).after(
			$('<div>', { style: 'height:30px' })
		);
		
		// Focus on the username box.
		$('#uname').focus();
		
		// Return false to cancel the actual navigation (non-js fallback will execute otherwise)
		return false;
	});
}

function bindEnter(el, fn){
	$(el).bind('keyup', function(e) {
		if(e.keyCode == 13) fn();
	});
}

function selectMenuItem(el){
	el = $(el);
	removeMenuSelection();
	
	el.addClass('selected');
	return true;
}

function removeMenuSelection(){
	$('#topMenu ul li').removeClass('selected');
}

function showUpload(){
	var dialog = $('#uploadDialog');
	
	// Get frame information
	var container = $('#container');
	var container_pos = container.position();
	
	var dialog_left = ((container.outerWidth() / 2) - (dialog.outerWidth() / 2) + container_pos.left);
	
	dialog.css({ top: container_pos.top + 20, 'left': dialog_left });
	
	dialog.fadeIn();
}



function selectMenu(){
	if(/pickup_list/i.test(window.location)) selectMenuItem('#inboxLink');
	if(/dropoff_list/i.test(window.location)) selectMenuItem('#outboxLink');
}

function setup(){
	selectMenu();

	if($('#loginLink a').length > 0) bindLogin();
	
	if(isLocal == "1" && $('#loginLink').length == 1) $('#loginLink a').trigger('click');
}


$(document).ready(function(){
	setup();
});

/ * Jules' code */
function doPickup(theID) {
  document.pickup.claimID.value = theID;
  return document.pickup.submit();
}