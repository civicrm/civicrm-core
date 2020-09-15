{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{literal}
<script type="text/javascript">
  /**
   * Show or hide payment options.
   *
   * @param bool $isHide
   *   Should the block be hidden.
   */
  function showHidePayment(isHide) {
    var payment_options = cj(".payment_options-group");
    var payment_processor = cj("div.payment_processor-section");
    var payment_information = cj("div#payment_information");
    // I've added a hide for billing block. But, actually the issue
    // might be that the unselecting of the processor should cause it
    // to be hidden (or removed) in which case it can go from this function.
    var billing_block = cj("div#billing-payment-block");
    if (isHide) {
      payment_options.hide();
      payment_processor.hide();
      payment_information.hide();
      billing_block.hide();
      // also unset selected payment methods
      cj('input[name="payment_processor_id"]').removeProp('checked');
    }
    else {
      payment_options.show();
      payment_processor.show();
      payment_information.show();
      billing_block.show();
      // also set selected payment methods
      cj('input[name="payment_processor_id"][checked=checked]').prop('checked', true);
    }
  }

  /**
   * Hides or shows billing and payment options block depending on whether payment is required.
   *
   * In general incomplete orders or $0 orders do not require a payment block.
   */
  function skipPaymentMethod() {
    var isHide = false;
    var isMultiple = {/literal}{$event.is_multiple_registrations|@json_encode}{literal};
    var alwaysShowFlag = (isMultiple && cj("#additional_participants").val());
    var alwaysHideFlag = (cj("#bypass_payment").val() == 1);
    var total_amount_tmp =  cj('#pricevalue').data('raw-total');
    // Hide billing questions if this is free
    if (!alwaysShowFlag && total_amount_tmp == 0){
      isHide = true;
    }
    else {
      isHide = false;
    }
    if (alwaysHideFlag) {
      isHide = true;
    }
    showHidePayment(isHide);
  }
  skipPaymentMethod();

  CRM.$(function($) {
    function buildPaymentBlock(type) {
      var $form = $('#billing-payment-block').closest('form');
      {/literal}
      {if $contributionPageID}
        {capture assign='contributionPageID'}id={$contributionPageID}&{/capture}
      {else}
        {capture assign='pageID'}{/capture}
      {/if}
      {if $custom_pre_id}
        {capture assign='preProfileID'}pre_profile_id={$custom_pre_id}&{/capture}
      {else}
        {capture assign='preProfileID'}{/capture}
      {/if}
      {if $urlPathVar}
        {capture assign='urlPathVar'}{$urlPathVar}&{/capture}
      {else}
        {capture assign='urlPathVar'}{/capture}
      {/if}
      {if $billing_profile_id}
        {capture assign='profilePathVar'}billing_profile_id={$billing_profile_id}&{/capture}
      {else}
        {capture assign='profilePathVar'}{/capture}
      {/if}

      {capture assign='isBackOfficePathVar'}&is_back_office={$isBackOffice}&{/capture}

      var payment_instrument_id = $('#payment_instrument_id').val();

      var dataUrl = "{crmURL p='civicrm/payment/form' h=0 q="formName=`$form.formName`&currency=`$currency`&`$urlPathVar``$isBackOfficePathVar``$profilePathVar``$contributionPageID``$preProfileID`processor_id="}" + type;
      {literal}
      if (typeof(CRM.vars) != "undefined") {
        if (typeof(CRM.vars.coreForm) != "undefined") {
          if (typeof(CRM.vars.coreForm.contact_id) != "undefined") {
            dataUrl = dataUrl + "&cid=" + CRM.vars.coreForm.contact_id;
          }

          if (typeof(CRM.vars.coreForm.checksum) != "undefined" ) {
            dataUrl = dataUrl + "&cs=" + CRM.vars.coreForm.checksum;
          }
        }
      }
      dataUrl =  dataUrl + "&payment_instrument_id=" + payment_instrument_id;

      // Processors like pp-express will hide the form submit buttons, so re-show them when switching
      $('.crm-submit-buttons', $form).show().find('input').prop('disabled', true);
      CRM.loadPage(dataUrl, {target: '#billing-payment-block'});
    }

    $('[name=payment_processor_id]').on('change.paymentBlock', function() {
        buildPaymentBlock($(this).val());
    });

    $('#payment_instrument_id').on('change.paymentBlock', function() {
      buildPaymentBlock(0);
    });

    $('#billing-payment-block').on('crmLoad', function() {
      $('.crm-submit-buttons input').prop('disabled', false);
    })
  });

</script>
{/literal}
