{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="help">
  {icon icon="fa-info-circle"}{/icon}
  {if $mode eq 'auto_renew'}
      {ts}Use this form to update the credit card and billing name and address used with the auto-renewal option for your {$membershipType} membership.{/ts}
  {else}
    <strong>{ts 1=$amount|crmMoney 2=$recur_frequency_interval 3=$recur_frequency_unit}Recurring Contribution Details: %1 every %2 %3{/ts}
    {if $installments}
      {ts 1=$installments}for %1 installments{/ts}.
    {/if}</strong>
    <div class="content">{ts}Use this form to update the credit card and billing name and address used for this recurring contribution.{/ts}</div>
  {/if}
</div>

{include file="CRM/Core/BillingBlockWrapper.tpl"}

<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
