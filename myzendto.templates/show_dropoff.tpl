{include file="header.tpl"}

<script type="text/javascript">
<!--

function doDelete()
{
  if ( confirm("Do you really want to delete this dropoff?") ) {
    return document.deleteDropoff.submit();
  }
  return 0;
}

//-->
</script>

{if $isDeleteable}<div style="float:right"><button class="UD_textbutton_admin" onclick="doDelete();">Delete Dropoff</button></div>{else}&nbsp;{/if}

<h1>Drop-Off Summary</h1>

{if $isClickable}
<div align="center">
  <h4>Click on a filename or icon to download that file.</h4>
</div>
{/if}

<table border="0" cellpadding="5" width="100%">
  <tr valign="top">
    <td>
    </td>
    <td>

{if $dropoffFilesCount>0}
      <table class="UD_form" cellpadding="4">
        <thead class="UD_form_header">
          <td colspan="2">Filename</td>
          <td align="center">Type</td>
          <td align="right">Size</td>
          <td>Description</td>
        </thead>
  {foreach from=$files item=f}
        <tr class="UD_form_lined" valign="middle">
      {if $isClickable}
          <td width="20" align="center"><a href="{$downloadURL}&fid={$f.rowID}"><img src="images/generic.png" border="0" alt="[file]"/></a></td>
          <td class="UD_form_lined"><a href="{$downloadURL}&fid={$f.rowID}"><tt>{$f.basename}</tt></a></td>
      {else}
          <td width="20" align="center"><img src="images/generic.png" alt="[file]"/></td>
          <td class="UD_form_lined"><tt>{$f.basename}</tt></td>
      {/if}
          <td class="UD_form_lined" align="center">{$f.mimeType}</td>
          <td class="UD_form_lined" align="right">{$f.length|replace:' ':'&nbsp;'}</td>
          <td>{$f.description|default:"&nbsp;"}</td>
        </tr>
  {/foreach}
        <tr class="UD_form_footer">
          <td colspan="5" align="center">{$dropoffFilesCount} file{if $dropoffFilesCount ne 1}s{/if}</td>
        </tr>
      </table>
      <form name="deleteDropoff" method="post" action="{$zendToURL}delete.php">
        <input type="hidden" name="claimID" value="{$claimID}"/>
        <input type="hidden" name="claimPasscode" value="{$claimPasscode}"/>

  {if $emailAddr ne ""}
        <input type="hidden" name="emailAddr" value="{$emailAddr}"/>
  {/if}
      </form>

{else}
      No files in the dropoff... something is amiss!
{/if}

    </td>
  </tr>
</table>


<div id="commentsArea">
	<label for="comments">Comments:</label><br />
	<textarea readonly="yes" id="comments" name="comments" style="width: 400px; height: 100px;">{$note}</textarea>
</div>

<div id="sendContainer">
{if $inPickupPHP}
<!--  <b>Claim ID:</b> {$claimID}
  <b>Claim Passcode:</b> {$claimPasscode} -->
{elseif $isAuthorizedUser}
<!--	<p>To send the file to someone else, simply send them this Claim ID and Passcode:</p>
	<textarea readonly="yes" wrap="hard" rows="2" cols="32">Claim ID: {$claimID}
Claim Passcode: {$claimPasscode}</textarea> -->
{/if}
</div>

{include file="footer.tpl"}
