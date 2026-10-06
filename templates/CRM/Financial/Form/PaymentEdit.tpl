{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

{crmRegion name="payment-edit-block"}
   <div id="payment-edit-section" class="crm-section billing_mode-section">
     {foreach from=$paymentFields key=fieldName item=paymentField}
       {assign var='name' value=$fieldName}
       <div class="crm-container {$name}-section">
         <div class="label">{$form.$name.label}
         </div>
         <div class="content">{if $name eq 'total_amount'}{$currency}&nbsp;&nbsp;{/if}{$form.$name.html}
         </div>
         <div class="clear"></div>
       </div>
     {/foreach}
   </div>
{/crmRegion}
{include file="CRM/common/customDataBlock.tpl" customDataType='FinancialTrxn' customDataSubType=false entityID=$id groupID='' cid=false}
<div class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl" location="bottom"}
</div>

{literal}
<script type="text/javascript">
CRM.$(function ($) {
  // The fields each option value needs, keyed by option value (see
  // CRM_Contribute_BAO_Contribution::getPaymentInstrumentFields()). We must not
  // compare the option label: it is translated and can be customised per-site.
  var paymentInstrumentFields = {/literal}{$paymentInstrumentFields|@json_encode}{literal};

  showHideFieldsByPaymentInstrumentID();
  $('#payment_instrument_id').on('change', showHideFieldsByPaymentInstrumentID);

  function showHideFieldsByPaymentInstrumentID() {
    var fields = paymentInstrumentFields[$('#payment_instrument_id').val()] || [];
    $('.check_number-section').toggle(fields.includes('check_number'));
    $('.card_type_id-section, .pan_truncation-section').toggle(fields.includes('card_type_id'));
  }
});
</script>
{/literal}
