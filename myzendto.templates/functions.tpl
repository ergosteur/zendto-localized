{function name=button href="" text="&nbsp;" width="" admin="" relative=TRUE}
  {if $width ne ""}
    {$width=" width=\"$width\""}
  {/if}
  {if $admin}
    {$admin="_admin"}
  {else}
    {$admin=""}
  {/if}
  {if $relative}
    {$href = "$zendTOURL$href"}
  {/if}
  <table{$width} class="UD_textbutton">
    <tr valign="middle">
      <td class="UD_textbutton_left{$admin}"><a class="UD_textbuttonedge" href="{$href}">&nbsp;</a></td>
      <td class="UD_textbutton_content{$admin}" align="center"><a class="UD_textbutton{$admin}" href="{$href}">{$text}</a></td>
      <td class="UD_textbutton_right{$admin}"><a class="UD_textbuttonedge" href="{$href}">&nbsp;</a></td>
    </tr>
  </table>
{/function}

{function name=footerButtons}
  {if $isAuthorizedUser}
	<button onclick="window.location='{$zendToURL}'">My Dropoffs</button>
	<button onclick="window.location='{$zendToURL}dropoff.php'">New Dropoff</button>
  {/if}

  {if $isAdminUser}
  <button class="UD_textbutton_admin" onclick="window.location='pickup_list_all.php'">Show All Dropoffs</button>
  <button class="UD_textbutton_admin" onclick="window.location='unlock.php'">Unlock Users</button>
  <button class="UD_textbutton_admin" onclick="window.location='stats.php'">System Statistics</button>
  <button class="UD_textbutton_admin" onclick="window.location='log.php'">System Log</button>
  {/if}

{/function}


