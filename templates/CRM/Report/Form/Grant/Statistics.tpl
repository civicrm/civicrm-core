{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
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

    {if $printOnly}
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
        <th class="statistics right" scope="row">Count</th>
        <th class="statistics right" scope="row">Amount</th>
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
         <th class="statistics right" scope="row">Number of Grants (%)</th>
         <th class="statistics right" scope="row">Total Amount (%)</th>
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
