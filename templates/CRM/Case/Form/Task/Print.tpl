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
<p>

{if $rows }
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
    <div class="icon inform-icon"></div>
        {ts}There are no records selected for Print.{/ts}
    </div>
{/if}
