{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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

{include file="CRM/common/enableDisableApi.tpl"}

{if $action eq 4} {* when action is view *}
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
            <tr><td class="label">{ts}Amount{/ts}</td><td>{$recur.amount|crmMoney:$recur.currency}{if $is_test} ({ts}test{/ts}){/if}</td></tr>
            <tr><td class="label">{ts}Frequency{/ts}</td><td>every {$recur.frequency_interval} {$recur.frequency_unit}</td></tr>
            <tr><td class="label">{ts}Installments{/ts}</td><td>{$recur.installments}</td></tr>
            <tr><td class="label">{ts}Status{/ts}</td><td>{$recur.contribution_status}</td></tr>
            <tr><td class="label">{ts}Start Date{/ts}</td><td>{$recur.start_date|crmDate}</td></tr>
            <tr><td class="label">{ts}Created Date{/ts}</td><td>{$recur.create_date|crmDate}</td></tr>
            {if $recur.modified_date}<tr><td class="label">{ts}Modified Date{/ts}</td><td>{$recur.modified_date|crmDate}</td></tr>{/if}
            {if $recur.cancel_date}<tr><td class="label">{ts}Cancelled Date{/ts}</td><td>{$recur.cancel_date|crmDate}</td></tr>{/if}
            {if $recur.cancel_reason}<tr><td class="label">{ts}Cancel Reason{/ts}</td><td>{$recur.cancel_reason}</td></tr>{/if}
            {if $recur.end_date}<tr><td class="label">{ts}End Date{/ts}</td><td>{$recur.end_date|crmDate}</td></tr>{/if}
            {if $recur.processor_id}<tr><td class="label">{ts}Processor ID{/ts}</td><td>{$recur.processor_id}</td></tr>{/if}
            <tr><td class="label">{ts}Transaction ID{/ts}</td><td>{$recur.trxn_id}</td></tr>
            {if $recur.invoice_id}<tr><td class="label">{ts}Invoice ID{/ts}</td><td>{$recur.invoice_id}</td></tr>{/if}
            <tr><td class="label">{ts}Cycle Day{/ts}</td><td>{$recur.cycle_day}</td></tr>
            {if $recur.contribution_status_id neq 3}<tr><td class="label">{ts}Next Contribution{/ts}</td><td>{$recur.next_sched_contribution_date|crmDate}</td></tr>{/if}
            <tr><td class="label">{ts}Failure Count{/ts}</td><td>{$recur.failure_count}</td></tr>
            {if $recur.invoice_id}<tr><td class="label">{ts}Failure Retry Date{/ts}</td><td>{$recur.next_sched_contribution_date|crmDate}</td></tr>{/if}
            <tr><td class="label">{ts}Auto Renew?{/ts}</td><td>{if $recur.auto_renew}{ts}Yes{/ts}{else}{ts}No{/ts}{/if}</td></tr>
            {if $recur.payment_processor}<tr><td class="label">{ts}Payment Processor{/ts}</td><td>{$recur.payment_processor}</td></tr>{/if}
            {if $recur.financial_type}<tr><td class="label">{ts}Financial Type{/ts}</td><td>{$recur.financial_type}</td></tr>{/if}
            {if $recur.campaign}<tr><td class="label">{ts}Campaign{/ts}</td><td>{$recur.campaign}</td></tr>{/if}
            {if $recur.membership_id}<tr>
              <td class="label">{ts}Membership{/ts}</td>
              <td><a class="crm-hover-button action-item" href='{crmURL p="civicrm/contact/view/membership" q="action=view&reset=1&cid=`$contactId`&id=`$recur.membership_id`&context=membership&selectedChild=member"}'>{$recur.membership_name}</a></td>
              </tr>
            {/if}
            {include file="CRM/Custom/Page/CustomDataView.tpl"}

          </table>
          <div class="crm-submit-buttons"><a class="button cancel crm-form-submit" href="{crmURL p='civicrm/contact/view' q='action=browse&selectedChild=contribute'}">{ts}Done{/ts}</a></div>
        </div>
    {/if}

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
  <div id="recurring-contribution-payments"></div>
{/if}
{if $recurRows}
  {include file="CRM/Contribute/Page/ContributionRecurSelector.tpl"}
{/if}
