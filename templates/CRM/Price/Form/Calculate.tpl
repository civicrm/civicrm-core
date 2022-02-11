{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

{assign var='hideTotal' value=$quickConfig+$noCalcValueDisplay}

<div id="pricesetTotal" class="crm-section section-pricesetTotal">
  <div id="pricelabel" class="label {if $hideTotal}hiddenElement{/if}">
    {if ($extends eq 'Contribution') || ($extends eq 'Membership')}
      <span id='amount_sum_label'>{ts}Total Amount{/ts}</span>
    {else}
      {if $isAdditionalParticipants}
        <span id='amount_sum_label'>{ts}Total for this participant{/ts}</span>
      {else}
        <span id='amount_sum_label'>{ts}Total{/ts}</span>
      {/if}
    {/if}
  </div>
  <div class="content calc-value" {if $hideTotal}style="display:none;"{/if} id="pricevalue"></div>
</div>

<script type="text/javascript">
{literal}
(function($) {
  var thousandMarker = '{/literal}{$config->monetaryThousandSeparator}{literal}';
  var separator      = '{/literal}{$config->monetaryDecimalPoint}{literal}';
  var symbol         = '{/literal}{$currencySymbol}{literal}';
  // moneyFormat is part of a temporary fix. it should
  // not be expected to be present in future versions
  // see https://github.com/civicrm/civicrm-core/pull/19151

  var moneyFormat    = '{/literal}{$moneyFormat}{literal}';
  var optionSep      = '|';

  // Recalculate the total fees based on user selection
  $("#priceset [price]").each(function () {
    var elementType = $(this).attr('type');
    if (this.tagName == 'SELECT') {
      elementType = 'select-one';
    }

    switch(elementType) {
      case 'checkbox':
        $(this).click(function(){
          calculateCheckboxLineItemValue(this);
          display(calculateTotalFee());
        });
        calculateCheckboxLineItemValue(this);
        break;

      case 'radio':
        $(this).click( function(){
          calculateRadioLineItemValue(this);
          display(calculateTotalFee());
        });
        calculateRadioLineItemValue(this);
        break;

      case 'text':
        $(this).bind( 'keyup', function() {
          calculateText(this);
        }).bind( 'blur' , function() {
          calculateText(this);
        });
        //default calculation of element.
        calculateText(this);
        break;

      case 'select-one':
        calculateSelectLineItemValue(this);

        $(this).change(function() {
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
    eval( 'var option = ' + $(priceElement).attr('price') ) ;
    optionPart = option[1].split(optionSep);
    price = parseFloat(0);
    if ($(priceElement).prop('checked')) {
      price = parseFloat(optionPart[0]);
    }
    $(priceElement).data('line_raw_total', price);
  }

  /**
   * Calculate the value of the line item for a radio value.
   */
  function calculateRadioLineItemValue(priceElement) {
    eval( 'var option = ' + $(priceElement).attr('price') );
    optionPart = option[1].split(optionSep);
    var lineTotal = parseFloat(optionPart[0]);
    $(priceElement).data('line_raw_total', lineTotal);
    var radionGroupName = $(priceElement).attr("name");
    // Reset all unchecked options to having a data value of 0.
    $('input[name=' + radionGroupName + ']:radio:unchecked').each(
      function () {
        $(this).data('line_raw_total', 0);
      }
    );
  }

  /**
   * Calculate the value of the line item for a select value.
   */
  function calculateSelectLineItemValue(priceElement) {
    eval( 'var selectedText = ' + $(priceElement).attr('price') );
    var price = parseFloat('0');
    var option = $(priceElement).val();
    if (option) {
      optionPart = selectedText[option].split(optionSep);
      price   = parseFloat(optionPart[0]);
    }
    $(priceElement).data('line_raw_total', price);
  }

  /**
   * Calculate the value of the line item for a text box.
   */
  function calculateText(priceElement) {
    //CRM-16034 - comma acts as decimal in price set text pricing
    //CRM-19937 - dollar sign easy mistake to make by users.
    var textval = parseFloat($(priceElement).val().replace(thousandMarker, '').replace(symbol, ''));

    if (isNaN(textval)) {
      textval = parseFloat(0);
    }
    eval('var option = '+ $(priceElement).attr('price'));
    optionPart = option[1].split(optionSep);
    addprice = parseFloat(optionPart[0]);
    var curval  = textval * addprice;
    $(priceElement).data('line_raw_total', curval);
    display(calculateTotalFee());
  }

  /**
   * Calculate the total fee for the visible priceset.
   */
  function calculateTotalFee() {
    var totalFee = 0;
    $("#priceset [price]").each(function () {
      totalFee = totalFee + $(this).data('line_raw_total');
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
    // dev/core#1019 Use the moneyFormat assigned to the template as an interim fix
    // to support forms using a currency other that the site default. Also make sure to
    // support various currency formatting options,
    // temporary measure - pending
    // our preferred fix.
    // see https://github.com/civicrm/civicrm-core/pull/19151
    var totalFormattedFee = CRM.formatMoney(totalfee, false, moneyFormat);
    $('#pricevalue').html(totalFormattedFee);

    $('#total_amount').val( totalfee );
    $('#pricevalue').data('raw-total', totalfee).trigger('change');

    if (totalfee < 0) {
      $('table#pricelabel').addClass('disabled');
    }
    else {
      $('table#pricelabel').removeClass('disabled');
    }

    if (typeof skipPaymentMethod == 'function') {
      // Advice to anyone who, like me, feels hatred towards this if construct ... if you remove the if you
      // get an error on participant 2 of a event that requires approval & permits multiple registrants.
      skipPaymentMethod();
    }
  }
})(CRM.$);
{/literal}
</script>
