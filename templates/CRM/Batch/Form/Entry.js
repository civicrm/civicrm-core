//@todo functions partially moved from tpl but still need an enclosure / cleanup
// jslinting etc
CRM.$(function($) {
  $('.selector-rows').change(function () {
    var options = {
      'url': CRM.url('civicrm/ajax/batch')
    };

    $("#Entry").ajaxSubmit(options);

    //avoid multiple click on submit button
    $("#_qf_Entry_upload").on('click', function() {
      $('#Entry').block();
    });

    // validate rows
    checkColumns($(this));
  });
   cj('.pledge-adjust-option').click(function(){
	var blockNo = cj(this).attr('id');
	cj('select[id="option_type_' + blockNo + '"]').show();
	cj('select[id="option_type_' + blockNo + '"]').removeAttr('disabled');
	cj('#field_' + blockNo + '_total_amount').removeAttr('readonly');
    });
  $('input[name^="soft_credit_contact_"]').on('change', function(){
    var rowNum = $(this).attr('id').replace('soft_credit_contact_id_','');
    var totalAmount = $('#field_'+rowNum+'_total_amount').val();
    //assign total amount as default soft credit amount
    $('#soft_credit_amount_'+ rowNum).val(totalAmount);
    //assign soft credit type default value if any
    $('#soft_credit_type_'+ rowNum).val($('#sct_default_id').val());
  });

  // Could be replaced if there ever is a PCP API.
  // See templates/CRM/Contribute/Form/PCP.js.tpl
  var pcpURL = CRM.url('civicrm/ajax/rest', 'className=CRM_Contact_Page_AJAX&fnName=getPCPList&json=1&context=contact&reset=1');
  $('input[name^="pcp_made_through_id"]').each(function() {
    // Figure out the name of the corresponding pcp_made_through[X] field
    var thisMadeThroughName = $(this).attr('name').replace('through_id', 'through');
    $(this).crmSelect2({
      minimumInputLength: 1,
      ajax: {
        url: pcpURL,
        data: function(term, page) {
          return {term: term, page_num: page};
        },
        results: function(response) {
          return response;
        }
      },
      initSelection: function(el, callback) {
        callback({id: $(el).val(), text: $('[name="'+thisMadeThroughName+'"]').val()});
      }
    })
    // This is just a cheap trick to store the name when the form reloads
    .on('change', function() {
      var fieldNameVal = $(this).select2('data');
      if (!fieldNameVal) {
        fieldNameVal = '';
      }
      $('[name="'+thisMadeThroughName+'"]').val(fieldNameVal.text);
    });
  });

  $('input[name^="pcp_display_in_roll"]').each(function() {
    showHidePCPRoll(this);
    $(this).change(function() {
      showHidePCPRoll(this);
    });
  });


  // validate rows
  validateRow();

  //calculate the actual total for the batch
  calculateActualTotal();

  $('input[id*="_total_amount"]').bind('keyup change', function () {
    calculateActualTotal();
  });

  if (CRM.batch.type_id == 1) {
    // hide all dates if send receipt is checked
    hideSendReceipt();

    // hide the receipt date if send receipt is checked
    $('input[id*="][send_receipt]"]').change(function () {
      showHideReceipt($(this));
    });

  }
  else if (CRM.batch.type_id == 2){
	cj('select[id^="member_option_"]').each(function () {
	    if (cj(this).val() == 1) {
		cj(this).attr('disabled', true);
	    }
	});

  // set payment info accord to membership type
  $('select[id*="_membership_type_0"]').change(function () {
    setPaymentBlock($(this), null);
  });
  $('select[id*="_membership_type_1"]').change(function () {
    setPaymentBlock($(this), $(this).val());
  });

  }

  // line breaks between radio buttons and checkboxes
  $('input.form-radio').next().after('<br />');
  $('input.form-checkbox').next().after('<br />');

  //set the focus on first element
  $('#primary_contact_1').focus();
});

function showHidePCPRoll(elem) {
  CRM.$(function($) {
    if ($(elem).prop('checked')) {
      $(elem).parents('.crm-grid-cell').children('.pcp_roll_display').show();
    }
    else {
      $(elem).parents('.crm-grid-cell').children('.pcp_roll_display').hide();
    }
  });
}

function setPaymentBlock(form, memType) {
  var rowID = form.closest('div.crm-grid-row').attr('entity_id');
  var dataUrl = CRM.url('civicrm/ajax/memType');

  if (!memType) {
    memType = cj('select[id="field_' + rowID + '_membership_type_1"]').val();
  }

  cj.post(dataUrl, {mtype: memType}, function (data) {
    cj('#field_' + rowID + '_financial_type').val(data.financial_type_id);
    cj('#field_' + rowID + '_total_amount').val(data.total_amount).change();
  }, 'json');
}

function hideSendReceipt() {
  cj('input[id*="][send_receipt]"]').each(function () {
    showHideReceipt(cj(this));
  });
}

function showHideReceipt(elem) {
  var rowID = elem.closest('div.crm-grid-row').attr('entity_id');
  if (elem.prop('checked')) {
    cj('.crm-batch-receipt_date-' + rowID).hide();
  }
  else {
    cj('.crm-batch-receipt_date-' + rowID).show();
  }
}

