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

  <tr class="crm-payment-details-block-financial_type_id">
    <td class="label">{$form.financial_type_id.label}<span class="crm-marker"> *</span></td>
    <td>{$form.financial_type_id.html}<br /><span class="description">{ts}Select the appropriate financial type for this payment.{/ts}</span></td>
  </tr>
  <tr class="crm-payment-details-block-total_amount">
    <td class="label">{$form.total_amount.label}</td>
    <td>{$form.total_amount.html|crmMoney:$currency}<br />
            <span class="description">{ts}Membership payment amount. A contribution record will be created for this amount.{/ts}</span><div class="totaltaxAmount"></div></td>
  </tr>
  <tr class="crm-payment-details-block-receive_date">
    <td class="label" >{$form.receive_date.label}</td>
    <td>{include file="CRM/common/jcalendar.tpl" elementName=receive_date}</td>
  </tr>
  <tr class="crm-payment-details-block-payment_instrument_id">
    <td class="label">{$form.payment_instrument_id.label}<span class="crm-marker"> *</span></td>
    <td>{$form.payment_instrument_id.html} {help id="payment_instrument_id" file="CRM/Contribute/Page/Tab.hlp"}</td>
  </tr>
  <tr id="creditCardType" class="crm-payment-details-block-credit_card_type">
    <td class="label">{$form.credit_card_type.label}</td>
    <td>{$form.credit_card_type.html}</td>
  </tr>
  <tr id="checkNumber" class="crm-payment-details-block-check_number">
    <td class="label">{$form.check_number.label}</td>
    <td>{$form.check_number.html|crmAddClass:six}</td>
  </tr>
  {if $showTransactionId }
    <tr class="crm-payment-details-block-trxn_id">
      <td class="label">{$form.trxn_id.label}</td>
      <td>{$form.trxn_id.html}</td>
    </tr>
  {/if}
  <tr class="crm-payment-details-block-contribution_status_id">
    <td class="label">{$form.contribution_status_id.label}</td>
    <td>{$form.contribution_status_id.html}</td>
  </tr>

{literal}
<script type="text/javascript">

CRM.$(function($) {
  onPaymentMethodChange();
  $("#payment_instrument_id").on("change",function(){
    onPaymentMethodChange();
  });

  function onPaymentMethodChange() {
    var paymentInstrument = $('#payment_instrument_id').val();
    if (paymentInstrument == 4) {
      $('tr#checkNumber').show();
      $('tr#creditCardType').hide();
    }
    else if (paymentInstrument == 1 || paymentInstrument == 2) {
      $('tr#creditCardType').show();
      $('tr#checkNumber').hide();
    }
    else {
      $('tr#checkNumber').hide();
      $('tr#creditCardType').hide();
    }
  }
});

</script>
{/literal}
