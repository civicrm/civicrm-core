{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* CiviContribute DashBoard (launch page) *}
{if $buildTabularView}
{crmRegion name="contribute-dashboard-reports"}
<table class="report">
<tr class="columnheader-dark">
    <th scope="col">{ts}Period{/ts}</th>
    <th scope="col">{ts}Total Amount{/ts}</th>
    <th scope="col" title="{ts}Contribution Count{/ts}"><strong>#</strong></th><th></th></tr>
<tr>
    <td><strong>{ts}Current Month-To-Date{/ts}</strong></td>
    <td class="label">{if NOT $monthToDate.Valid.amount}{ts}(n/a){/ts}{else}{$monthToDate.Valid.amount}{/if}</td>
    <td class="label">{$monthToDate.Valid.count}</td>
    <td><a href="{$monthToDate.Valid.url}">{ts}View details{/ts}...</a></td>
</tr>
<tr>
    <td><strong>{ts}Current Fiscal Year-To-Date{/ts}</strong></td>
    <td class="label">{if NOT $yearToDate.Valid.amount}{ts}(n/a){/ts}{else}{$yearToDate.Valid.amount}{/if}</td>
    <td class="label">{$yearToDate.Valid.count}</td>
    <td><a href="{$yearToDate.Valid.url}">{ts}View details{/ts}...</a></td>
</tr>
<tr>
    <td><strong>{ts}Cumulative{/ts}</strong><br />{ts}(since inception){/ts}</td>
    <td class="label">{if NOT $startToDate.Valid.amount}{ts}(n/a){/ts}{else}{$startToDate.Valid.amount}{/if}</td>
    <td class="label">{$startToDate.Valid.count}</td>
    <td><a href="{$startToDate.Valid.url}">{ts}View details{/ts}...</a></td>
</tr>
</table>
{/crmRegion}

{elseif $buildChart}
{crmRegion name="contribute-dashboard-charts"}
  {include file = "CRM/Contribute/Form/ContributionCharts.tpl"}
{/crmRegion}
{else}
{crmRegion name="contribute-dashboard-summary"}
  <h3>{ts}Contribution Summary{/ts} {help id="id-contribute-intro"}</h3>
      <div id="mainTabContainer" class="ui-tabs ui-widget ui-widget-content ui-corner-all">
        <ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all">
           <li id="chart_view"   class="crm-tab-button ui-state-active ui-corner-top ui-corner-bottom ui-tabs-selected" >
             <a href="#chart_layout"><span>&nbsp;</span>&nbsp;{ts}Chart Layout{/ts}&nbsp;</a> </li>&nbsp;
           <li id ="table_view"  class="crm-tab-button ui-corner-top ui-corner-bottom ui-state-default" >
             <a href="#table_layout"><span>&nbsp;</span>&nbsp;{ts}Table Layout{/ts}&nbsp;</a>
           </li>
{if $isAdmin}
 {capture assign=newPageURL}{crmURL p="civicrm/admin/contribute/add" q="action=add&reset=1"}{/capture}
 {capture assign=configPagesURL}{crmURL p="civicrm/admin/contribute" q="reset=1"}{/capture}

<div class="float-right">
<table class="form-layout-compressed">
  <tr>
    <td>
      <a href="{$configPagesURL|smarty:nodefaults}" class="button no-popup"><span>{ts}Manage Contribution Pages{/ts}</span></a>
    </td>
    <td>
      <a href="{$newPageURL|smarty:nodefaults}" class="button no-popup"><span><i class="crm-i fa-plus-circle" aria-hidden="true"></i> {ts}Add Contribution Page{/ts}</span></a>
    </td>
  </tr>
</table>
</div>
{/if}
</ul>
<div id="chartData"></div>
</div>
{/crmRegion}

{crmRegion name="contribute-dashboard-tables"}
<div class="spacer"></div>
{if $pager->_totalItems}
    <h3>{ts}Recent Contributions{/ts}</h3>
    <div>
        {include file="CRM/Contribute/Form/Selector.tpl" context="dashboard"}
    </div>
{/if}
{/crmRegion}
{/if}
