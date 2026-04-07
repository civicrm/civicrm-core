{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

<div id="case_dashboard_dashlet" class="form-item">

{capture assign=newCaseURL}{crmURL p="civicrm/case/add" q="action=add&context=standalone&reset=1"}{/capture}

<div class="float-right">
  <table class="form-layout-compressed">
   {if $newClient}
    <tr>
      <td>
        <a href="{$newCaseURL}" class="button">
          <span><i class="crm-i fa-plus-circle" role="img" aria-hidden="true"></i> {ts}New Case{/ts}</span>
        </a>
      </td>
    </tr>
   {/if}
   {if $myCases}
    <tr>
      <td class="right">
        <a href="{crmURL p="civicrm/case" q="reset=1&all=1"}"><span><i class="crm-i fa-chevron-right" role="img" aria-hidden="true"></i> {ts}Show ALL Cases with Upcoming Activities{/ts}</span></a>
      </td>
    </tr>
   {else}
    <tr>
      <td class="right">
        <a href="{crmURL p="civicrm/case" q="reset=1&all=0"}"><span><i class="crm-i fa-chevron-right" role="img" aria-hidden="true"></i> {ts}Show My Cases with Upcoming Activities{/ts}</span></a>
      </td>
    </tr>
   {/if}
   <tr>
     <td class="right">
       <a href="{crmURL p="civicrm/case/search" q="reset=1&case_owner=1&force=1"}"><span><i class="crm-i fa-chevron-right" role="img" aria-hidden="true"></i> {ts}Show My Cases{/ts}</span></a>
     </td>
   </tr>
  </table>
</div>

<h3>{ts}Summary of Involvement{/ts}</h3>

<table class="report">
  <tr class="columnheader">
    <th>&nbsp;</th>
    {foreach from=$casesSummary.headers item=header}
    <th scope="col" class="right" style="padding-right: 10px;"><a href="{$header.url}">{$header.status}</a></th>
    {/foreach}
  </tr>
  {foreach from=$casesSummary.rows item=row key=caseType}
   <tr>
   <th><strong>{$caseType}</strong></th>
   {foreach from=$casesSummary.headers item=header}
    {assign var="caseStatus" value=$header.status}
    <td class="label">
    {if $row.$caseStatus}
    <a href="{$row.$caseStatus.url}">{$row.$caseStatus.count}</a>
    {else}
     0
    {/if}
    </td>
   {/foreach}
  </tr>
  {/foreach}
</table>

</div>
