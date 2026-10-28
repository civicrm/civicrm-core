//@todo functions partially moved from tpl but still need an enclosure / cleanup
/* global cj */
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

  $('input[id*="primary_contact_"]').change(function() {
    var temp = this.id.split('_');
    var ROWID = temp[3];
    if ($(this).val()) {
      updateContactInfo(ROWID, 'primary_');
    }
  });

  $('select[id^="option_type_"]').each(function () {
    if ($(this).val() == 1) {
      $(this).attr('disabled', true);
      $(this).hide();
    }
  });

  $('#crm-container').on('keyup change', '*.selector-rows', function () {
    // validate rows
    checkColumns($(this));
  });

  $('.pledge-adjust-option').click(function(){
    var blockNo = $(this).attr('id');
    $('select[id="option_type_' + blockNo + '"]').show();
    $('select[id="option_type_' + blockNo + '"]').removeAttr('disabled');
    $('#field_' + blockNo + '_total_amount').removeAttr('readonly');
  });
  $('input[name^="soft_credit_contact_"]').on('change', function(){
    var rowNum = $(this).attr('id').replace('soft_credit_contact_id_','');
    var totalAmount = $('#field_'+rowNum+'_total_amount').val();
    //assign total amount as default soft credit amount
    $('#soft_credit_amount_'+ rowNum).val(totalAmount);
    //assign soft credit type default value if one has not already been selected.
    if (!$('#soft_credit_type_'+ rowNum).val()) {
      $('#soft_credit_type_'+ rowNum).val($('#sct_default_id').val());
    }
  });

  // Could be replaced if there ever is a PCP API.
  // See templates/CRM/Contribute/Form/PCP.js.tpl
  var pcpURL = CRM.url('civicrm/ajax/pcp/list', 'json=1&context=contact&reset=1');
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

  function showHidePCPRoll() {
    $(this).parents('.crm-grid-cell').children('.pcp_roll_display').toggle($(this).prop('checked'));
  }

  $('input[name^="pcp_display_in_roll"]').each(showHidePCPRoll).change(showHidePCPRoll);

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
  else if (CRM.batch.type_id == 2) {
    $('select[id^="member_option_"]').each(function () {
      if ($(this).val() == 1) {
        $(this).attr('disabled', true);
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
  else if (CRM.batch.type_id == 3) {
    $('select[id*="open_pledges_"]').change(function () {
      setPledgeAmount($(this), $(this).val());
    });
  }

  // line breaks between radio buttons and checkboxes
  $('input.form-radio').next().after('<br />');
  $('input.form-checkbox').next().after('<br />');

  //set the focus on first element
  $('#primary_contact_1').focus();
});

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
    // datepicker hasTimeEntry would not have an id - not sure why.
    if (typeof fieldId !== 'undefined' && fieldId.substring(0, 16) == 'primary_contact_') {
      // if display value is set then make sure we also check if contact id is set
      if (!cj(this).val()) {
        inValidRow++;
      }
      else {
        var contactIdElement = cj('input[name="primary_contact_select_id[' + rowID + ']"]');
        if (cj(this).val() && !contactIdElement.val()) {
          inValidRow++;
          errorExists = true;
        }
        else if (cj(this).val() && contactIdElement.val()) {
          // this is hack to remove error span because we are skipping this for autocomplete fields
          cj(this).next('span.crm-error').remove();
        }
      }
    }
    else {
      if (!cj(this).val()) {
        inValidRow++;
      }
      else {
        if (cj(this).hasClass('error') && (cj(this).hasClass('valid') || cj(this).hasClass('required'))) {
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

  cj('.batch-actual-total').text(CRM.formatMoney(total, true));
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

  cj('[id^=field_' + blockNo + '_' + fname + '_display]').val(displayDateValue);
}

function updateContactInfo(blockNo, prefix) {
  var contactHiddenElement = 'input[id="' + prefix + 'contact_id_' + blockNo + '"]';
  var contactId = cj(contactHiddenElement).val();

  var profileFields = (CRM.contact && CRM.contact.fieldmap) ? CRM.contact.fieldmap : {};
  var returnProperties = (CRM.contact && CRM.contact.return) ? CRM.contact.return : '';

  CRM.api('Contact', 'get', {
    'sequential': '1',
    'contact_id': contactId,
    'return': returnProperties
  }, {
    success: function (data) {
      if (data.values && data.values[0]) {
        cj.each(data.values[0], function (key, value) {
          // set the values
          var actualFldName = profileFields[key];
          if (key == 'country' || key == 'state_province') {
            var idFldName = key + '_id';
            value = data.values[0][idFldName];
          }
          setFieldValue(actualFldName, value, blockNo);
        });
      }

      // for membership batch entry based on contact we need to enable / disable
      // add membership select
      if (CRM.batch.type_id == 2) {
        CRM.api('Membership', 'get', {
          'sequential': '1',
          'contact_id': contactId
        }, {
          success: function (data) {
            if (data.count > 0) {
              //get the information on membership type
              var membershipTypeId = data.values[0].membership_type_id;
              var membershipJoinDate = data.values[0].join_date;
              var membershipStartDate = data.values[0].start_date;
              CRM.api('MembershipType', 'get', {
                'sequential': '1',
                'id': membershipTypeId
              }, {
                success: function (data) {
                  var memTypeContactId = data.values[0].member_of_contact_id;
                  cj('select[id="member_option_' + blockNo + '"]').prop('disabled', false).val(2);
                  cj('select[id="field_' + blockNo + '_membership_type_0"]').val(memTypeContactId).change();
                  cj('select[id="field_' + blockNo + '_membership_type_1"]').val(membershipTypeId).change();
                  cj('#field_' + blockNo + '_' + 'membership_join_date').val(membershipJoinDate).trigger('change');
                  cj('#field_' + blockNo + '_' + 'membership_start_date').val(membershipStartDate).trigger('change');
                }
              });
            }
          }
        });
      }
      else if (CRM.batch.type_id == 3) {
        cj('#open_pledges_' + blockNo).empty();
        cj('#open_pledges_' + blockNo).append(cj('<option>', {
          value: '',
          text: '-select-'
        }));
        CRM.api('Pledge', 'get', {
          'sequential': 1,
          'contact_id': contactId || 0
        }, {
          success: function (data) {
            cj.each(data.values, function (key, value) {
              if (value.pledge_status != 'Completed') {
                var date = cj.datepicker.parseDate('yy-mm-dd', value.pledge_next_pay_date);
                var dateformat = CRM.config.dateInputFormat;
                cj('#open_pledges_' + blockNo).append(cj('<option>', {
                  value: value.pledge_id,
                  text: cj.datepicker.formatDate(dateformat, date) + ", " + value.pledge_next_pay_amount + ' ' + value.pledge_currency
                }));
              }
            });
          }
        });
      }
    }
  });
}

function setPledgeAmount(form, pledgeID) {
  var rowID = form.closest('div.crm-grid-row').attr('entity_id');
  var dataUrl = CRM.url('civicrm/ajax/pledgeAmount');
  if (pledgeID) {
    cj.post(dataUrl, {pid: pledgeID}, function (data) {
      cj('#field_' + rowID + '_financial_type').val(data.financial_type_id).change();
      cj('#field_' + rowID + '_total_amount').val(data.amount).change();
      cj('#field_' + rowID + '_total_amount').attr('readonly', true);
    }, 'json');
  }
  else {
    cj('#field_' + rowID + '_total_amount').val('').change();
    cj('#field_' + rowID + '_financial_type').val('').change();
    cj('#field_' + rowID + '_total_amount').removeAttr('readonly');
  }
}

