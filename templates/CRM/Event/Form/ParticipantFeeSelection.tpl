{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
   
<h3>Change Registration Selections</h3>

<div class="crm-block crm-form-block crm-payment-form-block">
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
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
          <td class="view-value"><table class="form-layout">{include file="CRM/Price/Form/PriceSet.tpl" extends="Event"}</table></td>
        </tr>  
     {if $paymentInfo}
       <tr><td></td><td>
         <div class='crm-section'> 
         <div class='label'>{ts}Updated Fee(s){/ts}</div><div id="pricevalue" class='content updated-fee'></div>
         <div class='label'>{ts}Total Paid{/ts}</div>
         <div class='content'><a class='action-item' href='{crmURL p="civicrm/payment/view" q="action=browse&cid=`$contactId`&id=`$paymentInfo.id`&component=`$paymentInfo.component`&context=transaction"}'>{$paymentInfo.paid|crmMoney}<br/>>> view payments</a>
         </div>
         <div class='label'><strong>{ts}Balance Owed{/ts}</strong></div><div id='balance-fee' class='content'></div>
          </div>
       {include file='CRM/Price/Form/Calculate.tpl' currencySymbol=$currencySymbol noCalcValueDisplay='false'}
       {/if}    
      </table>
    </fieldset>
  {/if}
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
{literal}
<script type='text/javascript'>
cj(function(){
  cj('.total_amount-section').remove();
  cj('#pricevalue').val().trigger('populatebalanceFee');

  function populatebalanceFee() {
    console.log('dd');
    // calculate the balance amount using total paid and updated amount
    var updatedFeeUnFormatted = cj('#pricevalue').val();
    var updatedAmt = parseFloat(updatedFeeUnFormatted.replace(/[^0-9-.]/g, ''));
    var balanceAmt = updatedAmt - CRM.feePaid;
    balanceAmt = formatMoney(balanceAmt, 2, seperator, thousandMarker);
    cj('#balance-fee').val(balanceAmt);
  }
});
</script>
{/literal}