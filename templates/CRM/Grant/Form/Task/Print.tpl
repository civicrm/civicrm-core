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
<div class="crm-submit-buttons element-right">{$form.buttons.html}</div>
<div class="spacer"></div>
<br />
<p>
<table>
  <tr class="columnheader">
    <td>{ts}Name{/ts}</td>
    <td>{ts}Status{/ts}</td>
    <td>{ts}Type{/ts}</td>
    <td>{ts}Amount Requested{/ts}</td>
    <td>{ts}Amount Requested(orig. currency){/ts}</td>
    <td>{ts}Amount Granted{/ts}</td>
    <td>{ts}Application Received{/ts}</td>
    <td>{ts}Money Transferred{/ts}</td>
  </tr>
{foreach from=$rows item=row}
    <tr class="{cycle values="odd-row,even-row"}">
        <td>{$row.sort_name}</td>
        <td>{$row.grant_status}</td>
        <td>{$row.grant_type}</td>
        <td>{$row.grant_amount_total|crmMoney}</td>
        <td>{$row.grant_amount_requested|crmMoney}</td>
        <td>{$row.grant_amount_granted|crmMoney}</td>
        <td>{$row.grant_application_received_date|truncate:10:''|crmDate}</td>
        <td>{$row.grant_money_transfer_date|truncate:10:''|crmDate}</td>
    </tr>
{/foreach}
</table>

<div class="crm-submit-buttons element-right">{$form.buttons.html}</div>

{else}
   <div class="messages status no-popup">
    <div class="icon inform-icon"></div>&nbsp;
        {ts}There are no records selected for Print.{/ts}
   </div>
{/if}
