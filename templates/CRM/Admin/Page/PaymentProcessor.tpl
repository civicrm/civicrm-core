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
    {ts}You can configure one or more Payment Processors for your CiviCRM installation. You must then assign an active Payment Processor to each <strong>Online Contribution Page</strong> and each paid <strong>Event</strong>.{/ts} {help id='proc-type'}
</div>

{if $action eq 1 or $action eq 2 or $action eq 8}
   {include file="CRM/Admin/Form/PaymentProcessor.tpl"}
{else}

<div class="crm-content-block crm-block">
{if $rows}
<div id="ltype">
        {strip}
        {* handle enable/disable actions*}
   {include file="CRM/common/enableDisableApi.tpl"}
        <table class="selector row-highlight">
        <tr class="columnheader">
            <th>{ts}ID{/ts}</th>
            <th>{ts}Test ID{/ts}</th>
            <th>{ts}Name{/ts}</th>
            <th>{ts}Processor Type{/ts}</th>
            <th>{ts}Description{/ts}</th>
            <th>{ts}Financial Account{/ts}</th>
            <th>{ts}Enabled?{/ts}</th>
            <th>{ts}Default?{/ts}</th>
            <th></th>
        </tr>
        {foreach from=$rows item=row}
        <tr id="payment_processor-{$row.id}" class="crm-entity {cycle values="odd-row,even-row"} {if NOT $row.is_active} disabled{/if}">
            <td class="crmf-id center">{$row.id}</td>
            <td class="crmf-test_id center">{$row.test_id}</td>
            <td class="crmf-name">{$row.name}</td>
            <td class="crmf-payment_processor_type">{$row.payment_processor_type}</td>
            <td class="crmf-description">{$row.description}</td>
            <td class="crmf-financial_account_id">{$row.financialAccount}</td>
            <td class="crmf-is_active center">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
            <td class="crmf-is_default center">{icon condition=$row.is_default}{ts}Default{/ts}{/icon}&nbsp;
            </td>
            <td>{$row.action|smarty:nodefaults|replace:'xx':$row.id}</td>
        </tr>
        {/foreach}
        </table>
        {/strip}

        {if $action ne 1 and $action ne 2}
        <div class="action-link">
          {crmButton p='civicrm/admin/paymentProcessor/edit' q="action=add&reset=1" id="newPaymentProcessor"  icon="plus-circle"}{ts}Add Payment Processor{/ts}{/crmButton}
        </div>
        {/if}
</div>
{elseif $action ne 1}
    <div class="messages status no-popup">
      {icon icon="fa-info-circle"}{/icon}
        {ts}There are no Payment Processors entered.{/ts}
     </div>
     <div class="action-link">
       {crmButton p='civicrm/admin/paymentProcessor/edit' q="action=add&reset=1" id="newPaymentProcessor"  icon="plus-circle"}{ts}Add Payment Processor{/ts}{/crmButton}
     </div>
{/if}
</div>

{/if}
