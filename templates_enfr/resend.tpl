{include file="header.tpl"}

{if $success}
  <h3>Le partage <!--ayant pour ID {$claimID} -->a été ré-envoyé.</h3>
{else}
  <h3>Impossible de ré-envoyer <!-- {$claimID}-->. Veuillez contacter
      l'aide technique.</h3>
{/if}

{if $success}
  <h3>The dropoff <!--with claim ID {$claimID} -->was successfully re-sent to its recipients.</h3>
{else}
  <h3>Unable to re-send the dropoff<!-- {$claimID}-->. Please contact the system
      administrator.</h3>
{/if}

{include file="footer.tpl"}
