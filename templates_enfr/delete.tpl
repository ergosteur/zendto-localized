{include file="header.tpl"}

{if $success}
  <h3>Le partage ayant pour ID {$claimID} a été supprimé. <br/>The dropoff with claim ID {$claimID} was successfully removed.</h3>
{else}
  <h3>Impossible de supprimer le partage {$claimID}. Veuillez contacter l'aide technique.<br/>Unable to remove the dropoff {$claimID}. Please contact the system
      administrator.</h3>
{/if}

{include file="footer.tpl"}
