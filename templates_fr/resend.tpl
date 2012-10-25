{include file="header.tpl"}

{if $success}
  <h3>The dropoff <!--with claim ID {$claimID} -->was successfully re-sent to its recipients.</h3>
{else}
  <h3>Unable to re-send the dropoff<!-- {$claimID}-->. Please contact the system
      administrator.</h3>
{/if}

{include file="footer.tpl"}
