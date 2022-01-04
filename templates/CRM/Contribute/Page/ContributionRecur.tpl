{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

{if $recur}
  {if $recur.is_test}
    <div class="help">
      <strong>{ts}This is a TEST transaction{/ts}</strong>
    </div>
  {/if}
  <div class="crm-block crm-content-block crm-recurcontrib-view-block">
    <table class="crm-info-panel">
      <tr>
        <td class="label">{ts}From{/ts}</td>
        <td class="bold"><a href="{crmURL p='civicrm/contact/view' q="cid=`$recur.contact_id`"}">{$displayName}</a></td>
      </tr>
      {if $displayLineItems}
        <tr><td class="label">{ts}Amount{/ts}</td><td>{include file="CRM/Price/Page/LineItem.tpl" context="ContributionRecur" totalAmount=$recur.amount currency=$recur.currency}</td></tr>
      {else}
        <tr><td class="label">{ts}Amount{/ts}</td><td>{$recur.amount|crmMoney:$recur.currency}{if $is_test} ({ts}test{/ts}){/if}</td></tr>
      {/if}
      <tr><td class="label">{ts}Frequency{/ts}</td><td>{ts 1=$recur.frequency_interval 2=$recur.frequency_unit}every %1 %2{/ts}</td></tr>
      {if !empty($recur.installments)}<tr><td class="label">{ts}Installments{/ts}</td><td>{$recur.installments}</td></tr>{/if}
      <tr><td class="label">{ts}Status{/ts}</td><td>{$recur.contribution_status}</td></tr>
      <tr><td class="label">{ts}Start Date{/ts}</td><td>{$recur.start_date|crmDate}</td></tr>
      <tr><td class="label">{ts}Created Date{/ts}</td><td>{$recur.create_date|crmDate}</td></tr>
      {if $recur.modified_date}<tr><td class="label">{ts}Modified Date{/ts}</td><td>{$recur.modified_date|crmDate}</td></tr>{/if}
      {if !empty($recur.cancel_date)}<tr><td class="label">{ts}Cancelled Date{/ts}</td><td>{$recur.cancel_date|crmDate}</td></tr>{/if}
      {if !empty($recur.cancel_reason)}<tr><td class="label">{ts}Cancel Reason{/ts}</td><td>{$recur.cancel_reason}</td></tr>{/if}
      {if !empty($recur.end_date)}<tr><td class="label">{ts}End Date{/ts}</td><td>{$recur.end_date|crmDate}</td></tr>{/if}
      {if $recur.processor_id}<tr><td class="label">{ts}Processor ID{/ts}</td><td>{$recur.processor_id}</td></tr>{/if}
      {if !empty($recur.trxn_id) && ($recur.processor_id neq $recur.trxn_id)}<tr><td class="label">{ts}Transaction ID{/ts}</td><td>{$recur.trxn_id}</td></tr>{/if}
      {if $recur.invoice_id}<tr><td class="label">{ts}Invoice ID{/ts}</td><td>{$recur.invoice_id}</td></tr>{/if}
      <tr><td class="label">{ts}Cycle Day{/ts}</td><td>{$recur.cycle_day}</td></tr>
      {if !empty($recur.next_sched_contribution_date) && $recur.contribution_status_id neq 3}<tr><td class="label">{ts}Next Contribution{/ts}</td><td>{$recur.next_sched_contribution_date|crmDate}</td></tr>{/if}
      <tr><td class="label">{ts}Failure Count{/ts}</td><td>{$recur.failure_count}</td></tr>
      {if !empty($recur.next_sched_contribution_date) && $recur.invoice_id}<tr><td class="label">{ts}Failure Retry Date{/ts}</td><td>{$recur.next_sched_contribution_date|crmDate}</td></tr>{/if}
      <tr><td class="label">{ts}Auto Renew?{/ts}</td><td>{if $recur.auto_renew}{ts}Yes{/ts}{else}{ts}No{/ts}{/if}</td></tr>
      <tr><td class="label">{ts}Send receipt for each contribution?{/ts}</td><td>{if $recur.is_email_receipt}{ts}Yes{/ts}{else}{ts}No{/ts}{/if}</td></tr>
      {if $recur.payment_processor}<tr><td class="label">{ts}Payment Processor{/ts}</td><td>{$recur.payment_processor}</td></tr>{/if}
      {if $recur.financial_type}<tr><td class="label">{ts}Financial Type{/ts}</td><td>{$recur.financial_type}</td></tr>{/if}
      {if !empty($recur.campaign)}<tr><td class="label">{ts}Campaign{/ts}</td><td>{$recur.campaign}</td></tr>{/if}
      {if !empty($recur.membership_id)}<tr>
        <td class="label">{ts}Membership{/ts}</td>
        <td><a class="crm-hover-button action-item" href='{crmURL p="civicrm/contact/view/membership" q="action=view&reset=1&cid=`$contactId`&id=`$recur.membership_id`&context=membership&selectedChild=member"}'>{$recur.membership_name}</a></td>
        </tr>
      {/if}
      {include file="CRM/Custom/Page/CustomDataView.tpl"}

    </table>
    <div class="crm-submit-buttons"><a class="button cancel crm-form-submit" href="{crmURL p='civicrm/contact/view' q='action=browse&selectedChild=contribute'}">{ts}Done{/ts}</a></div>
  </div>
{/if}
{if $hasAccessCiviContributePermission}
  <script type="text/javascript">
    var recurContribID = {$recur.id};
    var contactID = {$contactId};
    {literal}
    CRM.$(function($) {
      CRM.loadPage(
              CRM.url(
                      'civicrm/contribute/contributionrecur-payments',
                      {
                        reset: 1,
                        id: recurContribID,
                        cid: contactID
                      },
                      'back'
              ),
              {
                target : '#recurring-contribution-payments',
                dialog : false
              }
      );
    });
    {/literal}
  </script>
{/if}
<div id="recurring-contribution-payments"></div>
