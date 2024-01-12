{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<p>

{if $rows}
<div class="crm-submit-buttons">
     <span class="element-right">{include file="CRM/common/formButtons.tpl" location="top"}</span>
</div>
<div class="spacer"></div>
<br />
<p>
<table>
  <tr class="columnheader">
    <th>{ts}Client{/ts}</th>
    <th>{ts}Status{/ts}</th>
    <th>{ts}Case Type{/ts}</th>
    <th>{ts}My Role{/ts}</th>
    <th>{ts}Most Recent Activity{/ts}</th>
    <th>{ts}Next Scheduled Activity{/ts}</th>
  </tr>

{foreach from=$rows item=row}
    <tr class="{cycle values="odd-row,even-row"}">
        <td class="crm-case-print-sort_name">{$row.sort_name}<br /><span class="description">{ts}Case ID{/ts}: {$row.case_id}</span></td>
        <td class="crm-case-print-case_status_id">{$row.case_status_id}</td>
        <td class="crm-case-print-case_type_id">{$row.case_type_id}</td>
        <td class="crm-case-print-case_role">{if $row.case_role}{$row.case_role}{else}---{/if}</td>
        <td class="crm-case-print-case_recent_activity_type">{if $row.case_recent_activity_type}
      {$row.case_recent_activity_type}<br />{$row.case_recent_activity_date|crmDate}{else}---{/if}</td>
        <td class="crm-case-print-case_scheduled_activity_type">{if $row.case_scheduled_activity_type}
      {$row.case_scheduled_activity_type}<br />{$row.case_scheduled_activity_date|crmDate}{else}---{/if}</td>
    </tr>
{/foreach}
</table>

<div class="crm-submit-buttons">
     <span class="element-right">{include file="CRM/common/formButtons.tpl" location="bottom"}</span>
</div>

{else}
   <div class="messages status no-popup">
    {icon icon="fa-info-circle"}{/icon}
        {ts}There are no records selected for Print.{/ts}
    </div>
{/if}
