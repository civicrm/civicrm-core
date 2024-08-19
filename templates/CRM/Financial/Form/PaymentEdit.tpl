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

  showHideFieldsByPaymentInstrumentID();
  $('#payment_instrument_id').on('change', showHideFieldsByPaymentInstrumentID);

  function showHideFieldsByPaymentInstrumentID() {
    var paymentInstrumentLabel = $('#payment_instrument_id option:selected').text();
    if (paymentInstrumentLabel == ts('Credit Card')) {
      $('.check_number-section').hide();
      $('.card_type_id-section, .pan_truncation-section').show();
    }
    else if (paymentInstrumentLabel == ts('Check')) {
      $('.card_type_id-section, .pan_truncation-section').hide();
      $('.check_number-section').show();
    }
    else {
      $('.card_type_id-section, .pan_truncation-section, .check_number-section').hide();
    }
  }
});
</script>
{/literal}
