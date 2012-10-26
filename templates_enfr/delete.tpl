{include file="header.tpl"}

{if $success}
  <h3>The dropoff with claim ID {$claimID} was successfully removed.</h3>
{else}
  <h3>Unable to remove the dropoff {$claimID}. Please contact the system
      administrator.</h3>
{/if}

{include file="footer.tpl"}
