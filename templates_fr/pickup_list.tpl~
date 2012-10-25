{include file="header.tpl"}

{if $isAuthorizedUser}

  {if $countDropoffs>0}
<h1>Inbox</h1>
<h5>Click on a drop-off claim ID to view the information and files for that drop-off.</h5>
<table class="UD_form" cellpadding="4" width="100%">
	<thead class="UD_form_header">
   	 <td>Claim ID</td>
   	 <td>Sender</td>
         <td>Size</td>
   	 <td>Created</td>
	</thead>

    {foreach from=$dropoffs item=d}
  <tr valign="middle" class="UD_form_lined {cycle values="row,rowalt"}">
    <td class="UD_form_lined"><a class="hoverlink" href="#" onclick="doPickup('{$d.claimID}');"><tt>{$d.claimID}</tt></a></td>
    <td class="UD_form_lined">{$d.senderName}, {$d.senderOrg} ({$d.senderEmail})</td>
    <td class="UD_form_lined">{$d.formattedBytes|replace:' ':'&nbsp;'}</td>
    <td><div style="white-space: nowrap"><tt>{$d.createdDate|date_format:"%d %b %Y&nbsp;&nbsp;%r"}</tt></div></td>
  </tr>
    {/foreach}

  <tr class="UD_form_footer">
    <td colspan="3" align="center">{$countDropoffs} drop-off{if $countDropoffs ne 1}s{/if} {$formattedTotalBytes}</td>
  </tr>
</table>

<br/>
  <form name="pickup" method="post" action="{$zendToURL}pickup.php">
  <input type="hidden" id="claimID" name="claimID" value=""/>
</form>

  {else}
<h5>There are no drop-offs available for you at this time.</h5>
  {/if}

{/if}

{include file="footer.tpl"}
