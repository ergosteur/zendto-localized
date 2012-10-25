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

{if $isClickable}
<div align="center">
  <h4>Click on a filename or icon to download that file.</h4>
</div>
{/if}

<table border="0" cellpadding="5">
  <tr valign="top">
    <td><table class="UD_form" cellpadding="4">
          <tr class="UD_form_header" valign="middle">
            <td colspan="2">Drop-Off Summary</td>
            <td align="right">{if $isDeleteable}<img src="images/{#CSSTheme#}/button-delete.png" onclick="doDelete();" onmouseover="document.body.style.cursor = 'pointer';" onmouseout="document.body.style.cursor = 'auto';" alt="[delete]"/>{else}&nbsp;{/if}</td>
          </tr>

{if $inPickupPHP}
          <tr>
            <td class="UD_form_lined" colspan="2" align="right"><b>Claim ID:</b></td>
            <td><tt>{$claimID}</tt></td>
          </tr>
          <tr class="UD_form_lined">
            <td class="UD_form_lined" colspan="2" align="right"><b>Claim Passcode:</b></td>
            <td><tt>{$claimPasscode}</tt></td>
          </tr>

{elseif $isAuthorizedUser}

          <tr>
            <td class="UD_form_lined">&nbsp;</td>
            <td colspan="2" align="left">To send the file to someone else, simply send them this Drop-off Claim ID and Passcode:</td>
          </tr>
          <tr class="UD_form_lined">
            <td class="UD_form_lined">&nbsp;</td>
            <td>&nbsp;</td>
            <td><textarea readonly="yes" wrap="hard" rows="2" cols="32">Claim ID: {$claimID}
Claim Passcode: {$claimPasscode}</textarea></td>
          </tr>
{/if}

          <tr>
            <td class="UD_form_lined" rowspan="6" align="center"><b>F<br/>R<br/>O<br/>M</b></td>
            <td class="UD_form_lined" align="right"><b>Name:</b></td>
            <td><tt>{$senderName}</tt></td>
          </tr>
          <tr>
            <td class="UD_form_lined" align="right"><b>Organisation:</b></td>
            <td><tt>{$senderOrg|default:"&nbsp;"}</tt></td>
          </tr>
          <tr>
            <td class="UD_form_lined" align="right"><b>Email:</b></td>
            <td><tt>{$senderEmail}</tt></td>
          </tr>
          <tr>
            <td class="UD_form_lined" align="right"><b>Sent From:</b></td>
            <td><tt>{$senderHost}</tt></td>
          </tr>
          <tr>
            <td class="UD_form_lined" align="right">&nbsp;</td>
            <td><tt>{$createdDate|date_format:"%d %b %Y&nbsp;&nbsp;%r"}</tt></td>
          </tr>
    <!--  <tr>
            <td class="UD_form_lined" align="right">&nbsp;</td>
            <td><tt>{$expiryDate|date_format:"%d %b %Y&nbsp;&nbsp;%r"}</tt></td>
          </tr> -->
          <tr class="UD_form_lined">
            <td class="UD_form_lined" align="right"><b>Confirm Delivery:</b></td>
            <td><tt>{if $confirmDelivery}yes{else}no{/if}</tt></td>
          </tr>
{if $showRecips}
          <tr class="UD_form_lined">
            <td class="UD_form_lined" align="center"><b>T<br/>O</b></td>
            <td class="UD_form_lined" align="right"><b>Name &amp; Email:</b></td>
            <td><tt>
  {foreach from=$recipients item=r}
              {$r.0|html_entity_decode} ({$r.1})<br />
  {/foreach}
            </tt></td>
          </tr>
{/if}
          <tr>
            <td class="UD_form_lined" align="center"><b>N<br/>O<br/>T<br/>E</b></td>
            <td colspan="2"><textarea readonly="yes" style="width: 425px; height: 200px;">{$note|html_entity_decode}</textarea></td>
          </tr>
        </table>
    </td>
    <td>

{if $dropoffFilesCount>0}
      <table class="UD_form" cellpadding="4">
        <tr class="UD_form_header">
          <td colspan="2">Filename</td>
          <td align="center">Type</td>
          <td align="right">Size</td>
          <td>Description</td>
        </tr>
  {foreach from=$files item=f}
        <tr class="UD_form_lined" valign="middle">
      {if $isClickable}
          <td width="20" align="center"><a href="{$downloadURL}&fid={$f.rowID}"><img src="images/generic.png" border="0" alt="[file]"/></a></td>
          <td class="UD_form_lined"><a href="{$downloadURL}&fid={$f.rowID}"><tt>{$f.basename}</tt></a></td>
      {else}
          <td width="20" align="center"><img src="images/generic.png" alt="[file]"/></td>
          <td class=\"UD_form_lined\"><tt>{$f.basename}</tt></td>
      {/if}
          <td class=\"UD_form_lined\" align=\"center\">{$f.mimeType}</td>
          <td class=\"UD_form_lined\" align=\"right\">{$f.length}</td>
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
      No files in the dropoff...something is amiss!
{/if}

    </td>
  </tr>

<!-- Show all the recipients and their pick-up details -->
{if $showRecips}
  <tr>
    <td colspan="2">
  {if $pickupsCount>0}
      <table width="100%" class="UD_form" cellpadding="4">
        <tr class="UD_form_header">
          <td>Picked-up on date...</td>
          <td>...from remote address...</td>
          <td>...by recipient.</td>
        </tr>
    {foreach from=$pickups item=p}
        <tr class="UD_form_lined" valign="middle">
          <td class=\"UD_form_lined\"><tt>{$p.pickupDate|date_format:"%d %b %Y&nbsp;&nbsp;%r"}</tt></td>
          <td class=\"UD_form_lined\">{$p.hostname|default:"&nbsp;"}</td>
          <td>{$p.pickedUpBy|default:"&nbsp;"}</td>
        </tr>
    {/foreach}
        <tr class="UD_form_footer">
          <td colspan="3" align="center">{$pickupsCount} pickup{if $pickupsCount ne 1}s{/if}</td>
        </tr>
      </table>
  {else}
    None of the files have been picked-up yet.
  {/if}
    </td>
  </tr>
{/if}
</table>

{include file="footer.tpl"}
