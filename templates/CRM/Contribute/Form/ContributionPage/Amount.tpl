{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{capture assign="adminPriceSets"}{crmURL p='civicrm/admin/price' q="reset=1"}{/capture}
{crmRegion name="contribute-form-contributionpage-amount-main"}
<div class="crm-block crm-form-block crm-contribution-contributionpage-amount-form-block">
<div class="help">
    {ts}Use this form to configure Contribution Amount options. You can give contributors the ability to enter their own contribution amounts and/or provide a fixed list of amounts. For fixed amounts, you can enter a label for each 'level' of contribution (e.g. Friend, Sustainer, etc.). If you allow people to enter their own dollar amounts, you can also set minimum and maximum values. Depending on your choice of Payment Processor, you may be able to offer a recurring contribution option.{/ts} {docURL page="user/contributions/payment-processors"}
</div>
    {if !$paymentProcessor}
        {capture assign=ppUrl}{crmURL p='civicrm/admin/paymentProcessor' q="reset=1"}{/capture}
        <div class="status message">
            {ts 1=$ppUrl}No Payment Processor has been configured / enabled for your site. Unless you are only using CiviContribute to solicit non-monetary / in-kind contributions, you will need to <a href='%1'>configure a Payment Processor</a>. Then return to this screen and assign the processor to this Contribution Page.{/ts} {docURL page="user/contributions/payment-processors"}
            <p>{ts}NOTE: Alternatively, you can enable the <strong>Pay Later</strong> option below without setting up a payment processor. All users will then be asked to submit payment offline (e.g. mail in a check, call in a credit card, etc.).{/ts}</p>
        </div>
    {/if}
    <table class="form-layout-compressed">
        <tr class="crm-contribution-contributionpage-amount-form-block-is_monetary">
            <td scope="row" class="label" width="20%">{$form.is_monetary.label} {help id="is_monetary"}</td>
            <td>{$form.is_monetary.html}</td>
        </tr>
        <tr class="crm-contribution-contributionpage-amount-form-block-currency"><td scope="row" class="label" width="20%">{$form.currency.label}</td>
          <td>{$form.currency.html}</td>
        </tr>
        {if $paymentProcessor}
          <tr class="crm-contribution-contributionpage-amount-form-block-payment_processor"><td scope="row" class="label" width="20%">{$form.payment_processor.label}</td>
            <td>{$form.payment_processor.html}</td>
          </tr>
        {/if}
        <tr class="crm-contribution-contributionpage-amount-form-block-is_pay_later"><td scope="row" class="label">{$form.is_pay_later.label}</td>
          <td>{$form.is_pay_later.html}
          <span class="description">{ts}Check this box if you want to give users the option to submit payment offline (e.g. mail in a check, call in a credit card, etc.).{/ts}</span></td>
        </tr>
        <tr id="payLaterFields" class="crm-contribution-form-block-payLaterFields"><td>&nbsp;</td>
            <td>
            <table class="form-layout">
                <tr class="crm-contribution-contributionpage-amount-form-block-pay_later_text">
                    <td scope="row" class="label">{$form.pay_later_text.label} <span class="crm-marker" title="{ts escape='htmlattribute'}This field is required.{/ts}">*</span> {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contribution_page' field='pay_later_text' id=$contributionPageID}{/if}</td>
                    <td>{$form.pay_later_text.html|crmAddClass:big}</td>
                </tr>
                <tr class="crm-contribution-contributionpage-amount-form-block-pay_later_receipt">
                    <td scope="row" class="label">
                        {$form.pay_later_receipt.label}
                        <span class="crm-marker" title="{ts escape='htmlattribute'}This field is required.{/ts}">*</span>
                        {help id="pay_later_receipt"}
                        {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contribution_page' field='pay_later_receipt' id=$contributionPageID}{/if}
                    </td>
                    <td>{$form.pay_later_receipt.html|crmAddClass:big}</td>
                </tr>
                <tr>
                    <td scope="row" class="label">{$form.is_billing_required.label} {help id="is_billing_required"}</td>
                    <td>{$form.is_billing_required.html}</td>
                </tr>
            </table>
            </td>
        </tr>
        <tr class="crm-contribution-contributionpage-amount-form-block-amount_block_is_active">
          <td scope="row" class="label">{$form.amount_block_is_active.label} {help id="amount_block_is_active"}</td>
          <td>{$form.amount_block_is_active.html}</td>
        </tr>
        <tr id="priceSet" class="crm-contribution-contributionpage-amount-form-block-priceSet">
            <td scope="row" class="label">{$form.price_set_id.label}</td>
            <td>{$form.price_set_id.html}
              <a href="#" class="crm-button crm-popup">{icon icon="fa-list-alt"}{/icon} {ts}Fields{/ts}</a>
              <div class="description">{ts}Select a Price Set to offer more complex amount options. Otherwise, leave this empty and enter fixed contribution options below.{/ts}</div>
              <div class="description">{ts}To create a new Price Set, go to <a href="{crmURL p="civicrm/admin/price" q="reset=1"}" target="_blank">Administer Price Sets</a>.{/ts}</p>
            </td>
        </tr>
    </table>

    <div id="recurringFields">
        <table class="form-layout-compressed">


  {if $recurringPaymentProcessor}
        <tr id="recurringContribution" class="crm-contribution-form-block-is_recur"><td scope="row" class="label" width="20%">{$form.is_recur.label} {help id="is_recur"}</td>
               <td>{$form.is_recur.html}</td>
        </tr>
        <tr id="recurFields" class="crm-contribution-form-block-recurFields"><td>&nbsp;</td>
               <td>
                  <table class="form-layout-compressed">
                    <tr class="crm-contribution-form-block-recur_frequency_unit">
                        <td scope="row" class="label">{$form.recur_frequency_unit.label} <span class="crm-marker" title="{ts escape='htmlattribute'}This field is required.{/ts}">*</span></td>
                        <td>{$form.recur_frequency_unit.html}</td>
                    </tr>
                    <tr class="crm-contribution-form-block-is_recur_interval"><td scope="row" class="label">{$form.is_recur_interval.label}</td>
                        <td>{$form.is_recur_interval.html}<br />
                        <span class="description">{ts}Can users also set an interval (e.g. every '3' months)?{/ts}</span></td>
                    </tr>
                    <tr class="crm-contribution-form-block-is_recur_installments"><td scope="row" class="label">{$form.is_recur_installments.label}</td>
                        <td>{$form.is_recur_installments.html}<br />
                        <span class="description">{ts}Give the user a choice of installments (e.g. donate every month for 6 months)? If not, recurring donations will continue indefinitely.{/ts}</span></td>
                    </tr>
                  </table>
                </td>
        </tr>
        {/if}

        </table>
    </div>
{if $futurePaymentProcessor}
    <span id="pledge_calendar_date_field">&nbsp;&nbsp;{$form.pledge_calendar_date.html}</span>
    <span id="pledge_calendar_month_field">&nbsp;&nbsp;{$form.pledge_calendar_month.html}</span>
{/if}


    <div id="amountFields">
        <table class="form-layout-compressed">
            {* handle CiviPledge fields *}
            {if $civiPledge}
            <tr class="crm-contribution-form-block-is_pledge_active"><td scope="row" class="label" width="20%">{$form.is_pledge_active.label} {help id="is_pledge_active"}</td>
                <td>{$form.is_pledge_active.html}</td>
            </tr>
            <tr id="pledgeFields" class="crm-contribution-form-block-pledgeFields"><td></td><td>
                <table class="form-layout-compressed">
                    <tr class="crm-contribution-form-block-pledge_frequency_unit"><td scope="row" class="label">{$form.pledge_frequency_unit.label}<span class="crm-marker"> *</span></td>
                        <td>{$form.pledge_frequency_unit.html}</td>
                    </tr>
                    <tr class="crm-contribution-form-block-is_pledge_interval"><td scope="row" class="label">{$form.is_pledge_interval.label}</td>
                        <td>{$form.is_pledge_interval.html}<br />
                            <span class="description">{ts}Can they also set an interval (e.g. every '3' months)?{/ts}</span></td>
                    </tr>
                    <tr class="crm-contribution-form-block-initial_reminder_day"><td scope="row" class="label">{$form.initial_reminder_day.label}</td>
                        <td>{$form.initial_reminder_day.html}
                            <span class="label">{ts}Days prior to each scheduled payment due date.{/ts}</span></td>
                    </tr>
                    <tr class="crm-contribution-form-block-max_reminders"><td scope="row" class="label">{$form.max_reminders.label}</td>
                        <td>{$form.max_reminders.html}
                            <span class="label">{ts}Reminders for each scheduled payment.{/ts}</span></td>
                    </tr>
                    <tr class="crm-contribution-form-block-additional_reminder_day"><td scope="row" class="label">{$form.additional_reminder_day.label}</td>
                        <td>{$form.additional_reminder_day.html}
                            <span class="label">{ts}Days after the last one sent, up to the maximum number of reminders.{/ts}</span></td>
                    </tr>
                {if $futurePaymentProcessor}
                    <tr id="adjustRecurringFields" class="crm-contribution-form-block-adjust_recur_start_date"><td scope="row" class="label">{$form.adjust_recur_start_date.label}</td>
                        <td>{$form.adjust_recur_start_date.html}<br/>
                          <div id="recurDefaults">
                            <span class="description">{$form.pledge_default_toggle.label}</span>
                            <table class="form-layout-compressed">
                              <tr class="crm-contribution-form-block-date_of_recurring_contribution">
                                <td>{$form.pledge_default_toggle.html}</td>
                              </tr>
                              <tr class="crm-contribution-form-block-is_pledge_start_date_visible">
                                <td>{$form.is_pledge_start_date_visible.html}&nbsp;{$form.is_pledge_start_date_visible.label}</td>
                              </tr>
                              <tr class="crm-contribution-form-block-is_pledge_start_date_visible">
                                <td>{$form.is_pledge_start_date_editable.html}&nbsp;{$form.is_pledge_start_date_editable.label}</td>
                              </tr>
                            </table>
                          </div>
                        </td>
                    </tr>
                {/if}
                </table>
                </td>
            </tr>
            {/if}

      <tr class="crm-contribution-form-block-amount_label">
              <td scope="row" class="label" width="20%">{$form.amount_label.label}<span class="crm-marker"> *</span></td>
        <td>{$form.amount_label.html}</td>
      </tr>
            <tr class="crm-contribution-form-block-is_allow_other_amount">
                <td scope="row" class="label" width="20%">{$form.is_allow_other_amount.label} {help id="is_allow_other_amount"}</td>
                <td>{$form.is_allow_other_amount.html}</td>
            </tr>

            <tr id="minMaxFields" class="crm-contribution-form-block-minMaxFields"><td>&nbsp;</td><td>
               <table class="form-layout-compressed">
                <tr class="crm-contribution-form-block-min_amount"><td scope="row" class="label">{$form.min_amount.label}</td>
                <td>{$form.min_amount.html}</td></tr>
                <tr class="crm-contribution-form-block-max_amount"><td scope="row" class="label">{$form.max_amount.label}</td>
                <td>{$form.max_amount.html}</td></tr>
               </table>
            </td></tr>

            <tr><td colspan="2">
                <fieldset><legend>{ts}Fixed Contribution Options{/ts}</legend>
                  <div class="description">
                    {ts}Use the table below to enter up to ten fixed contribution amounts. These will be presented as a list of radio button options. Both the label and dollar amount will be displayed.{/ts}{if $isQuick}{ts} Click <a id='quickconfig' href='#'>here</a> if you want to configure the Fixed Contribution Options below as part of a Price Set, with the added flexibility and complexity that entails.{/ts}{/if}
                  </div>
                    <br />
                    <table id="map-field-table">
                        <tr class="columnheader" ><td scope="column">{ts}Contribution Label{/ts}</td><td scope="column">{ts}Amount{/ts}</td><td scope="column">{ts}Default?{/ts}<br />{$form.default.0.html}</td></tr>
                        {section name=loop start=1 loop=11}
                            {assign var=idx value=$smarty.section.loop.index}
                            <tr><td class="even-row">{$form.label.$idx.html}</td><td>{$form.value.$idx.html}</td><td class="even-row">{$form.default.$idx.html}</td></tr>
                        {/section}
                    </table>
              </fieldset>
            </td></tr>
        </table>
      </div>
      <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
{literal}
<script type="text/javascript">

   {/literal}{if $futurePaymentProcessor}{literal}
   CRM.$(function($) {
     var defId = $('input[name="pledge_default_toggle"][value="contribution_date"]').attr('id');
     var calId = $('input[name="pledge_default_toggle"][value="calendar_date"]').attr('id');
     var monId = $('input[name="pledge_default_toggle"][value="calendar_month"]').attr('id');

     $("label[for='" + calId + "']").append($('#pledge_calendar_date_field'));
     $("label[for='" + monId + "']").append($('#pledge_calendar_month_field'));

     setDateDefaults();

     $("#" + defId).click( function() {
       if ($(this).is(':checked')) {
         $('#pledge_calendar_month').prop('disabled', 'disabled');
         $('#pledge_calendar_date').prop('disabled', 'disabled');
         $("#pledge_calendar_date").next('input').prop('disabled', 'disabled');
       }
     });

     $("#" + calId).click( function() {
       if ($(this).is(':checked')) {
         $('#pledge_calendar_month').prop('disabled', 'disabled');
         $('#pledge_calendar_date').prop('disabled', false);
         $("#pledge_calendar_date").next('input').prop('disabled', false);
       }
     });

     $("#" + monId).click( function() {
       if ($(this).is(':checked')) {
         $('#pledge_calendar_month').prop('disabled', false);
         $("#pledge_calendar_date").next('input').prop('disabled', 'disabled');
         $('#pledge_calendar_date').prop('disabled', 'disabled');
       }
     });


   });
{/literal}{/if}{literal}

   var paymentProcessorMapper = [];
     {/literal}
       {if $recurringPaymentProcessor}
           {foreach from=$recurringPaymentProcessor item="paymentProcessor" key="index"}{literal}
               paymentProcessorMapper[{/literal}{$index}{literal}] = '{/literal}{$paymentProcessor}{literal}';
           {/literal}{/foreach}
       {/if}
     {literal}
     CRM.$(function($) {
       var psid = $('#price_set_id').val();
       showHideAmountBlock(psid, 'price_set_id');
       function checked_payment_processors() {
         var ids = [];
         $('.crm-contribution-contributionpage-amount-form-block-payment_processor input[type="checkbox"]').each(function(){
           if($(this).prop('checked')) {
             var id = $(this).attr('id').split('_')[2];
             ids.push(id);
           }
         });
         return ids;
       }

        // show/hide recurring block
        $('.crm-contribution-contributionpage-amount-form-block-payment_processor input[type="checkbox"]').change(function(){
            showRecurring( checked_payment_processors() );
            showAdjustRecurring( checked_payment_processors() );
        });
        showRecurring( checked_payment_processors() );
        showAdjustRecurring( checked_payment_processors() );
    });
  var element_other_amount = document.getElementsByName('is_allow_other_amount');
    if (! element_other_amount[0].checked) {
     cj('#minMaxFields').hide();
  }
  var amount_block = document.getElementsByName('amount_block_is_active');
  var priceSetID = {/literal}'{$priceSetID}'{literal};

  if ( ! amount_block[0].checked || priceSetID ) {
     if ( !priceSetID ) {
       cj('#priceSet').hide();
       if (CRM.memberPriceset) {
         cj(".crm-contribution-contributionpage-amount-form-block-amount_block_is_active td").html('<span class="description">{/literal}{ts escape="js"}You cannot enable the Contribution Amounts section when a Membership Price Set is in use. (See the Memberships tab above.) Membership Price Sets may include additional fields for non-membership options that require an additional fee (e.g. magazine subscription) or an additional voluntary contribution.{/ts}{literal}</span>');
       }
     }
     cj('#amountFields').hide();
  }

  CRM.$(function($) {
    payLater('is_pay_later');
  });

  cj('#is_pay_later').click( function() {
     payLater('is_pay_later');
  });


  function minMax(chkbox) {
    if (chkbox.checked) {
       cj('#minMaxFields').show();
    } else {
     cj('#minMaxFields').hide();
     document.getElementById("min_amount").value = '';
     document.getElementById("max_amount").value = '';
    }
  }

  function payLater(chkbox) {
    var elementId = 'payLaterFields';
    if (cj('#' + chkbox).prop('checked')) {
      cj('#' + elementId).show();
    } else {
      cj('#' + elementId).hide();
    }
  }

  function showHideAmountBlock(element, elementName) {
    // show / hide when amount section is active check/uncheck.
    var priceSetID = {/literal}'{$priceSetID}'{literal};
    switch (elementName) {
      case 'price_set_id':
        if (element) {
          cj('#amountFields').hide();
        }
        else {
          cj('#amountFields').show();
        }
        break;

      case 'is_pledge_active':
      case 'is_allow_other_amount':
        if (element.checked) {
          if (priceSetID) cj( "#price_set_id" ).val('');
          cj('#amountFields').show();
        }
        cj("#amount_block_is_active").prop('checked', true );
        break;

      case 'amount_block_is_active':
        if (element.checked) {
          if (priceSetID) {
            cj('#amountFields').hide();
            cj( "#price_set_id" ).val(priceSetID);
          }
          else {
            cj('#amountFields').show();
            cj( "#price_set_id" ).val('');
          }
          cj('#priceSet, #recurringFields').show();
        }
        else {
          cj( "#price_set_id" ).val('');
          cj('#amountFields, #priceSet, #recurringFields').hide();
        }
        break;
      }
    }

    function showRecurring( paymentProcessorIds ) {
        var display = true;
        cj.each(paymentProcessorIds, function(k, id){
            if( cj.inArray(id, paymentProcessorMapper) == -1 ) {
                display = false;
            }
        });

        if(display) {
            cj( '#recurringContribution' ).show( );
        } else {
            if ( cj( '#is_recur' ).prop('checked' ) ) {
                cj( '#is_recur' ).prop('checked', false);
                cj( '#recurFields' ).hide( );
            }
            cj( '#recurringContribution' ).hide( );
        }
    }

    function showAdjustRecurring( paymentProcessorIds ) {
        var display = true;
        cj.each(paymentProcessorIds, function(k, id){
           if( cj.inArray(parseInt(id), {/literal}{$futurePaymentProcessor}{literal}) == -1 ) {
              display = false;
          }
        });

        if(display) {
            cj('#adjustRecurringFields').show();
        } else {
            if ( cj( '#adjust_recur_start_date' ).prop('checked' ) ) {
                cj( '#adjust_recur_start_date' ).prop('checked', false);
                cj( '#recurDefaults' ).hide( );
            }
            cj( '#adjustRecurringFields' ).hide( );
        }
    }

{/literal}{if $futurePaymentProcessor}{literal}
    function setDateDefaults() {
     {/literal}{if !$pledge_calendar_date}{literal}
       cj('#pledge_calendar_date').prop('disabled', 'disabled');
       cj("#pledge_calendar_date").next('input').prop('disabled', 'disabled');
     {/literal}{/if}

     {if !$pledge_calendar_month}{literal}
       cj('#pledge_calendar_month').prop('disabled', 'disabled');
     {/literal}{/if}{literal}
    }
{/literal}{/if}{literal}

</script>
{/literal}
{if !empty($form.is_recur)}
{include file="CRM/common/showHideByFieldValue.tpl"
    trigger_field_id    ="is_recur"
    trigger_value       ="true"
    target_element_id   ="recurFields"
    target_element_type ="table-row"
    field_type          ="radio"
    invert              = "false"
}
{/if}
{if !empty($form.adjust_recur_start_date)}
{include file="CRM/common/showHideByFieldValue.tpl"
    trigger_field_id    ="adjust_recur_start_date"
    trigger_value       ="true"
    target_element_id   ="recurDefaults"
    target_element_type ="table-row"
    field_type          ="radio"
    invert              = "false"
}
{/if}
{if $civiPledge}
{include file="CRM/common/showHideByFieldValue.tpl"
    trigger_field_id    = "is_pledge_active"
    trigger_value       = "true"
    target_element_id   = "pledgeFields"
    target_element_type = "table-row"
    field_type          = "radio"
    invert              = "false"
}
{/if}

{if $isQuick}
{literal}
<script type="text/javascript">
  CRM.$(function($) {
    $("#quickconfig").click(function(e) {
      e.preventDefault();
      CRM.confirm({
        width: 400,
        message: {/literal}"{ts escape='js'}Once you switch to using a Price Set, you won't be able to switch back to your existing settings below except by re-entering them. Are you sure you want to switch to a Price Set?{/ts}"{literal}
      }).on('crmConfirm:yes', function() {
        {/literal}
        var dataUrl = '{crmURL p="civicrm/ajax/rest" h=0 q="className=CRM_Core_Page_AJAX&fnName=setIsQuickConfig&context=civicrm_contribution_page&id=$contributionPageID"}';
        {literal}
        $.getJSON(dataUrl).done(function(result) {window.location = CRM.url("civicrm/admin/price/field", {reset: 1, action: 'browse', sid: result});});
      });
    });
  });
</script>
{/literal}
{/if}
{/crmRegion}
{crmRegion name="contribute-form-contributionpage-amount-post"}
{/crmRegion}
