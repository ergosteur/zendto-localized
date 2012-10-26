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
  <table border="0" cellpadding="4"><tr>
  <td>{call name=button href="{$zendToURL}" text="Return to the {#ServiceTitle#} main menu"}</td>
  {if $isAuthorizedUser}<td>{call name=button href="{$zendToURL}?action=logout" text="Logout"}</td>{/if}</tr>
  </table>
{/function}


