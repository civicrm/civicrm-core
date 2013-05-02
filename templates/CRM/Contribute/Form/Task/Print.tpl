{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
<div class="form-item crm-block crm-form-block crm-contribution-form-block">
     <span class="element-right">{$form.buttons.html}</span>
</div>
<div class="spacer"></div>
<br />
<p>
<table>
  <tr class="columnheader">
    <th>{ts}Name{/ts}</th>
    <th>{ts}Amount{/ts}</th>
    <th>{ts}Type{/ts}</th>
    <th>{ts}Source{/ts}</th>
    <th>{ts}Received{/ts}</th>
    <th>{ts}Thank-you Sent{/ts}</th>
    <th>{ts}Status{/ts}</th>
    <th>{ts}Premium{/ts}</th>
  </tr>
{foreach from=$rows item=row}
    <tr class="{cycle values="odd-row,even-row"} crm-contribution">
        <td class="crm-contribution-sort_name">{$row.sort_name}</td>
        <td class="right bold crm-contribution-total_amount" nowrap>{$row.total_amount|crmMoney}</td>
        <td class="crm-contribution-type crm-contribution-{$row.financial_type} crm-financial-type crm-contribution-{$row.financial_type}">{$row.financial_type}</td>  
        <td class="crm-contribution-contribution_source">{$row.contribution_source}</td> 
        <td class="crm-contribution-receive_date">{$row.receive_date|truncate:10:''|crmDate}</td>
        <td class="crm-contribution-thankyou_date">{$row.thankyou_date|truncate:10:''|crmDate}</td>
        <td class="crm-contribution-status crm-contribution-status_{$row.contribution_status_id}"> 
            {$row.contribution_status_id}<br />
            {if $row.cancel_date}    
                {$row.cancel_date|truncate:10:''|crmDate}
            {/if}
        </td>
        <td class="crm-contribution-product_name">{$row.product_name}</td>
    </tr>
{/foreach}
</table>

<div class="form-item">
     <span class="element-right">{$form.buttons.html}</span>
</div>

{else}
   <div class="messages status no-popup">
     <div class="icon inform-icon"/>
        {ts}There are no records selected for Print.{/ts}
   </div>
{/if}
