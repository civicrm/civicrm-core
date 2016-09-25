{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
    <th>{ts}Start Date{/ts}</th>
    <th>{ts}End Date{/ts}</th>
    <th>{ts}Source{/ts}</th>
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
