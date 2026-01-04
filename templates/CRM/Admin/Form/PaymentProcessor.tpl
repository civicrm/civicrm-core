{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* This template is used for adding/configuring Payment Processors used by a particular site/domain.  *}
<h3>{if $action eq 1}{ts}New Payment Processor{/ts}{elseif $action eq 2}{ts}Edit Payment Processor{/ts}{else}{ts}Delete Payment Processor{/ts}{/if}</h3>
<div class="crm-block crm-form-block crm-paymentProcessor-form-block">

{if $action eq 8}
  <div class="messages status no-popup">
    {icon icon="fa-info-circle"}{/icon}
    {$deleteMessage|escape}
  </div>
{else}
  <table class="form-layout-compressed">
    {* This works for the fields managed from the EntityFields trait - see RelationshipType.tpl for end goal in this tpl *}
    {foreach from=$entityFields item=fieldSpec}
      {assign var=fieldName value=$fieldSpec.name}
      <tr class="crm-{$entityInClassFormat}-form-block-{$fieldName}">
        {include file="CRM/Core/Form/Field.tpl"}
      </tr>
    {/foreach}

    <tr class="crm-paymentProcessor-form-block-financial_account">
      <td class="label">{$form.financial_account_id.label}</td>
      <td>
  {if $financialAccount}
    {$form.financial_account_id.html}
      {else}
      {capture assign=ftUrl}{crmURL p='civicrm/admin/financial/financialAccount' q="reset=1"}{/capture}
    {ts 1=$ftUrl}There are no financial accounts configured with Financial Account Type 'Asset' Type. <a href='%1'>Click here</a> if you want to configure financial accounts for your site.{/ts}
        {/if}
      </td>
    </tr>
    <tr class="crm-paymentProcessor-form-block-payment-instrument-id">
      <td class="label">{$form.payment_instrument_id.label}</td><td>{$form.payment_instrument_id.html}</td>
    </tr>
    <tr class="crm-paymentProcessor-form-block-is_active">
        <td></td><td>{$form.is_active.html}&nbsp;{$form.is_active.label}</td>
    </tr>
    <tr class="crm-paymentProcessor-form-block-is_default">
        <td></td><td>{$form.is_default.html}&nbsp;{$form.is_default.label}</td>
    </tr>
    {if !empty($form.accept_credit_cards)}
    <tr class="crm-paymentProcessor-form-block-accept_credit_cards">
        <td class="label">{$form.accept_credit_cards.label}</td><td>{$form.accept_credit_cards.html}<br />
        <span class="description">{ts}Select Credit Card Types that this payment processor can accept{/ts}</span></td>
    </tr>
    {/if}
  </table>
<fieldset>
<legend>{ts}Processor Details for Live Payments{/ts}</legend>
  {include file="CRM/Admin/Form/PaymentProcessor/Details.tpl" fieldNames=$liveFieldNames}
</fieldset>

<fieldset>
<legend>{ts}Processor Details for Test Payments{/ts}</legend>
  {include file="CRM/Admin/Form/PaymentProcessor/Details.tpl" fieldNames=$testFieldNames}
</fieldset>
{/if}

  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>

{if $action eq 1  or $action eq 2}
  <script type="text/javascript">
  {literal}
    CRM.$(function($) {
      const $form = $('form.{/literal}{$form.formClass}{literal}');
      $('#payment_processor_type_id', $form).change(function() {
        var url = {/literal}"{$refreshURL}"{literal} + "&pp=" + $(this).val();
        $form.attr('data-warn-changes', 'false')
          // Ajax refresh (works in a popup or full-screen)
          .closest('.crm-ajax-container, #crm-main-content-wrapper')
          .crmSnippet({url: url}).crmSnippet('refresh');
      });
      const $elements = $('input[name=frontend_title], input[name=title]', $form);
      if ($elements.length === 2) {
        CRM.utils.syncFields($elements.first(), $elements.last());
      }
    });
  {/literal}
  </script>

{/if}
