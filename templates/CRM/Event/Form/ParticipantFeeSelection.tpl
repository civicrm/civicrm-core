{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
{* This template is used to change selections of fees for a participant *}
{literal}
<script type='text/javascript'>
function display(totalfee) {
  {/literal}{if $optionFullTotalAmount}
    totalfee += {$optionFullTotalAmount};{/if}
  {literal};
  // totalfee is monetary, round it to 2 decimal points so it can
  // go as a float - CRM-13491
  totalfee = Math.round(totalfee*100)/100;
  // note : some variables used used here are global variables defined inside Calculate.tpl
  var totalEventFee  = formatMoney( totalfee, 2, seperator, thousandMarker);
  cj('#pricevalue').html("<b>"+symbol+"</b> "+totalEventFee);
  scriptfee   = totalfee;
  scriptarray = price;
  cj('#total_amount').val(totalfee);
  ( totalfee < 0 ) ? cj('table#pricelabel').addClass('disabled') : cj('table#pricelabel').removeClass('disabled');

  // populate the balance amount div
  // change the status selections according to updated selections
  populatebalanceFee(totalfee, false);
}

function populatebalanceFee(updatedAmt, onlyStatusUpdate) {
  // updatedAmt is: selected line total

  // assign statuses
  var partiallyPaid = {/literal}{$partiallyPaid}{literal};
  var pendingRefund = {/literal}{$pendingRefund}{literal};
  var participantStatus = {/literal}{$participantStatus}{literal};

  // fee actually paid
  var feePaid = {/literal}{$feePaid}{literal};

  var updatedTotalLineTotal = updatedAmt;

  {/literal}{if $totalLineTotal}{literal}
    // line total of current participant stored in DB
    var linetotal = {/literal}{$lineItemTotal}{literal};

    // line total of all the participants
    var totalLineTotal = {/literal}{$totalLineTotal}{literal};
    updatedTotalLineTotal = totalLineTotal + (updatedAmt - linetotal);
  {/literal}{/if}{literal}

  // calculate the balance amount using total paid and updated amount
  var balanceAmt = updatedTotalLineTotal - feePaid;

  // change the status selections according to updated selections
  if (balanceAmt > 0 && feePaid != 0) {
    cj('#status_id').val(partiallyPaid);
  }
  else if(balanceAmt < 0) {
    cj('#status_id').val(pendingRefund);
  }
  else {
    cj('#status_id').val(participantStatus);
  }

  if (!onlyStatusUpdate) {
    balanceAmt = formatMoney(balanceAmt, 2, seperator, thousandMarker);
    cj('#balance-fee').text(symbol+" "+balanceAmt);
  }
}

CRM.$(function($) {
  var updatedFeeUnFormatted = $('#pricevalue').text();
  var updatedAmt = parseFloat(updatedFeeUnFormatted.replace(/[^0-9-.]/g, ''));

  populatebalanceFee(updatedAmt, true);
});

{/literal}
</script>
<h3>Change Registration Selections</h3>

<div class="crm-block crm-form-block crm-payment-form-block">
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
  {if !$email}
  <div class="messages status no-popup">
    <i class="crm-i fa-info-circle"></i>&nbsp;{ts}You will not be able to send an automatic email receipt for this payment because there is no email address recorded for this contact. If you want a receipt to be sent when this payment is recorded, click Cancel and then click Edit from the Summary tab to add an email address before recording the payment.{/ts}
  </div>
  {/if}
  <table class="form-layout">
    <tr>
      <td class="font-size12pt label"><strong>{ts}Participant{/ts}</strong></td><td class="font-size12pt"><strong>{$displayName}</strong></td>
    </tr>
    <tr>
      <td class='label'>{ts}Event{/ts}</td><td>{$eventName}</td>
    </tr>
    <tr class="crm-participant-form-block-status_id">
      <td class="label">{$form.status_id.label}</td>
      <td>{$form.status_id.html}</td>
    </tr>
  {if $lineItem}
     <tr class="crm-event-eventfees-form-block-line_items">
       <td class="label">{ts}Current Selections{/ts}</td>
       <td>{include file="CRM/Price/Page/LineItem.tpl" context="Event"}</td>
     </tr>
  {/if}
  </table>

  {if $priceSet.fields}
    <fieldset id="priceset" class="crm-group priceset-group">
      <table class='form-layout'>
        <tr class="crm-event-eventfees-form-block-price_set_amount">
          <td class="label" style="padding-top: 10px;">{$form.amount.label}</td>
          <td class="view-value"><table class="form-layout">{include file="CRM/Price/Form/PriceSet.tpl" extends="Event" noCalcValueDisplay=0 context="participant"}</table></td>
        </tr>
     {if $paymentInfo}
       <tr><td></td><td>
         <div class='crm-section'>
         <div class='label'>{ts}Updated Fee(s){/ts}</div><div id="pricevalue" class='content updated-fee'></div>
         <div class='label'>{ts}Total Paid{/ts}</div>
         <div class='content'>
           {$paymentInfo.paid|crmMoney}<br/>
           <a class="crm-hover-button action-item crm-popup medium-popup" href='{crmURL p="civicrm/payment" q="view=transaction&action=browse&cid=`$contactId`&id=`$paymentInfo.id`&component=`$paymentInfo.component`&context=transaction"}'><i class="crm-i fa-list-alt"></i> {ts}view payments{/ts}</a>
         </div>
         <div class='label'><strong>{ts}Balance Owed{/ts}</strong></div><div class='content'><strong id='balance-fee'></strong></div>
          </div>
       {include file='CRM/Price/Form/Calculate.tpl' currencySymbol=$currencySymbol noCalcValueDisplay=1 displayOveride='true'}
       {/if}
      </table>
    </fieldset>
  {/if}
{if $email}
    <fieldset id="email-receipt"><legend>{ts}Participant Confirmation{/ts}</legend>
      <table class="form-layout" style="width:auto;">
       <tr class="crm-event-eventfees-form-block-send_receipt">
          <td class="label">{ts}Send Confirmation{/ts}</td>
          <td>{$form.send_receipt.html}<br>
             <span class="description">{ts 1=$email}Automatically email a confirmation to %1?{/ts}</span>
          </td>
       </tr>
       <tr id="from-email" class="crm-event-eventfees-form-block-from_email_address">
         <td class="label">{$form.from_email_address.label}</td>
         <td>{$form.from_email_address.html} {help id="id-from_email" file="CRM/Contact/Form/Task/Email.hlp" isAdmin=$isAdmin}</td>
       </tr>
       <tr id='notice' class="crm-event-eventfees-form-block-receipt_text">
         <td class="label">{$form.receipt_text.label}</td>
         <td><span class="description">
             {ts}Enter a message you want included at the beginning of the confirmation email. EXAMPLE: 'We have made the changes you requested to your registration.'{/ts}
             </span><br />
             {$form.receipt_text.html|crmAddClass:huge}
         </td>
       </tr>
      </table>
    </fieldset>
{/if}
    <fieldset>
      <table class="form-layout">
        <tr class="crm-participant-form-block-note">
          <td class="label">{$form.note.label}</td><td>{$form.note.html}</td>
        </tr>
      </table>
    </fieldset>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
{if $email}
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
{literal}
<script type='text/javascript'>
CRM.$(function($) {
  var $form = $('form.{/literal}{$form.formClass}{literal}');
  cj('.total_amount-section').remove();

  cj($form).submit(function(e) {
    var partiallyPaid = {/literal}{$partiallyPaid}{literal};
    var pendingRefund = {/literal}{$pendingRefund}{literal};
    var statusId = cj('#status_id').val();
    var statusLabel = cj('#status_id option:selected').text();
    var balanceFee = cj('#balance-fee').text();

    // fee actually paid
    var feePaid = {/literal}{$feePaid}{literal};

    balanceFee = parseFloat(balanceFee.replace(/[^0-9-.]/g, ''));

    if ((balanceFee > 0 && feePaid != 0) && statusId != partiallyPaid) {
      var result = confirm('Balance is owing for the updated selections. Expected participant status is \'Partially paid\'. Are you sure you want to set the participant status to ' + statusLabel + ' ? Click OK to continue, Cancel to change your entries.');
      if (result == false) {
        e.preventDefault();
      }
    }
    else if ((balanceFee < 0 && feePaid != 0) && statusId != pendingRefund) {
      var result = confirm('Balance is overpaid for the updated selections. Expected participant status is \'Pending refund\'. Are you sure you want to set the participant status to ' + statusLabel + ' ? Click OK to continue, Cancel to change your entries');
      if (result == false) {
        e.preventDefault();
      }
    }
  });
});
</script>
{/literal}
