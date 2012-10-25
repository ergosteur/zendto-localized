<!-- End page content -->
</div>

<br />&nbsp;
<br />

{if $isIndexPage == FALSE}
  {call name=footerButtons}
{/if}

<table width="100%" class="UD_footer">
  <tr valign="bottom">
    <td id="UD_footer_text">Version {$ztVersion}&nbsp;|&nbsp;{#ContactInfo#}{if $whoAmI ne ""}&nbsp;|&nbsp;you are currently logged in as <i>{$whoAmI}</i>{/if}</td>
{if $isAdminUser}
    <td id="UD_footer_right_admin" rowspan="2">&nbsp;</td>
{else}
    <td id="UD_footer_right" rowspan="2">&nbsp;</td>
{/if}
  </tr>
  <tr>
    <td id="UD_footer_bottom">&nbsp;</td>
  </tr>
</table>

</body>
</html>
