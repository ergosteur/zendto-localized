{include file="header.tpl"}

{if $isAuthorizedUser}
  <!-- User has logged in -->
<table border="0">  <tr><td colspan="2">&nbsp;</td></tr>
  <tr>
    <td>{call name=button href="about.php" text="About {#ServiceTitle#}" width="100%"}</td>
    <td class="UD_nav_label">What <i>is</i> {#ServiceTitle#}?</td>
  </tr>
  <tr>
    <td colspan="2">&nbsp;<!-- {$isLocalIP} --></td>
  </tr>
  <tr>
    <td colspan="2"><h4>You may perform the following activities:</h4></td>
  </tr>
  <tr>
    <td>{call name=button href="verify.php" text="Drop-off" width="100%"}</td>
    <td class="UD_nav_label">Drop-off (<i>upload</i>) a file for someone else.</td>
  </tr>
  <tr>
    <td>{call name=button href="pickup.php" text="Pick-up" width="100%"}</td>
    <td class="UD_nav_label">Pick-up (<i>download</i>) a file dropped-off for you.</td>
  </tr>
  <tr><td colspan="2">&nbsp;</td></tr>
  <tr>
    <td>{call name=button href="pickup_list.php" text="Drop-offs for Me" width="100%"}</td>
    <td class="UD_nav_label">Display a list of drop-offs that were sent to you.</td>
  </tr>
  <tr>
    <td>{call name=button href="dropoff_list.php" text="Drop-offs by Me" width="100%"}</td>
    <td class="UD_nav_label">Display a list of drop-offs that you have created.</td>
  </tr>
  <tr><td colspan="2">&nbsp;</td></tr>
  <tr>
    <td>{call name=button href="req.php" text="Request a Drop-off" width="100%"}</td>
    <td class="UD_nav_label">Ask another person to send you some files.</td>
  </tr>
{if $isAdminUser}
  <tr>
    <td>{call name=button href="pickup_list_all.php" text="Show All Drop-offs" width="100%" admin=$isAdminUser}</td>
    <td class="UD_nav_label">View all drop-offs in the database (<i>Administrators only</i>).</td>
  </tr>
  <tr>
    <td>{call name=button href="unlock.php" text="Unlock Users" width="100%" admin=$isAdminUser}</td>
    <td class="UD_nav_label">Unlock locked-out users (<i>Administrators only</i>).</td>
  </tr>
  <tr>
    <td>{call name=button href="stats.php" text="System Statistics" width="100%" admin=$isAdminUser}</td>
    <td class="UD_nav_label">View daily statistics for the dropbox (<i>Administrators only</i>).</td>
  </tr>
{/if}

  <tr><td colspan="2">&nbsp;</td></tr>
  <tr>
    <td>{call name=button href="?action=logout" text="Logout" width="100%"}</td>
    <td class="UD_nav_label">Logout from {#ServiceTitle#}.</td>
  </tr>
</table>

{else}
  <!-- Not logged in. -->

<table border="0">
  <tr><td colspan="2">&nbsp;</td></tr>
  <tr>
    <td>{call name=button href="about.php" text="About {#ServiceTitle#}" width="100%"}</td>
    <td class="UD_nav_label">What <i>is</i> {#ServiceTitle#}?</td>
  </tr>
  <tr><td colspan="2">&nbsp;</td></tr>
  <tr><td colspan="2"><h4>If you are {#LocalUser#}, you may login here:</h4></td></tr>
  <tr>
    <td>{call name=button href="?action=login" text="Login" width="100%"}</td>
    <td class="UD_nav_label"><b>Avoid having to verify your email address</b>,<br />and drop-off files to {#NonLocalUsers#}.</td>
  </tr>
  <tr><td colspan="2">&nbsp;</td></tr>
  <tr><td colspan="2"><h4>Anyone may perform the following activities:</h4></td></tr>
  <tr>
    <td>{call name=button href="verify.php" text="Drop-off" width="100%"}</td>
    <td class="UD_nav_label">Drop-off (<i>upload</i>) a file for {#LocalUser#} (<b>email verification required</b>).</td>
  </tr>
  <tr>
    <td>{call name=button href="pickup.php" text="Pick-up" width="100%"}</td>
    <td class="UD_nav_label">Pick-up (<i>download</i>) a file dropped-off for you.</td>
  </tr>
  <tr><td colspan="2">&nbsp;</td></tr>
</table>

{/if}

{include file="footer.tpl"}
