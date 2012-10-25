<!DOCTYPE html
        PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
         "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
{include file="functions.tpl"}

<html xmlns="http://www.w3.org/1999/xhtml" lang="en-US" xml:lang="en-US">
  <head>
    <title>{#ServiceTitle#}</title>
    <link rel="stylesheet" type="text/css" href="css/{#CSSTheme#}.css"/>
    <link rel="stylesheet" type="text/css" href="css/local.css"/>
    <link rel="stylesheet" type="text/css" href="css/datatables.css"/>
{if $autoHome}
    <meta http-equiv="refresh" content="10;URL={$zendToURL}">
{/if}

    <script type="text/javascript">
    <!--
    function doPickup(theID)
    {
      document.pickup.claimID.value = theID;
      return document.pickup.submit();
    }
    //-->
    </script>
    
	<script type="text/javascript" src="js/jquery-1.5.2.min.js"></script> 
	<script type="text/javascript" src="js/facebox/facebox.js"></script>
        <script type="text/javascript" src="js/jquery.dataTables.js"></script>
        <script type="text/javascript" src="js/jquery.dataTables.datesort.js"></script>
	<link href="js/facebox/facebox.css" media="screen" rel="stylesheet" type="text/css" />
	<script type="text/javascript" src="js/main.js"></script>
	<script type="text/javascript">
		var isLocal = "{$isLocalIP}";
	</script>

<!--[if lte IE 8]>
<link rel="stylesheet" type="text/css" href="css/ie7.css"/>
<![endif]-->
<!-- JKF Created IE9-specific stylesheet without DirectX gradient fills -->
<!--[if gte IE 9]>
<link rel="stylesheet" type="text/css" href="css/ie9.css"/>
<![endif]-->

  </head>

<!--[if lte IE 8]>
<style type="text/css">

    .emailButton {
        float: none;
        zoom: 1; display: inline-block; /*  for block-level elements in IE 7 and 6. See http://foohack.com/2007/11/cross-browser-support-for-inline-block-styling/ */
    	display: inline;
    }
</style>
<![endif]-->

{if $focusTarget ne ''}
<body onload="document.{$focusTarget}.focus();">
{else}
<body>
{/if}

<!--[if lt IE 7]> <div style=' clear: both; height: 59px; padding:0 0 0 0px; position: relative;'> <a href="http://windows.microsoft.com/en-US/internet-explorer/products/ie/home?ocid=ie6_countdown_bannercode"><img src="/images/upgradeie6.jpg" border="0" height="42" width="820" alt="You are using an outdated browser. For a faster, safer browsing experience, upgrade for free today." /></a></div> <![endif]-->

<!-- Begin page content -->
<div class="content">
	<div id="logo"><a href="/"><!--<img src="{$padlockImage}" alt="{$padlockImageAlt}"/>--> {#ServiceLogo#}</a></div>
	<div id="topMenu">
		<ul>
			<li id="homeLink" class="selected"><a href="/">Home</a></li>					
		{if $isAuthorizedUser}
			<li id="logoutLink"><a href="index.php?action=logout">Logout</a></li>
		{else}
			<li id="loginLink"><a href="index.php?action=login">Login</a></li>
		{/if}			
		</ul>
	</div>
	<div id="container">

{if count($errors)>0}
  <center>
    <table class="UD_error" width="50%">
  {for $i=0;$i<count($errors);$i++}
      <tr>
        <td valign="middle" rowspan="2"><img src="images/error-icon.png" alt="[error]"/></td>
        <td class="UD_error_title">{$errors[$i].title|default:"&nbsp;"}</td>
      </tr>
      <tr>
        <td class="UD_error_message">{$errors[$i].text|default:"&nbsp;"}</td>
      </tr>
  {/for}
    </table>
  </center>
{/if}
