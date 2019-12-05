{if $jobLastRunWarning == 1}
  <h3>{if $jobOverdue != ''}{ts}Warning!{/ts}{else}{ts}Cron Running{/ts}{/if}</h3>
  <p>The current time is {$currentTime}</p>
  <p>Your iATS Payments extension version is {$currentVersion}</p>
  <p>{ts 1=$jobLastRun}Your iATS Payments cron last ran at %1.{/ts}</p>
  <p>{if $jobOverdue != ''}<strong style="font-size: 120%">{ts}Your recurring contributions for iATS Payments requires a correctly setup and functioning cron job that runs daily. You need to take action now.{/ts}</strong>{else}{ts}It's all good.{/ts}{/if}</p>
{/if}


<h3>Recent transations using iATS Payments</h3>
<form method="GET">
  <fieldset><legend>Filter results</legend>
    <div><em>Filter your results by any part of the last 4 digits of a Card Number or the Authorization Result:</em></div>
    <table>
      <tr>
        <td>Card Number (last 4 digits): <input size="4" type="text" name="search_cc" value="{$search.cc}"></td>
        <td>Authorization Result: <input size="11" type="text" name="search_auth_result" value="{$search.auth_result}"></td>
        <td><input type="submit" value="Filter" class='crm-button'></td>
     </tr>
    </table>
  </fieldset>
</form>

<table class="iats-report">
  <caption>Recent transactions with the IATS Payment Processor</caption>
  <tr>
    <th>{ts}Invoice{/ts}</th>
    <th>{ts}Contact{/ts}</th>
    <th>{ts}Request IP{/ts}</th>
    <th>{ts}Card Number{/ts}</th>
    <th>{ts}Total{/ts}</th>
    <th>{ts}Request DateTime{/ts}</th>
    <th>{ts}Result{/ts}</th>
    <th>{ts}Transaction ID{/ts}</th>
    <th>{ts}Response DateTime{/ts}</th>
  </tr>
  {foreach from=$iATSLog item=row}
    <tr>
      {if $row.contributionURL != ''}
      <td><a href="{$row.contributionURL}">{$row.invoice_num}</a></td>
      {else}
      <td>{$row.invoice_num}</td>
      {/if}
      {if $row.contactURL != ''}
      <td><a href="{$row.contactURL}">{$row.sort_name}</a></td>
      {else}
      <td></td>
      {/if}
      <td>{$row.ip}</td>
      <td>{$row.cc}</td>
      <td>{$row.total}</td>
      <td>{$row.request_datetime}</td>
      <td>{$row.auth_result}</td>
      <td>{$row.remote_id}</td>
      <td>{$row.response_datetime}</td>
    </tr>
  {/foreach}
</table>
