{include file="header.tpl"}

{if $success}
  <h3>Le partage ayant pour ID {$claimID} a été supprimé. </h3>
{else}
  <h3>Impossible de supprimer le partage {$claimID}. Veuillez contacter l'aide technique.</h3>
{/if}

{include file="footer.tpl"}
