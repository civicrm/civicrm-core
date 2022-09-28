{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $section eq 1}
    <div class="crm-block crm-content-block crm-report-layoutGraph-form-block">
        {*include the graph*}
        {include file="CRM/Report/Form/Layout/Graph.tpl"}
    </div>
{else}
    <div class="crm-block crm-form-block crm-report-field-form-block">
        {include file="CRM/Report/Form/Fields.tpl" componentName='Grant'}
    </div>

    <div class="crm-block crm-content-block crm-report-form-block">
        {*include actions*}
        {include file="CRM/Report/Form/Actions.tpl"}

        {*include the graph*}
        {include file="CRM/Report/Form/Layout/Graph.tpl"}

    {if !empty($printOnly)}
        <h1>{$reportTitle}</h1>
        <div id="report-date">{$reportDate}</div>
    {/if}

    {if !empty($totalStatistics)}
          <h3>{ts}Report Filters{/ts}</h3>
          <table class="report-layout statistics-table">
          {if $totalStatistics.filters}
              {foreach from=$totalStatistics.filters item=row}
                <tr>
                    <th class="statistics">{$row.title}</th>
                    <td>{$row.value}</td>
                </tr>
              {/foreach}
          {else}
            ( {ts}All Grants{/ts} )
          {/if}
          </table>

    <h3>{ts}Summary Statistics{/ts}</h2>
    <table class="report-layout display">
      <tr>
        <th class="statistics" scope="row"></th>
        <th class="statistics right" scope="row">{ts}Count{/ts}</th>
        <th class="statistics right" scope="row">{ts}Amount{/ts}</th>
      </tr>
        {foreach from=$totalStatistics.total_statistics key=key item=val}
           <tr>
             <td>{$val.title}</td>
             <td class="right">{$val.count}</td>
             <td class="right">{$val.amount|crmMoney}</td>
           </tr>
        {/foreach}
    </table>
    {/if}

    {if !empty($grantStatistics)}
    <h3>{ts}Statistics Breakdown{/ts}</h3>
    <table class="report-layout display">
      {foreach from=$grantStatistics item=values key=key}
       <tr>
         <th class="statistics" scope="row">{$values.title}</th>
         <th class="statistics right" scope="row">{ts}Number of Grants (%){/ts}</th>
         <th class="statistics right" scope="row">{ts}Total Amount (%){/ts}</th>
       </tr>
         {foreach from=$values.value item=row key=field}
           <tr>
              <td>{$field}</td>
              <td class="right">{if $row.count}{$row.count} ({$row.percentage}%){/if}</td>
              <td class="right">
                {foreach from=$row.currency key=fld item=val}
                   {$val.value|crmMoney:$fld} ({$val.percentage}%)&nbsp;&nbsp;
                {/foreach}
              </td>
           </tr>
         {if $row.unassigned_count}
           <tr>
              <td>{$field} ({ts}Unassigned{/ts})</td>
              <td class="right">{if $row.unassigned_count}{$row.unassigned_count} ({$row.unassigned_percentage}%){/if}</td>
              <td class="right">
                {foreach from=$row.unassigned_currency key=fld item=val}
                   {$val.value|crmMoney:$fld} ({$val.percentage}%)&nbsp;&nbsp;
                {/foreach}
              </td>
           </tr>
         {/if}
        {/foreach}
        <tr><td colspan="3" style="border: none;">&nbsp;</td></tr>
      {/foreach}
    </table>
    {/if}

    <br />
        {if empty($totalStatistics)}
          {include file="CRM/Report/Form/ErrorMessage.tpl"}
        {/if}
    </div>
{/if}
