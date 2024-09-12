{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This code is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{assign var=isRecordPayment value=1}
{capture assign="isShowBillingBlock"}{if $action neq 2}1{else}0{/if}{/capture}
{if $paid} {* We retrieve this tpl when event is selected - keep it empty if event is not paid *}
    <table class="form-layout">
    {if $priceSet}
      {if $discount and $hasPayment}
        <tr class="crm-event-eventfees-form-block-discount"><td class="label">&nbsp;&nbsp;{ts}Discount Set{/ts}</td><td class="view-value">{$discount}</td></tr>
      {elseif $form.discount_id.label}
        <tr class="crm-event-eventfees-form-block-discount_id"><td class="label">&nbsp;&nbsp;{$form.discount_id.label}</td><td>{$form.discount_id.html}</td></tr>
      {/if}
      {if $action eq 2 and $hasPayment} {* Updating *}
         {if $lineItem}
            <tr class="crm-event-eventfees-form-block-line_items">
               <td class="label">{ts}Selections{/ts}</td>
               <td>{include file="CRM/Price/Page/LineItem.tpl" context="Event"}</td>
            </tr>
         {else}
            <tr class="crm-event-eventfees-form-block-event_level">
               <td class="label">{ts}Event Level{/ts}</td>
               <td>{$fee_level}&nbsp;{if $fee_amount}- {$fee_amount|crmMoney:$fee_currency}{/if}</td>
            </tr>
         {/if}
          <tr>
            <td></td>
            <td>
              <a class="action-item crm-hover-button" href='{crmURL p="civicrm/event/participant/feeselection" q="reset=1&id=`$participantId`&cid=`$contactId`&action=update"}'><i class="crm-i fa-pencil" aria-hidden="true"></i> {ts}Change Selections{/ts}</a>
            </td>
         </tr>
      {else} {* New participant *}
        {if $priceSet.fields}
          <fieldset id="priceset" class="crm-group priceset-group">
            <tr class="crm-event-eventfees-form-block-price_set_amount">
            <td class="label" style="padding-top: 10px;">{$form.amount.label}</td>
            <td class="view-value"><table class="form-layout">{include file="CRM/Price/Form/PriceSet.tpl" extends="Event" hideTotal=false isShowAdminVisibilityFields=true}</td>
          </fieldset>
        {else}
          {assign var=isRecordPayment value=0}
          <div class='messages status'>{ts}No active price fields found for this event!{/ts}</div>
        {/if}
        </table>
      {/if}
      </td>
      </tr>
    {/if}

    {if $accessContribution and ! $participantMode and ($action neq 2 or !$rows.0.contribution_id or $onlinePendingContributionId) and $isRecordPayment and ! $registeredByParticipantId}
      {assign var=isShowBillingBlock value=true}
        <tr class="crm-event-eventfees-form-block-record_contribution">
            <td class="label">{$form.record_contribution.label}</td>
            <td>{$form.record_contribution.html}</td>
        </tr>
        <tr id="payment_information" class="crm-event-eventfees-form-block-payment_information">
           <td class ='html-adjust' colspan=2>
           <fieldset><legend>{ts}Payment Information{/ts}</legend>
             <table id="recordContribution" class="form-layout" style="width:auto;">
                <tr class="crm-event-eventfees-form-block-financial_type_id">
                    <td class="label">{$form.financial_type_id.label}<span class="crm-marker"> *</span></td>
                    <td>{$form.financial_type_id.html}</td>
                </tr>
                <tr class="crm-event-eventfees-form-block-total_amount"><td class="label">{$form.total_amount.label}</td><td>{$form.total_amount.html}</td></tr>
                <tr>
                    <td class="label" >{$form.receive_date.label}</td>
                    <td>{$form.receive_date.html}</td>
                </tr>
                {if $showTransactionId}
                    <tr class="crm-event-eventfees-form-block-trxn_id"><td class="label">{$form.trxn_id.label}</td><td>{$form.trxn_id.html}</td></tr>
                {/if}
                <tr class="crm-event-eventfees-form-block-contribution_status_id"><td class="label">{$form.contribution_status_id.label}</td><td>{$form.contribution_status_id.html}</td></tr>
                <tr class="crm-event-eventfees-form-block-payment_instrument_id"><td class="label">{$form.payment_instrument_id.label}<span class="crm-marker"> *</span></td><td>{$form.payment_instrument_id.html} {help id="payment_instrument_id" file="CRM/Contribute/Page/Tab.hlp"}</td></tr>
             </table>
           </fieldset>
           </td>
        </tr>
    {/if}
    </table>

{/if}

{if $isShowBillingBlock}
  {include file='CRM/Core/BillingBlockWrapper.tpl'}
{/if}

{if ($email OR $batchEmail) and $outBound_option != 2}
    <fieldset id="send_confirmation_receipt"><legend>{if $paid}{ts}Registration Confirmation and Receipt{/ts}{else}{ts}Registration Confirmation{/ts}{/if}</legend>
      <table class="form-layout" style="width:auto;">
         <tr class="crm-event-eventfees-form-block-send_receipt">
            <td class="label">{if $paid}{ts}Send Confirmation and Receipt{/ts}{else}{ts}Send Confirmation{/ts}{/if}</td>
            <td>{$form.send_receipt.html}
              {if $email}
                {if $paid}
                  <span class="description">{ts 1=$email}Automatically email a confirmation and receipt to %1?{/ts}</span></td>
                {else}
                  <span class="description">{ts 1=$email}Automatically email a confirmation to %1?{/ts}</span></td>
                {/if}
              {/if}
        </tr>
        <tr id="from-email" class="crm-event-eventfees-form-block-from_email_address">
          <td class="label">{$form.from_email_address.label}</td>
          <td>{$form.from_email_address.html}  {help id="id-from_email" file="CRM/Contact/Form/Task/Help/Email/id-from_email.hlp" title=$form.from_email_address.label}</td>
        </tr>
        <tr id='notice' class="crm-event-eventfees-form-block-receipt_text">
        <td class="label">{$form.receipt_text.label}</td>
          <td>{$form.receipt_text.html|crmAddClass:huge}<br />
             <span class="description">
             {ts}Enter a message you want included at the beginning of the confirmation email.{/ts}
             </span>
          </td>
        </tr>
      </table>
    </fieldset>
{elseif $context eq 'standalone' and $outBound_option != 2}
    <fieldset id="email-receipt" style="display:none;"><legend>{if $paid}{ts}Registration Confirmation and Receipt{/ts}{else}{ts}Registration Confirmation{/ts}{/if}</legend>
      <table class="form-layout" style="width:auto;">
       <tr class="crm-event-eventfees-form-block-send_receipt">
          <td class="label">{if $paid}{ts}Send Confirmation and Receipt{/ts}{else}{ts}Send Confirmation{/ts}{/if}</td>
          <td>{$form.send_receipt.html}
            {if $paid}
              <span class="description">{ts 1='<span id="email-address"></span>'}Automatically email a confirmation and receipt to %1?{/ts}</span>
            {else}
              <span class="description">{ts 1='<span id="email-address"></span>'}Automatically email a confirmation to %1?{/ts}</span>
            {/if}
          </td>
      </tr>
      <tr id="from-email" class="crm-event-eventfees-form-block-from_email_address">
        <td class="label">{$form.from_email_address.label}</td>
        <td>{$form.from_email_address.html} {help id="id-from_email" file="CRM/Contact/Form/Task/Help/Email/id-from_email.hlp" title=$form.from_email_address.label}</td>
      </tr>
      <tr id='notice' class="crm-event-eventfees-form-block-receipt_text">
        <td class="label">{$form.receipt_text.label}</td>
          <td>{$form.receipt_text.html|crmAddClass:huge}<br />
             <span class="description">
             {ts}Enter a message you want included at the beginning of the confirmation email.{/ts}
             </span>
          </td>
        </tr>
      </table>
    </fieldset>
{/if}

{if ($email and $outBound_option != 2) OR $context eq 'standalone'} {* Send receipt field only present if contact has a valid email address. *}
{include file="CRM/common/showHideByFieldValue.tpl"
    trigger_field_id    ="send_receipt"
    trigger_value       =""
    target_element_id   ="notice"
    target_element_type ="table-row"
    field_type          ="radio"
    invert              = 0
}
{include file="CRM/common/showHideByFieldValue.tpl"
    trigger_field_id    ="send_receipt"
    trigger_value       =""
    target_element_id   ="from-email"
    target_element_type ="table-row"
    field_type          ="radio"
    invert              = 0
}
{/if}

{if $context eq 'standalone' and $outBound_option != 2}
<script type="text/javascript">
{literal}
  CRM.$(function($) {
    var $form = $("form.{/literal}{$form.formClass}{literal}");
    $("#contact_id", $form).change(checkEmail);
    checkEmail();

    function checkEmail( ) {
      var data = $("#contact_id", $form).select2('data');
      if (data && data.extra && data.extra.email && data.extra.email.length) {
        $("#email-receipt", $form).show();
        if ($("#send_receipt", $form).is(':checked')) {
          $("#notice", $form).show();
        }
        $("#email-address", $form).html(data.extra.email);
      }
      else {
        $("#email-receipt, #notice", $form).hide();
      }
    }
  });
{/literal}
</script>
{/if}

{if $onlinePendingContributionId}
<script type="text/javascript">
{literal}
  function confirmStatus( pStatusId, cStatusId ) {
     if ( (pStatusId == cj("#status_id").val() ) && (cStatusId == cj("#contribution_status_id").val()) ) {
         var allow = confirm( '{/literal}{ts escape='js'}The Payment Status for this participant is Completed. The Participant Status is set to Pending (pay later). Click Cancel if you want to review or modify these values before saving this record{/ts}{literal}.' );
         if ( !allow ) return false;
     }
  }

  function checkCancelled( statusId, pStatusId, cStatusId ) {
    //selected participant status is 'cancelled'
    if ( statusId == pStatusId ) {
       cj("#contribution_status_id").val( cStatusId );

       //unset value for send receipt check box.
       cj("#send_receipt").prop("checked", false );
       cj("#send_confirmation_receipt").hide( );

       // set receive data to null.
       clearDateTime( 'receive_date' );
    } else {
       cj("#send_confirmation_receipt").show( );
    }
    sendNotification();
  }

{/literal}
</script>
{/if}
{if $showFeeBlock && $feeBlockPaid && ! $priceSet && $action neq 2}
<script>
{literal}
     fillTotalAmount( );

     function fillTotalAmount( totalAmount ) {
          if ( !totalAmount ) {
        var amountVal = {/literal}{if !empty($form.amount.value)}{$form.amount.value}{else}0{/if}{literal};
        if ( amountVal > 0 ) {
               var eventFeeBlockValues = {/literal}{$eventFeeBlockValues}{literal};
          totalAmount = eval('eventFeeBlockValues.amount_id_'+ amountVal);
              } else {
          totalAmount = '';
        }
    }
          cj('#total_amount').val( totalAmount );
     }
{/literal}
</script>
{/if}

{* ADD mode if *}
