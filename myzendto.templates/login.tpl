{include file="header.tpl"}

<script type="text/javascript">
$(document).ready(function(){
	bindEnter($('input[name=password]'), function(){
		$('form[name=login]').submit();
	});
});
</script>
{if $isLocalIP}
<h4>Please login above.</h4>
{else}
<br><br><br>
<br><br><br>
<center>
  <form name="login" method="post" action="{$zendToURL}">
  <input type="hidden" name="action" value="login">
  <table class="UD_form" cellpadding="4">
    <tr class="UD_form_header">
      <td colspan="2">Please Login</td>
    </tr>
    <tr>
      <td align="right"><b>Your {#Username#}:</b></td>
      <td><input type="text" id="uname" name="uname" size="15" value=""/></td>
    </tr>
    <tr>
      <td align="right"><b>Your Password:</b></td>
      <td><input type="password" name="password" size="15" value=""/></td>
    </tr>
    <tr class="footer">
      <td colspan="2" align="center">
        <script type="text/javascript">
          function submitform() { document.login.submit(); }
        </script>
        <button onclick="javascript:submitform();" style="width:110px">Login</button>
      </td>
    </tr>
  </table>
  </form>
</center>
{/if}

{include file="footer.tpl"}
