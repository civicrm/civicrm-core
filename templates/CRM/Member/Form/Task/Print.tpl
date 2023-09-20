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
<div class="form-item">
     <span class="element-right">{include file="CRM/common/formButtons.tpl" location="top"}</span>
</div>
<div class="spacer"></div>
<br />
<p>
<table>
  <tr class="columnheader">
    <th>{ts}Name{/ts}</th>
    <th>{ts}Type{/ts}</th>
    <th>{ts}Member Since{/ts}</th>
    <th>{ts}Membership Start Date{/ts}</th>
    <th>{ts}Membership Expiration Date{/ts}</th>
    <th>{ts}Membership Source{/ts}</th>
    <th>{ts}Status{/ts}</th>
  </tr>
{foreach from=$rows item=row}
    <tr class="{cycle values="odd-row,even-row"} crm-membership">
        <td class="crm-membership-sort_name">{$row.sort_name}</td>
        <td class="crm-membership-type crm-membership-type_{$row.membership_type}">{$row.membership_type}</td>
        <td class="crm-membership-join_date">{$row.join_date|truncate:10:''|crmDate}</td>
        <td class="crm-membership-start_date">{$row.membership_start_date|truncate:10:''|crmDate}</td>
        <td class="crm-membership-membership_end_date">{$row.membership_end_date|truncate:10:''|crmDate}</td>
        <td class="crm-membership-source">{$row.membership_source}</td>
        <td class="crm-membership-status crm-membership-status_{$row.membership_status}">{$row.membership_status}</td>
    </tr>
{/foreach}
</table>

<div class="form-item">
     <span class="element-right">{include file="CRM/common/formButtons.tpl" location="bottom"}</span>
</div>

{else}
   <div class="messages status no-popup">
    <img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}">
        {ts}There are no records selected for Print.{/ts}
   </div>
{/if}
