{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
{math equation="x / y" x=$amount y=$installments format="%.2f" assign="currentInstallment"}
{* Check if current Total Pledge Amount is different from original pledge amount. *}
{if $currentInstallment NEQ $original_installment_amount}
    {assign var=originalPledgeAmount value=`$installments*$original_installment_amount`}
{/if}

<div class="crm-block crm-content-block crm-pledge-view-block">
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
<table class="crm-info-panel">
     <tr class="crm-pledge-form-block-displayName"><td class="label">{ts}Pledge By{/ts}</td><td class="bold"><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=$contactId"}">{$displayName}</a></td></tr>
     <tr class="crm-pledge-form-block-amount">
        <td class="label">{ts}Total Pledge Amount{/ts}</td>
        <td><span class="bold">{$amount|crmMoney:$currency}</span>
            {if $originalPledgeAmount}<div class="messages status no-popup"><div class="icon inform-icon"></div>{ts 1=$originalPledgeAmount|crmMoney:$currency} Pledge total has changed due to payment adjustments. Original pledge amount was %1.{/ts}</div>{/if}
        </td>
     </tr>
     <tr class="crm-pledge-form-block-installments"><td class="label">{ts}To be paid in{/ts}</td><td>{$installments} {ts}installments of{/ts} {$original_installment_amount|crmMoney:$currency} {ts}every{/ts} {$frequency_interval} {$frequencyUnit}</td></tr>
    <tr><td class="label">{ts}Payments are due on the{/ts}</td><td>{$frequency_day} day of the period</td></tr>

    {if $start_date}
       <tr class="crm-pledge-form-block-create_date"><td class="label">{ts}Pledge Made{/ts}</td><td>{$create_date|truncate:10:''|crmDate}</td></tr>
         <tr class="crm-pledge-form-block-start_date"><td class="label">{ts}Payment Start{/ts}</td><td>{$start_date|truncate:10:''|crmDate}</td></tr>
  {/if}
    {if $end_date}
         <tr class="crm-pledge-form-block-end_date"><td class="label">{ts}End Date{/ts}</td><td>{$end_date|truncate:10:''|crmDate}</td></tr>
  {/if}
    {if $cancel_date}
         <tr class="crm-pledge-form-block-cancel_date"><td class="label">{ts}Cancelled Date{/ts}</td><td>{$cancel_date|truncate:10:''|crmDate}</td></tr>
    {/if}
        <tr class="crm-pledge-form-block-contribution_type crm-pledge-form-block-financial_type"><td class="label">{ts}Financial Type{/ts}</td><td>{$financial_type}&nbsp;
    {if $is_test}
        {ts}(test){/ts}
    {/if}
        </td></tr>

    {if $campaign}
  <tr class="crm-pledge-form-block-campaign">
      <td class="label">{ts}Campaign{/ts}</td>
          <td>{$campaign}</td>
  </tr>
    {/if}

    {if $acknowledge_date}
            <tr class="crm-pledge-form-block-acknowledge_date"><td class="label">{ts}Received{/ts}</td><td>{$acknowledge_date|truncate:10:''|crmDate}&nbsp;</td></tr>
  {/if}
    {if $contribution_page}
            <tr class="crm-pledge-form-block-contribution_page"><td class="label">{ts}Self-service Payments Page{/ts}</td><td>{$contribution_page}</td></tr>
    {/if}
        <tr class="crm-pledge-form-block-pledge_status"><td class="label">{ts}Pledge Status{/ts}</td><td{if $status_id eq 3} class="font-red bold"{/if}>{$pledge_status} </td></tr>
    {if $honor_contact_id}
            <tr class="crm-pledge-form-block-honor_type"><td class="label">{$honor_type}</td><td>{$honor_display}</td></tr>
    {/if}
        <tr class="crm-pledge-form-block-initial_reminder_day"><td class="label">{ts}Initial Reminder Day{/ts}</td><td>{$initial_reminder_day}&nbsp;days prior to schedule date </td></tr>
        <tr class="crm-pledge-form-block-max_reminders"><td class="label">{ts}Maximum Reminders Send{/ts}</td><td>{$max_reminders}&nbsp;</td></tr>
        <tr class="crm-pledge-form-block-additional_reminder_day"><td class="label">{ts}Send additional reminders{/ts}</td><td>{$additional_reminder_day}&nbsp;days after the last one sent</td></tr>

    {include file="CRM/Custom/Page/CustomDataView.tpl"}
</table>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>

