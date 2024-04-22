{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

<div class="crm-block crm-content-block crm-pledge-view-block">
  <table class="crm-info-panel">
    <tr class="crm-pledge-form-block-displayName"><td class="label">{ts}Pledge By{/ts}</td><td class="bold"><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=$contactId"}">{$displayName|escape}</a></td></tr>
    <tr class="crm-pledge-form-block-amount">
      <td class="label">{ts}Total Pledge Amount{/ts}</td>
      <td><span class="bold">{$amount|crmMoney:$currency}</span>
        {if $originalPledgeAmount}<div class="messages status no-popup">{icon icon="fa-info-circle"}{/icon}{ts 1=$originalPledgeAmount|crmMoney:$currency} Pledge total has changed due to payment adjustments. Original pledge amount was %1.{/ts}</div>{/if}
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
    <tr class="crm-pledge-form-block-contribution_type crm-pledge-form-block-financial_type">
      <td class="label">{ts}Financial Type{/ts}</td>
      <td>{$financial_type|escape}&nbsp;{if $is_test}{ts}(test){/ts}{/if}</td>
    </tr>
    {if $campaign}
      <tr class="crm-pledge-form-block-campaign">
        <td class="label">{ts}Campaign{/ts}</td>
        <td>{$campaign|escape}</td>
      </tr>
    {/if}
    {if $acknowledge_date}
      <tr class="crm-pledge-form-block-acknowledge_date"><td class="label">{ts}Received{/ts}</td><td>{$acknowledge_date|truncate:10:''|crmDate}&nbsp;</td></tr>
    {/if}
    {if $contribution_page}
      <tr class="crm-pledge-form-block-contribution_page"><td class="label">{ts}Self-service Payments Page{/ts}</td><td>{$contribution_page|escape}</td></tr>
    {/if}
    <tr class="crm-pledge-form-block-pledge_status"><td class="label">{ts}Pledge Status{/ts}</td><td{if $pledge_status_name eq 'Cancelled'} class="font-red bold"{/if}>{$pledge_status|escape} </td></tr>
    <tr class="crm-pledge-form-block-initial_reminder_day"><td class="label">{ts}Initial Reminder Day{/ts}</td><td>{$initial_reminder_day}&nbsp;days prior to schedule date </td></tr>
    <tr class="crm-pledge-form-block-max_reminders"><td class="label">{ts}Maximum Reminders Send{/ts}</td><td>{$max_reminders}&nbsp;</td></tr>
    <tr class="crm-pledge-form-block-additional_reminder_day"><td class="label">{ts}Send additional reminders{/ts}</td><td>{$additional_reminder_day}&nbsp;days after the last one sent</td></tr>
    {include file="CRM/Custom/Page/CustomDataView.tpl"}
  </table>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
