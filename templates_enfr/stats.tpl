{include file="header.tpl"}

{if $isAuthorizedUser && $isStatsUser}

<blockquote>
  <form name="periodForm" method="get" action="{$zendToURL}stats.php">
  <table border="0">
    <tr>
      <td>View stats for the</td>
      <td>
        <select name="period" onchange="return document.periodForm.submit();">
          <option value="week"{if $period eq 7} selected="selected"{/if}>past week</option>
          <option value="month"{if $period eq 30} selected="selected"{/if}>past month</option>
          <option value="90days"{if $period eq 90} selected="selected"{/if}>past 90 days</option>
          <option value="year"{if $period eq 365} selected="selected"{/if}>past year</option>
          <option value="decade"{if $period eq 3650} selected="selected"{/if}>past 10 years</option>
        </select>
      </td>
    </tr>
  </table>
  </form>

  <hr/>

  <table border="0">
    <tr>
      <td><b>Number of dropoffs made (checked daily)</b></td>
    </tr>
    <tr>
      <td><img src="{$zendToURL}graph.php?m=dropoff_count&p={$period}" alt="[dropoff counts]"/></td>
    </tr>

    <tr>
      <td><b>Total amount of data dropped off (checked daily)</b></td>
    </tr>
    <tr>
      <td><img src="{$zendToURL}graph.php?m=total_size&p={$period}" alt="[total dropoff bytes]"/></td>
    </tr>

    <tr>
      <td><b>Total files dropped off (checked daily)</b></td>
    </tr>
    <tr>
      <td><img src="{$zendToURL}graph.php?m=total_files&p={$period}" alt="[total dropoff files]"/></td>
    </tr>

    <tr>
      <td><b>File count per dropoff (checked daily)</b></td>
    </tr>
    <tr>
      <td><img src="{$zendToURL}graph.php?m=files_per_dropoff&p={$period}" alt="[files per dropoff]"/></td>
    </tr>
  </table>
</blockquote>


{else}

<h5>This feature is only accessible by administrators who have logged-in
    to the system.</h5>

{/if}

{include file="footer.tpl"}
