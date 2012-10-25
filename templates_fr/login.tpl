{include file="header.tpl"}

{if $isLocalIP}
<h4>Veuillez vous connecter ci-dessus.</h4>
{else}
<br><br><br>
<center>
  <form name="login" method="post" action=".">
  <input type="hidden" name="action" value="login">
  <table class="UD_form" cellpadding="4">
    <tr class="UD_form_header">
      <td colspan="2">Authentification</td>
    </tr>
    <tr>
      <td align="right"><b>{#Username#}:</b></td>
      <td><input type="text" id="uname" name="uname" size="15" value=""/></td>
    </tr>
    <tr>
      <td align="right"><b>Mot de passe:</b></td>
      <td><input type="password" id="passwordField" name="password" size="15" value=""/></td>
    </tr>
    <tr class="footer">
      <td colspan="2" align="center">
        <script type="text/javascript">
        	bindEnter($('#passwordField'), function(){ submitform() });
          function submitform() { document.login.submit(); }
        </script>
        {call name=button relative=FALSE href="javascript:submitform();" text="Connexion"}
      </td>
    </tr>
  </table>
  </form>
</center>
{/if}

{include file="footer.tpl"}
