{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* CiviCase DashBoard (launch page) *}

<div class="crm-block crm-content-block">
  {if $notConfigured} {* Case types not present. Component is not configured for use. *}
    {include file="CRM/Case/Page/ConfigureError.tpl"}
  {else}

    {capture assign=newCaseURL}{crmURL p="civicrm/case/add" q="action=add&context=standalone&reset=1"}{/capture}

    <div class="crm-submit-buttons crm-case-dashboard-buttons float-right">
      {crmPermission has='access all cases and activities'}
        <div class="crm-case-dashboard-switch-view-buttons">
          <div class="crm-form-toggle-container">
            <span class="crm-form-toggle-text">{ts}Show Only My Cases{/ts}</span>
            <input name="allupcoming" {if $myCases}checked{/if} type="checkbox" class="crm-form-toggle" onClick='window.location.replace(CRM.url("civicrm/case", "reset=1&all=" + (this.checked ? "0" : "1")))' value="1">
          </div>
        </div>
      {/crmPermission}
      {if $newClient and $allowToAddNewCase}
        <a href="{$newCaseURL}" class="button"><span><i class="crm-i fa-plus-circle" role="img" aria-hidden="true"></i> {ts}Add Case{/ts}</span></a>
      {/if}
      <a class="button no-popup" name="find_my_cases" href="{crmURL p="civicrm/case/search" q="reset=1&case_owner=2&force=1"}"><span><i class="crm-i fa-search" role="img" aria-hidden="true"></i> {ts}Find My Cases{/ts}</span></a>
    </div>
    <h3>{ts}Case Summary{/ts}</h3>
    <table class="report">
      <tr class="columnheader">
        <th>&nbsp;</th>
        {foreach from=$casesSummary.headers item=header}
          <th scope="col" class="right" style="padding-right: 10px;"><a href="{$header.url}">{$header.status}</a></th>
        {/foreach}
      </tr>
      {foreach from=$casesSummary.rows item=row key=caseType}
        <tr class="crm-case-caseStatus">
          <th><strong>{$caseType}</strong></th>
          {foreach from=$casesSummary.headers item=header}
            {assign var="caseStatus" value=$header.status}
            <td class="label">
              {if is_array($row.$caseStatus)}
                <a class="crm-case-summary-drilldown" href="{$row.$caseStatus.url}">{$row.$caseStatus.count}</a>
              {else}
                0
              {/if}
            </td>
          {/foreach}
        </tr>
      {/foreach}
    </table>
    {capture assign=findCasesURL}<a href="{crmURL p="civicrm/case/search" q="reset=1"}">{ts}Find Cases{/ts}</a>{/capture}

    <div class="spacer"></div>
    <h3>{if $myCases}{ts}My Cases With Upcoming Activities{/ts}{else}{ts}All Cases With Upcoming Activities{/ts}{/if}</h3>
    {if $upcomingCases}
      {include file="CRM/Case/Form/CaseFilter.tpl" context="$context" list="upcoming"}
      <div class="form-item">
        {include file="CRM/Case/Page/DashboardSelector.tpl" context="dashboard" list="upcoming" all="$all"}
      </div>
    {else}
      <div class="messages status no-popup">
        {ts 1=$findCasesURL}There are no open cases with activities scheduled in the next two weeks. Use %1 to expand your search.{/ts}
      </div>
    {/if}

    <div class="spacer"></div>
    <h3>{if $myCases}{ts}My Cases With Recently Performed Activities{/ts}{else}{ts}All Cases With Recently Performed Activities{/ts}{/if}</h3>
    {if $recentCases}
      {include file="CRM/Case/Form/CaseFilter.tpl" context="$context" list="recent"}
      <div class="form-item">
        {include file="CRM/Case/Page/DashboardSelector.tpl" context="dashboard" list="recent" all="$all"}
      </div>
    {else}
      <div class="messages status no-popup">
        {ts 1=$findCasesURL}There are no cases with activities scheduled in the past two weeks. Use %1 to expand your search.{/ts}
      </div>
    {/if}
  {/if}
</div>
