{include file="header.tpl"}

<script type="text/javascript">
<!--
$(document).ready(function() {
    $('#pickup_list').dataTable( {
       "sPaginationType": "full_numbers",
       "aaSorting": [[ 1, "desc" ]],
       "aoColumns": [
       { "bSortable": false, "sTitle": "Claim ID", "sClass": "left" },
       { "sTitle": "Created", "sType": "date-euro", "sClass": "center" },
       { "sTitle": "Files", "sClass": "left" },
       { "sTitle": "Size", "iDataSort":  5, "sClass": "left" },
       { "sTitle": "Delete", "sClass": "center" },
       { "bVisible":  false }
       ]
    } );
} );

function sortByDate() {
  if (document.list.sortOrder.value.substring(0,2) == "fd") {
    document.list.sortOrder.value = 'rdate';
  } else {
    document.list.sortOrder.value = 'fdate';
  }
  return document.list.submit();
}
function sortByFile() {
  if (document.list.sortOrder.value.substring(0,2) == "ff") {
    document.list.sortOrder.value = 'rfile';
  } else {
    document.list.sortOrder.value = 'ffile';
  }
  return document.list.submit();
}
function sortBySize() {
  if (document.list.sortOrder.value.substring(0,2) == "fs") {
    document.list.sortOrder.value = 'rsize';
  } else {
    document.list.sortOrder.value = 'fsize';
  }
  return document.list.submit();
}
function doDelete(id,code,name)
{
  if ( confirm("Do you really want to delete this dropoff?" + '\n' + name) ) {
    document.deleteDropoff.claimID.value = id;
    document.deleteDropoff.claimPasscode.value = code;
    return document.deleteDropoff.submit();
  }
  return 0;
}
-->
</script>

{if $isAuthorizedUser}
  {if $countDropoffs>0}
<h5>Click on a drop-off claim ID to view the information and files for that drop-off.<br/>Click on the "Created" or "Files" headings to change the sort order. Click again to reverse the order.</h5>
<table id="pickup_list" width="100%">
	<thead>
    <td>Claim ID</td>
    <td>Created</td>
    <td>Files</td>
    <td>Size</td>
    <td>Delete</td>
   </thead>

    {foreach from=$dropoffs item=d}
<!--  <tr valign="middle" class="UD_form_lined {cycle values="row,rowalt"}">
    <td class="UD_form_lined"><a href="#" onmouseover="document.body.style.cursor = 'pointer';" onmouseout="document.body.style.cursor = 'auto';" onclick="doPickup('{$d.claimID}');"><tt>{$d.claimID}</tt></a></td>
    <td class="UD_form_lined"><div style="white-space: nowrap"><tt>{$d.createdDate|date_format:"%d %b %Y&nbsp;&nbsp;%r"}</tt></div></td>
    <td class="UD_form_lined">{$d.note}</td>
    <td class="UD_form_lined">{$d.formattedBytes|replace:' ':'&nbsp;'}</td>
    <td class="UD_form_lined"><a href="javascript:doDelete('{$d.claimID}','{$d.claimPasscode}','{$d.note}');"><img src="{$zendToURL}images/trashcan16.png" height="16" width="16" border="0" alt="delete"/></a></td>
  </tr> -->
  <tr>
    <td><a class="hoverlink" href="#" onclick="doPickup('{$d.claimID}');">{$d.claimID}</a></td>
    <td>{$d.createdDate|date_format:"%d/%m/%Y %H:%M:%S"}</td>
    <td>{$d.note}</td>
    <td>{$d.formattedBytes}</td>
    <td><a href="javascript:doDelete('{$d.claimID}','{$d.claimPasscode}','{$d.note}');"><img src="{$zendToURL}images/trashcan16.png" height="16" width="16" border="0" alt="delete"/></a></td>
    <td>{$d.Bytes}</td>
  </tr>
    {/foreach}

<!--  <tr class="UD_form_footer">
    <td colspan="4" align="center">{$countDropoffs} drop-off{if $countDropoffs ne 1}s{/if}</td>
  </tr>
  <tr class="UD_form_footer">
    <td colspan="4" align="center">{$remainingQuota} quota remaining</td>
  </tr>
-->
</table>

<br/>
<p>{$remainingQuota} quota remaining</p>

  <form name="pickup" method="post" action="{$zendToURL}pickup.php">
    <input type="hidden" id="claimID" name="claimID" value=""/>
  </form>
  <form name="list" method="post" action="{$zendToURL}pickup_list.php">
    <input type="hidden" id="sortOrder" name="sortOrder" value="{$sortOrder}"/>
  </form>

  {else}
<h5>There are no drop-offs available for you at this time.</h5>
<h5>{$remainingQuota} quota remaining</h5>
  {/if}

  <form name="deleteDropoff" method="post" action="{$zendToURL}delete.php">
    <input type="hidden" id="claimID" name="claimID" value=""/>
    <input type="hidden" id="claimPasscode" name="claimPasscode" value=""/>
    <input type="hidden" id="next" name="next" value="index"/>
  </form>

{/if}

{include file="footer.tpl"}
