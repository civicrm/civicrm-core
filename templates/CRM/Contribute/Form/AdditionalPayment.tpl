{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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
{if $transaction}
  {if !empty($rows)}
   <table id='info'>
     <tr class="columnheader">
       <th>{ts}Amount{/ts}</th>
       <th>{ts}Type{/ts}</th>
       <th>{ts}Payment Method{/ts}</th>
       <th>{ts}Received{/ts}</th>
       <th>{ts}Transaction ID{/ts}</th>
       <th>{ts}Status{/ts}</th>
     </tr>
     {foreach from=$rows item=row}
     <tr>
       <td>{$row.total_amount|crmMoney:$row.currency}</td>
       <td>{$row.financial_type}</td>
       <td>{$row.payment_instrument}{if $row.check_number} (#{$row.check_number}){/if}</td>
       <td>{$row.receive_date|crmDate}</td>
       <td>{$row.trxn_id}</td>
       <td>{$row.status}</td>
     </tr>
     {/foreach}
    <table>
  {else}
     {if $component eq 'event'}
       {assign var='entity' value='participant'}
     {else}
       {assign var='entity' value=$component}
     {/if}
     {ts 1=$entity}No payments found for this %1 record{/ts}
  {/if}
  {if !$suppressPaymentFormButtons}
    <div class="crm-submit-buttons">
       {include file="CRM/common/formButtons.tpl"}
    </div>
  {/if}
 {elseif $formType}
  {include file="CRM/Contribute/Form/AdditionalInfo/$formType.tpl"}
{else}

<div class="crm-block crm-form-block crm-payment-form-block">

  {if !$email}
  <div class="messages status no-popup">
    <div class="icon inform-icon"></div>&nbsp;{ts}You will not be able to send an automatic email receipt for this payment because there is no email address recorded for this contact. If you want a receipt to be sent when this payment is recorded, click Cancel and then click Edit from the Summary tab to add an email address before recording the payment.{/ts}
  </div>
  {/if}
  {if $newCredit AND $contributionMode EQ null}
    {if $contactId}
      {capture assign=ccModeLink}{crmURL p='civicrm/payment/add' q="reset=1&action=add&cid=`$contactId`&id=`$id`&component=`$component`&mode=live"}{/capture}
    {/if}
    {if $paymentType eq 'owed'}
      <div class="action-link css_right crm-link-credit-card-mode">
        <a class="open-inline-noreturn action-item crm-hover-button" href="{$ccModeLink}">&raquo; {ts}submit credit card payment{/ts}</a>
      </div>
    {/if}
  {/if}
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl"}
  </div>
  <table class="form-layout-compressed">
    <tr>
      <td class="font-size12pt label"><strong>{if $component eq 'event'}{ts}Participant{/ts}{else}{ts}Contact{/ts}{/if}</strong></td><td class="font-size12pt"><strong>{$displayName}</strong></td>
    </tr>
    {if $contributionMode}
      <tr class="crm-payment-form-block-payment_processor_id"><td class="label nowrap">{$form.payment_processor_id.label}<span class="crm-marker"> * </span></td><td>{$form.payment_processor_id.html}</td></tr>
    {/if}
    {if $eventName}
      <tr>
        <td class='label'>{ts}Event{/ts}</td><td>{$eventName}</td>
      </tr>
    {/if}
    <tr class="crm-payment-form-block-total_amount">
      <td class="label">{$form.total_amount.label}</td>
      <td>
        <span id='totalAmount'>{$form.currency.html|crmAddClass:eight}&nbsp;{$form.total_amount.html|crmAddClass:eight}</span>&nbsp; <span class="status">{if $paymentType EQ 'refund'}{ts}Refund Due{/ts}{else}{ts}Balance Owed{/ts}{/if}:&nbsp;{$paymentAmt|crmMoney}</span>
      </td>
    </tr>
   </table>
    <div class="crm-accordion-wrapper crm-accordion_title-accordion crm-accordion-processed" id="paymentDetails_Information">
      <div class="crm-accordion-header">
        {if $paymentType EQ 'refund'}{ts}Refund Details{/ts}{else}{ts}Payment Details{/ts}{/if}
      </div>
      <div class="crm-accordion-body">
        <table class="form-layout-compressed" >
          <tr class="crm-payment-form-block-trxn_date">
            <td class="label">{$form.trxn_date.label}</td>
            <td {$valueStyle}>{include file="CRM/common/jcalendar.tpl" elementName=trxn_date}<br />
              <span class="description">{ts}The date this payment was received.{/ts}</span>
            </td>
          </tr>
          <tr class="crm-payment-form-block-payment_instrument_id">
            <td class="label">{$form.payment_instrument_id.label}</td>
            <td {$valueStyle}>{$form.payment_instrument_id.html} {help id="payment_instrument_id" file="CRM/Contribute/Page/Tab.hlp"}</td>
            </td>
          </tr>
	  {if !$isOnline}
            <tr id="cardType" class="crm-payment-form-block-credit_card_type">
              <td class="label">{$form.credit_card_type.label}</td>
              <td {$valueStyle}>{$form.credit_card_type.html}</td>
            </tr>
            <tr id="cardNumber" class="crm-payment-form-block-credit_card_number">
              <td class="label">{$form.credit_card_number.label}</td>
              <td {$valueStyle}>{$form.credit_card_number.html} {help id="pan_truncation" file="CRM/Contribute/Page/Tab.hlp"}</td>
            </tr>
          {/if}
          {if $showCheckNumber || !$isOnline}
            <tr id="checkNumber" class="crm-payment-form-block-check_number">
              <td class="label">{$form.check_number.label}</td>
              <td>{$form.check_number.html}</td>
            </tr>
          {/if}
          <tr class="crm-payment-form-block-trxn_id">
            <td class="label">{$form.trxn_id.label}</td>
            <td {$valueStyle}>{$form.trxn_id.html} {help id="id-trans_id" file="CRM/Contribute/Page/Tab.hlp"}</td>
          </tr>
          {if $email and $outBound_option != 2}
            <tr class="crm-payment-form-block-is_email_receipt">
              <td class="label">
                {$form.is_email_receipt.label}</td><td>{$form.is_email_receipt.html}&nbsp;
                <span class="description">{ts 1=$email}Automatically email a receipt to %1?{/ts}</span>
              </td>
            </tr>
          {/if}
          <tr id="fromEmail" class="crm-payment-form-block-receipt_date" style="display:none;">
            <td class="label">{$form.from_email_address.label}</td>
            <td>{$form.from_email_address.html}</td>
          </tr>
          <tr id='notice' class="crm-event-eventfees-form-block-receipt_text">
            <td class="label">{$form.receipt_text.label}</td>
            <td><span class="description">
                {ts}Enter a message you want included at the beginning of the confirmation email.{/ts}
                </span><br />
                {$form.receipt_text.html|crmAddClass:huge}
            </td>
          </tr>
           <tr class="crm-payment-form-block-fee_amount"><td class="label">{$form.fee_amount.label}</td><td{$valueStyle}>{$form.fee_amount.html|crmMoney:$currency:'XXX':'YYY'}<br />
            <span class="description">{ts}Processing fee for this transaction (if applicable).{/ts}</span></td></tr>
           <tr class="crm-payment-form-block-net_amount"><td class="label">{$form.net_amount.label}</td><td{$valueStyle}>{$form.net_amount.html|crmMoney:$currency:'':1}<br />
            <span class="description">{ts}Net value of the payment (Total Amount minus Fee).{/ts}</span></td></tr>
        </table>
      </div>
    </div>

<div class="accordion ui-accordion ui-widget ui-helper-reset">
  {* Additional Detail / Honoree Information / Premium Information *}
    {foreach from=$allPanes key=paneName item=paneValue}

      <div class="crm-accordion-wrapper crm-ajax-accordion crm-{$paneValue.id}-accordion {if $paneValue.open neq 'true'}collapsed{/if}">
        <div class="crm-accordion-header" id="{$paneValue.id}">

          {$paneName}
        </div><!-- /.crm-accordion-header -->
        <div class="crm-accordion-body">

          <div class="{$paneValue.id}"></div>
        </div><!-- /.crm-accordion-body -->
      </div><!-- /.crm-accordion-wrapper -->

    {/foreach}
  </div>



    {literal}
    <script type="text/javascript">

    var url = "{/literal}{$dataUrl}{literal}";

      CRM.$(function($) {
        showHideByValue( 'is_email_receipt', '', 'notice', 'table-row', 'radio', false );
        showHideByValue( 'is_email_receipt', '', 'fromEmail', 'table-row', 'radio', false );
      });
    {/literal}
  </script>

<br />
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
  {literal}
    <script type="text/javascript">
      function verify() {
        if (cj('#is_email_receipt').prop('checked')) {
          return confirm( '{/literal}{ts escape='js'}Click OK to save this payment record AND send a receipt to the contributor now{/ts}{literal}.' );
        }
      }
      CRM.$(function($) {
        var $form = $('form.{/literal}{$form.formClass}{literal}');
        checkEmailDependancies();
        $('#is_email_receipt', $form).click(function() {
          checkEmailDependancies();
        });

        function checkEmailDependancies() {
          if ($('#is_email_receipt', $form).attr('checked')) {
            $('#fromEmail, #notice', $form).show();
            $('#receiptDate', $form).hide();
          }
          else {
            $('#fromEmail, #notice', $form).hide( );
            $('#receiptDate', $form).show();
          }
        }
  
        // bind first click of accordion header to load crm-accordion-body with snippet
        $('#adjust-option-type', $form).hide();
        $('.crm-ajax-accordion .crm-accordion-header', $form).one('click', function() {
          loadPanes($(this).attr('id'));
        });
        $('.crm-ajax-accordion:not(.collapsed) .crm-accordion-header', $form).each(function(index) {
          loadPanes($(this).attr('id'));
        });
        // load panes function call for snippet based on id of crm-accordion-header
        function loadPanes(id) {
          var url = "{/literal}{crmURL p='civicrm/payment/add' q='formType=' h=0}{literal}" + id;
          {/literal}
          {if $contributionMode}
            url += "&mode={$contributionMode}";
          {/if}
          {if $qfKey}
            url += "&qfKey={$qfKey}";
          {/if}
          {literal}
          if (!$('div.'+ id, $form).html()) {
            CRM.loadPage(url, {target: $('div.' + id, $form)});
          }
        }
        
        $('#fee_amount', $form).change( function() {
          var totalAmount = $('#total_amount', $form).val();
          var feeAmount = $('#fee_amount', $form).val();
          var netAmount = totalAmount.replace(/,/g, '') - feeAmount.replace(/,/g, '');
          if (!$('#net_amount', $form).val() && totalAmount) {
            $('#net_amount', $form).val(CRM.formatMoney(netAmount, true));
          }
        });
      });
      CRM.$(function($) {
        onPaymentMethodChange();
  	$("#payment_instrument_id").on("change",function(){
    	  onPaymentMethodChange();
  	});

  	function onPaymentMethodChange() {
    	  var paymentInstrument = $('#payment_instrument_id').val();
    	  if (paymentInstrument == 4) {
      	    $('tr#checkNumber').show();
      	    $('tr#cardType').hide();
      	    $('tr#cardNumber').hide();
    	  }
    	  else if (paymentInstrument == 1) {
      	    $('tr#cardType').show();
      	    $('tr#cardNumber').show();
      	    $('tr#checkNumber').hide();
    	  }
    	  else {
            $('tr#checkNumber').hide();
      	    $('tr#cardType').hide();
      	    $('tr#cardNumber').hide();
          }
        }
      });
    </script>
    {/literal}
{/if}
