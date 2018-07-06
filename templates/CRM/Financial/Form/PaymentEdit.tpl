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
