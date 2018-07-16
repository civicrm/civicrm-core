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
{if $transaction}
  {include file="CRM/Contribute/Form/PaymentInfoBlock.tpl"}
  {if !$suppressPaymentFormButtons}
    <div class="crm-submit-buttons">
       {include file="CRM/common/formButtons.tpl"}
    </div>
  {/if}
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
      {if $email and $outBound_option != 2}
        <tr class="crm-payment-form-block-is_email_receipt">
          <td class="label">
            {$form.is_email_receipt.label}
          </td>
          <td>{$form.is_email_receipt.html}&nbsp;
              <span class="description">{ts 1=$email}Automatically email a receipt to %1?{/ts}</span>
          </td>
        </tr>
        <tr id="fromEmail" class="crm-payment-form-block-from_email_address" style="display:none;">
          <td class="label">{$form.from_email_address.label}</td>
          <td>{$form.from_email_address.html} {help id="id-from_email" file="CRM/Contact/Form/Task/Email.hlp" isAdmin=$isAdmin}</td>
        </tr>
      {/if}
      {if $contributionMode}
        <tr class="crm-payment-form-block-payment_processor_id"><td class="label nowrap">{$form.payment_processor_id.label}<span class="crm-marker"> * </span></td><td>{$form.payment_processor_id.html}</td></tr>
      {/if}
    </tr>
   </table>

    <div class="crm-accordion-wrapper crm-accordion_title-accordion crm-accordion-processed" id="paymentDetails_Information">
      {if !$contributionMode}
      <div class="crm-accordion-header">
        {if $paymentType EQ 'refund'}{ts}Refund Details{/ts}{else}{ts}Payment Details{/ts}{/if}
      </div>
      <div class="crm-accordion-body">
        <table class="form-layout-compressed" >
          <tr class="crm-payment-form-block-trxn_date">
            <td class="label">{$form.trxn_date.label}</td>
            <td>{$form.trxn_date.html}<br />
              <span class="description">{ts}The date this payment was received.{/ts}</span>
            </td>
          </tr>
          <tr class="crm-payment-form-block-payment_instrument_id">
            <td class="label">{$form.payment_instrument_id.label}</td>
            <td >{$form.payment_instrument_id.html} {help id="payment_instrument_id"}</td>
            </td>
          </tr>
          <tr class="crm-payment-form-block-trxn_id">
            <td class="label">{$form.trxn_id.label}</td>
            <td>{$form.trxn_id.html} {help id="id-trans_id"}</td>
          </tr>
          <tr class="crm-payment-form-block-fee_amount"><td class="label">{$form.fee_amount.label}</td><td{$valueStyle}>{$form.fee_amount.html|crmMoney:$currency:'XXX':'YYY'}<br />
            <span class="description">{ts}Processing fee for this transaction (if applicable).{/ts}</span></td></tr>
           <tr class="crm-payment-form-block-net_amount"><td class="label">{$form.net_amount.label}</td><td>{$form.net_amount.html|crmMoney:$currency:'':1}<br />
            <span class="description">{ts}Net value of the payment (Total Amount minus Fee).{/ts}</span></td></tr>
        </table>
      </div>
      {/if}
      {include file='CRM/Core/BillingBlockWrapper.tpl'}
    </div>

    {literal}
    <script type="text/javascript">

    var url = {/literal}{$dataUrl|@json_encode}{literal};

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
          if ($('#is_email_receipt', $form).prop('checked')) {
            $('#fromEmail', $form).show();
          }
          else {
           $('#fromEmail', $form).hide( );
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

    </script>
    {/literal}
{/if}
