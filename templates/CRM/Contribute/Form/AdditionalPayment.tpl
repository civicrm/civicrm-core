{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
      <td class="font-size12pt label"><strong>{ts}Participant{/ts}</strong></td><td class="font-size12pt"><strong>{$displayName}</strong></td>
    </tr>
    {if $contributionMode}
      <tr class="crm-payment-form-block-payment_processor_id"><td class="label nowrap">{$form.payment_processor_id.label}<span class="crm-marker"> * </span></td><td>{$form.payment_processor_id.html}</td></tr>
    {/if}
    <tr>
      <td class='label'>{ts}Event{/ts}</td><td>{$eventName}</td>
    </tr>
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
            <td {$valueStyle}>{$form.payment_instrument_id.html} {help id="payment_instrument_id"}</td>
            </td>
          </tr>
          {if $showCheckNumber || !$isOnline}
            <tr id="checkNumber" class="crm-payment-form-block-check_number">
              <td class="label">{$form.check_number.label}</td>
              <td>{$form.check_number.html}</td>
            </tr>
          {/if}
          <tr class="crm-payment-form-block-trxn_id">
            <td class="label">{$form.trxn_id.label}</td>
            <td {$valueStyle}>{$form.trxn_id.html} {help id="id-trans_id"}</td>
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
  function verify( ) {
    if (cj('#is_email_receipt').prop('checked')) {
      var ok = confirm( '{/literal}{ts escape='js'}Click OK to save this payment record AND send a receipt to the contributor now{/ts}{literal}.' );
      if (!ok) {
        return false;
      }
    }
  }
  </script>
  {/literal}

    {literal}
    <script type="text/javascript">
      CRM.$(function($) {
        checkEmailDependancies( );
        cj('#is_email_receipt').click( function( ) {
          checkEmailDependancies( );
        });
      });

      function checkEmailDependancies( ) {
        if (cj('#is_email_receipt').attr( 'checked' )) {
          cj('#fromEmail').show( );
          cj('#receiptDate').hide( );
          cj('#notice').show( );
        }
        else {
          cj('#fromEmail').hide( );
          cj('#notice').hide( );
          cj('#receiptDate').show( );
        }
      }

    // bind first click of accordion header to load crm-accordion-body with snippet
    // everything else taken care of by cj().crm-accordions()
    CRM.$(function($) {
      cj('#adjust-option-type').hide();
      cj('.crm-ajax-accordion .crm-accordion-header').one('click', function() {
        loadPanes(cj(this).attr('id'));
      });
      cj('.crm-ajax-accordion:not(.collapsed) .crm-accordion-header').each(function(index) {
        loadPanes(cj(this).attr('id'));
      });
    });
    // load panes function call for snippet based on id of crm-accordion-header
    function loadPanes( id ) {
      var url = "{/literal}{crmURL p='civicrm/payment/add' q='snippet=4&formType=' h=0}{literal}" + id;
      {/literal}
      {if $contributionMode}
        url = url + "&mode={$contributionMode}";
      {/if}
      {if $qfKey}
        url = url + "&qfKey={$qfKey}";
      {/if}
      {literal}
      if (! cj('div.'+id).html()) {
        var loading = '<img src="{/literal}{$config->resourceBase}i/loading.gif{literal}" alt="{/literal}{ts escape='js'}loading{/ts}{literal}" />&nbsp;{/literal}{ts escape='js'}Loading{/ts}{literal}...';
        cj('div.'+id).html(loading);
        cj.ajax({
          url    : url,
          success: function(data) { cj('div.'+id).html(data).trigger('crmLoad'); }
        });
      }
    }

cj('#fee_amount').change( function() {
  var totalAmount = cj('#total_amount').val();
  var feeAmount = cj('#fee_amount').val();
  var netAmount = totalAmount.replace(/,/g, '') - feeAmount.replace(/,/g, '');
  if (!cj('#net_amount').val() && totalAmount) {
    cj('#net_amount').val(CRM.formatMoney(netAmount, true));
  }
});
    </script>
    {/literal}
      {if !$contributionMode}
        {include file="CRM/common/showHideByFieldValue.tpl"
        trigger_field_id    ="payment_instrument_id"
        trigger_value       = '4'
        target_element_id   ="checkNumber"
        target_element_type ="table-row"
        field_type          ="select"
        invert              = 0
        }
    {/if}
{/if}
