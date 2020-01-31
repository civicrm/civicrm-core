{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

<div class="crm-block crm-form-block crm-auto-renew-membership-cancellation">
<div class="help">
  <div class="icon inform-icon"></div>&nbsp;
  {if $mode eq 'auto_renew'}
      {ts}Click the button below if you want to cancel the auto-renewal option for your {$membershipType} membership. This will not cancel your membership. However you will need to arrange payment for renewal when your membership expires.{/ts}
  {else}
      <strong>{ts 1=$amount|crmMoney 2=$frequency_interval 3=$frequency_unit}Recurring Contribution Details: %1 every %2 %3{/ts}
      {if $installments}
        {ts 1=$installments}for %1 installments{/ts}.
      {/if}</strong>
      <div class="content">{ts}Click the button below to cancel this commitment and stop future transactions. This does not affect contributions which have already been completed.{/ts}</div>
  {/if}
  {if !$cancelSupported}
    <div class="status-warning">
      {ts}Automatic cancellation is not supported for this payment processor. You or the contributor will need to manually cancel this recurring contribution using the payment processor website.{/ts}
    </div>
  {/if}
</div>
  {include file="CRM/Core/Form/EntityForm.tpl"}
</div>
