{include file="header.tpl"}

{if $isAuthorizedUser}
  <!-- User has logged in -->
<table border="0" class="homeButtons">
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
    <td>{call name=button href="req.php" text="Request a Drop-off" width="100%"}</td>
    <td class="UD_nav_label">Ask another person to send you some files.</td>
  </tr>
{if $isStatsUser}
	<tr><td colspan="2">&nbsp;</td></tr>
{/if}
{if $isAdminUser}
  <tr>
    <td>{call name=button href="pickup_list_all.php" text="Show All Drop-offs" width="100%" admin=$isAdminUser}</td>
    <td class="UD_nav_label">View all drop-offs in the database (<i>Administrators only</i>).</td>
  </tr>
  <tr>
    <td>{call name=button href="unlock.php" text="Unlock Users" width="100%" admin=$isAdminUser}</td>
    <td class="UD_nav_label">Unlock locked-out users (<i>Administrators only</i>).</td>
  </tr>
{/if}
{if $isStatsUser}
  <tr>
    <td>{call name=button href="stats.php" text="System Statistics" width="100%" admin=$isStatsUser}</td>
    <td class="UD_nav_label">View daily statistics for the dropbox (<i>Administrators only</i>).</td>
  </tr>
{/if}
{if $isAdminUser}
  <tr>
    <td>{call name=button href="log.php" text="System Log" width="100%" admin=$isAdminUser}</td>
    <td class="UD_nav_label">View log file (<i>Administrators only</i>).</td>
  </tr>
{/if}

  <tr><td colspan="2">&nbsp;</td></tr>
</table>

{else}
  <!-- Not logged in. -->
<table border="0" class="homeButtons">
  <tr><td colspan="2"><h4>If you are {#LocalUser#}, you may login here:</h4></td></tr>
  <tr>
    <td>{call name=button href="index.php?action=login" text="Login" width="100%"}</td>
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

<h4>Help</h4>

<p>{#ServiceTitle#} is a service to make it easy for you to move files, including large files up to {$maxFileSize}, in and out of {#OrganizationType#}.</p>
<ul>
  <li>If you are a member of {#OrganizationType#}, you can log in with your {#Username#} and password and send files to anyone, in or out of {#OrganizationType#}.<br/>Start by logging in and then clicking the "<em>Drop-off</em>" button.</li>
  <li>If you are not a member of {#OrganizationType#}, you cannot log in but you can still send files to people in {#OrganizationType#} if you know their email address.<br/>Start by clicking the "<em>Drop-off</em>" button.</li>
  <li>If you are a member of {#OrganizationType#} and wish to ask someone outside {#OrganizationType#} to send you some files, you can make the process a lot easier for them by logging in and then clicking the "<em>Request a Drop-off</em>" button.<br/>This means the other person does not have to pass any tests to prove who they are, which makes the whole process a lot quicker for them.</li>
  <li>Files are automatically deleted from {#ServiceTitle#} {$keepForDays} days after you upload them, so you don't need to manually clean up.</li>
</ul>

{include file="footer.tpl"}
