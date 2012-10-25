<!DOCTYPE html
        PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
         "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
{include file="functions.tpl"}

<html xmlns="http://www.w3.org/1999/xhtml" lang="en-US" xml:lang="en-US">
  <head>
    <title>{#ServiceTitle#}</title>
    <link rel="stylesheet" type="text/css" href="css/{#CSSTheme#}.css"/>
    <meta http-equiv="Content-Type" content="text/html"/>
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

    <style type="text/css">
      #progress {
        visibility:hidden;
        width:450px;
        height:75px;
        padding:0px;
        margin: 5px 0px 0px 0px;
        border:2px solid #C01010;
        background:#FFF0F0;
        color:#C01010;
        text-align:justify;
      }
    </style>

  </head>

{if $focusTarget ne ''}
<body onload="document.{$focusTarget}.focus();">
{else}
<body>
{/if}

<table class="UD_header" width="100%">
  <tr valign="top">
    <td id="UD_header_left" rowspan="2">&nbsp;</td>
    <td id="UD_header_top" align="right">&nbsp;</td>
  </tr>
  <tr>
    <td id="UD_header_title">{#ServiceTitle#}&nbsp;<img src="{$padlockImage}" alt="{$padlockImageAlt}"/></td>
  </tr>
</table>

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


<!-- Begin page content -->
<div class="content">
