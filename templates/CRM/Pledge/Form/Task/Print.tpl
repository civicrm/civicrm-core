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
<div class="form-item">
     <span class="element-right">{$form.buttons.html}</span>
</div>
<div class="spacer"></div>
<br />
<p>
<table>
  <tr class="columnheader">
    <th>{ts}Name{/ts}</th>
    <th>{ts}Amount{/ts}</th>
    <th>{ts}Create Date{/ts}</th>
    <th>{ts}To Be Paid{/ts}</th>
    <th>{ts}Beginning Date{/ts}</th>
    <th>{ts}Status{/ts}</th>
  </tr>
{foreach from=$rows item=row}
    <tr class="{cycle values="odd-row,even-row"}">
        <td class="crm-pledge-sort_name" >{$row.sort_name}</td>
        <td class="crm-pledge-pledge_amount">{$row.pledge_amount|crmMoney}</td>
        <td class="crm-pledge-pledge_create_date">{$row.pledge_create_date|truncate:10:''|crmDate}</td>
        <td class="crm-pledge-pledge_frequency_interval">{$row.pledge_frequency_interval} {$row.pledge_frequency_unit|capitalize:true}(s) </td>
        <td class="crm-pledge-.pledge_start_date">{$row.pledge_start_date|truncate:10:''|crmDate}</td>
        <td class="crm-pledge-pledge_status">{$row.pledge_status_id}</td>
    </tr>
{/foreach}
</table>

<div class="form-item">
     <span class="element-right">{$form.buttons.html}</span>
</div>

{else}
   <div class="messages status no-popup">
    <div class="icon inform-icon"></div>
        {ts}There are no records selected for Print.{/ts}
   </div>
{/if}
