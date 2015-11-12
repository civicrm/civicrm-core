{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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

{assign var='hideTotal' value=$quickConfig+$noCalcValueDisplay}
<div id="pricesetTotal" class="crm-section section-pricesetTotal">
  {if !$hideTotal}
  <div class="label" id="pricelabel">
    <label>
      {if ( $extends eq 'Contribution' ) || ( $extends eq 'Membership' )}
        {ts}Total Amount{/ts}{else}{ts}Total Fee(s){/ts}
         {if $isAdditionalParticipants} {ts}for this participant{/ts}{/if}
      {/if}
    </label>
  </div>
  {/if}
  <div class="content calc-value" {if $hideTotal}style="display:none;"{/if} id="pricevalue" ></div>
</div>

<script type="text/javascript">
{literal}

var thousandMarker = '{/literal}{$config->monetaryThousandSeparator}{literal}';
var separator      = '{/literal}{$config->monetaryDecimalPoint}{literal}';
var symbol         = '{/literal}{$currencySymbol}{literal}';
var optionSep      = '|';

cj("#priceset [price]").each(function () {

    var elementType =  cj(this).attr('type');
    if ( this.tagName == 'SELECT' ) {
      elementType = 'select-one';
    }

    switch(elementType) {
      case 'checkbox':
        //event driven calculation of element.
        cj(this).click(function(){
          calculateCheckboxLineItemValue(this);
          display(calculateTotalFee());
        });
        calculateCheckboxLineItemValue(this);
      break;

    case 'radio':
      //event driven calculation of element.
      cj(this).click( function(){
        calculateRadioLineItemValue(this);
        display(calculateTotalFee());
      });
      calculateRadioLineItemValue(this);
      break;

  case 'text':

    //event driven calculation of element.
    cj(this).bind( 'keyup', function() {
      calculateText(this);
    }).bind( 'blur' , function() {
      calculateText(this);
    });
    //default calculation of element.
    calculateText(this);

    break;

  case 'select-one':
    calculateSelectLineItemValue(this);

    //event driven calculation of element.
    cj(this).change( function() {
      calculateSelectLineItemValue(this);
      display(calculateTotalFee());
    });


    break;

  }
  display(calculateTotalFee());
});

/**
 * Calculate the value of the line item for a radio value.
 */
function calculateCheckboxLineItemValue(priceElement) {
  eval( 'var option = ' + cj(priceElement).attr('price') ) ;
  optionPart = option[1].split(optionSep);
  price = parseFloat(0);
  if (cj(priceElement).prop('checked')) {
    price = parseFloat(optionPart[0]);
  }
  cj(priceElement).data('line_raw_total', price);
}

/**
 * Calculate the value of the line item for a radio value.
 */
function calculateRadioLineItemValue(priceElement) {
  eval( 'var option = ' + cj(priceElement).attr('price') );
  optionPart = option[1].split(optionSep);
  var lineTotal = parseFloat(optionPart[0]);
  cj(priceElement).data('line_raw_total', lineTotal);
  var radionGroupName = cj(priceElement).attr("name");
  // Reset all unchecked options to having a data value of 0.
  cj('input[name=' + radionGroupName + ']:radio:unchecked').each(
    function () {
      cj(this).data('line_raw_total', 0);
    }
  );
}

/**
 * Calculate the value of the line item for a select value.
 */
function calculateSelectLineItemValue(priceElement) {
  eval( 'var selectedText = ' + cj(priceElement).attr('price') );
  var price = parseFloat('0');
  var option = cj(priceElement).val();
  if (option) {
    optionPart = selectedText[option].split(optionSep);
    price   = parseFloat(optionPart[0]);
  }
  cj(priceElement).data('line_raw_total', price);
}

/**
 * Calculate the value of the line item for a text box.
 */
function calculateText(priceElement) {
  //CRM-16034 - comma acts as decimal in price set text pricing
  var textval = parseFloat(cj(priceElement).val().replace(thousandMarker, ''));

  if (isNaN(textval)) {
    textval = parseFloat(0);
  }
  eval('var option = '+ cj(priceElement).attr('price'));
  optionPart = option[1].split(optionSep);
  addprice = parseFloat(optionPart[0]);
  var curval  = textval * addprice;
  cj(priceElement).data('line_raw_total', curval);
  display(calculateTotalFee());
}

/**
 * Calculate the total fee for the visible priceset.
 */
function calculateTotalFee() {
  var totalFee = 0;
  cj("#priceset [price]").each(function () {
    totalFee = totalFee + cj(this).data('line_raw_total');
  });
  return totalFee;
}

/**
 * Display calculated amount.
 */
function display(totalfee) {

    // totalfee is monetary, round it to 2 decimal points so it can
    // go as a float - CRM-13491
    totalfee = Math.round(totalfee*100)/100;
    var totalEventFee  = formatMoney( totalfee, 2, separator, thousandMarker);
    document.getElementById('pricevalue').innerHTML = "<b>"+symbol+"</b> "+totalEventFee;

    cj('#total_amount').val( totalfee );
    cj('#pricevalue').data('raw-total', totalfee).trigger('change');

    ( totalfee < 0 ) ? cj('table#pricelabel').addClass('disabled') : cj('table#pricelabel').removeClass('disabled');
    skipPaymentMethod();
}

//money formatting/localization
function formatMoney (amount, c, d, t) {
var n = amount,
    c = isNaN(c = Math.abs(c)) ? 2 : c,
    d = d == undefined ? "," : d,
    t = t == undefined ? "." : t, s = n < 0 ? "-" : "",
    i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "",
    j = (j = i.length) > 3 ? j % 3 : 0;
  return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
}

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
  }
}

/**
 * Hides or shows billing and payment options block depending on whether payment is required.
 *
 * In general incomplete orders or $0 orders do not require a payment block.
 */
function skipPaymentMethod() {
  var isHide = false;
  var isMultiple = '{/literal}{$event.is_multiple_registrations}{literal}';
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

{/literal}
</script>