function validateRow() {
  cj('.selector-rows').each(function () {
    checkColumns(cj(this));
  });
}

function checkColumns(parentRow) {
  // show valid row icon if all required data is field
  var validRow = 0;
  var inValidRow = 0;
  var errorExists = false;
  var rowID = parentRow.closest('div.crm-grid-row').attr('entity_id');

  parentRow.find('div .required').each(function () {
    //special case to handle contact autocomplete select
    var fieldId = cj(this).attr('id');
    if (fieldId.substring(0, 16) == 'primary_contact_') {
      // if display value is set then make sure we also check if contact id is set
      if (!cj(this).val()) {
        inValidRow++;
      }
      else {
        if (cj(this).val() && !cj('input[name="primary_contact_select_id[' + rowID + ']"]').val()) {
          inValidRow++;
          errorExists = true;
        }
      }
    }
    else {
      if (!cj(this).val()) {
        inValidRow++;
      }
      else {
        if (cj(this).hasClass('error') && !cj(this).hasClass('valid')) {
          errorExists = true;
        }
        else {
          validRow++;
        }
      }
    }
  });

  // this means user has entered some data
  if (errorExists) {
    parentRow.find("div:first span").prop('class', 'batch-invalid');
  }
  else {
    if (inValidRow === 0 && validRow > 0) {
      parentRow.find("div:first span").prop('class', 'batch-valid');
    }
    else {
      parentRow.find("div:first span").prop('class', 'batch-edit');
    }
  }
}

function calculateActualTotal() {
  var total = 0;
  cj('input[id*="_total_amount"]').each(function () {
    if (cj(this).val()) {
      total += parseFloat(cj(this).val());
    }
  });

  cj('.batch-actual-total').html(formatMoney(total));
}

//money formatting/localization
function formatMoney(amount) {
  var c = 2;
  var t = CRM.setting.monetaryThousandSeparator;
  var d = CRM.setting.monetaryDecimalPoint;

  c = isNaN(c = Math.abs(c)) ? 2 : c;
  t = t === undefined ? "." : t;
  d = d === undefined ? "," : d;

  var n = amount,
    i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "",
    s = n < 0 ? "-" : "",
    j = (j = i.length) > 3 ? j % 3 : 0;

  return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
}

/**
 * This function is use to setdefault elements via ajax
 *
 * @param fname string field name
 * @return void
 */
function setFieldValue(fname, fieldValue, blockNo) {
  var elementId = cj('[name="field[' + blockNo + '][' + fname + ']"]');

  if (elementId.length === 0) {
    elementId = cj('input[type=checkbox][name^="field[' + blockNo + '][' + fname + ']"][type!=hidden]');
  }

  // if element not found than return
  if (elementId.length === 0) {
    return;
  }

  //check if it is date element
  var isDateElement = elementId.attr('format');

  //get the element type
  var elementType = elementId.attr('type');

  // set the value for all the elements, elements needs to be handled are
  // select, checkbox, radio, date fields, text, textarea, multi-select
  // wysiwyg editor, advanced multi-select ( to do )
  if (elementType == 'radio') {
    if (fieldValue) {
      elementId.filter("[value=" + fieldValue + "]").prop("checked", true);
    }
    else {
      elementId.removeProp('checked');
    }
  }
  else {
    if (elementType == 'checkbox') {
      // handle checkbox
      elementId.removeProp('checked');
      if (fieldValue) {
        cj.each(fieldValue, function (key, value) {
          cj('input[name="field[' + blockNo + '][' + fname + '][' + value + ']"]').prop('checked', true);
        });
      }
    }
    else {
      if (elementId.is('textarea')) {
        CRM.wysiwyg.setVal(elementId, fieldValue);
      }
      else {
        elementId.val(fieldValue);
      }
    }
  }

  // since we use different display field for date we also need to set it.
  // also check for date time field and set the value correctly
  if (isDateElement && fieldValue) {
    setDateFieldValue(fname, fieldValue, blockNo);
  }
}

function setDateFieldValue(fname, fieldValue, blockNo) {
  var dateValues = fieldValue.split(' ');

  var actualDateElement = cj('#field_' + blockNo + '_' + fname);
  var date_format = actualDateElement.attr('format');
  var altDateFormat = 'yy-mm-dd';

  var actualDateValue = cj.datepicker.parseDate(altDateFormat, dateValues[0]);

  // format date according to display field
  var hiddenDateValue = cj.datepicker.formatDate('mm/dd/yy', actualDateValue);

  actualDateElement.val(hiddenDateValue);

  var displayDateValue = actualDateElement.val();
  if (date_format != 'mm/dd/yy') {
    displayDateValue = cj.datepicker.formatDate(date_format, actualDateValue);
  }

  cj('#field_' + blockNo + '_' + fname + '_display').val(displayDateValue);

  // need to fix time formatting
  if (dateValues[1]) {
    cj('#field_' + blockNo + '_' + fname + '_time').val(dateValues[1].substr(0, 5));
  }
}
