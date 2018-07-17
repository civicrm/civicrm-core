// http://civicrm.org/licensing
(function($, CRM, _) {
  'use strict';

  /* jshint validthis: true */
  /**
   * Handle user input - field or operator selection.
   *
   * Decide whether to display select drop down, regular text or date
   * field for the given field and row.
   */
  function handleUserInputField() {
    var row = $(this).closest('tr');
    var field = $('select[id^=mapper][id$="_1"]', row).val();
    field = (field === 'world_region') ? 'worldregion_id': field;
    var operator = $('select[id^=operator]', row);
    var op = operator.val();

    var patt = /_1$/; // pattern to check if the change event came from field name
    if (field !== null && patt.test(this.id)) {
      // based on data type remove invalid operators e.g. IS EMPTY doesn't work with Boolean type column
      var operators = CRM.searchBuilder.generalOperators;
      if ((field in CRM.searchBuilder.fieldTypes) === true) {
        if ($.inArray(CRM.searchBuilder.fieldTypes[field], ['Boolean', 'Int']) > -1) {
          operators = _.omit(operators, ['IS NOT EMPTY', 'IS EMPTY']);
        }
        else if (CRM.searchBuilder.fieldTypes[field] == 'String') {
          operators = _.omit(operators, ['>', '<', '>=', '<=']);
        }
      }
      buildOperator(operator, operators);
    }

    // These Ops don't get any input field.
    var noFieldOps = ['', 'IS EMPTY', 'IS NOT EMPTY', 'IS NULL', 'IS NOT NULL'];

    if ($.inArray(op, noFieldOps) > -1) {
      // Hide the fields and return.
      $('.crm-search-value', row).hide().find('input, select').val('');
      return;
    }
    $('.crm-search-value', row).show();

    if (!CRM.searchBuilder.fieldOptions[field]) {
      removeSelect(row);
    }
    else {
      buildSelect(row, field, op, false);
    }

    if ((field in CRM.searchBuilder.fieldTypes) === true &&
      CRM.searchBuilder.fieldTypes[field] == 'Date'
    ) {
      buildDate(row, op);
    }
    else {
      removeDate(row);
    }
  }

  /**
   * Add appropriate operator to selected field
   * @param operator: jQuery object
   * @param options: array
   */
  function buildOperator(operator, options) {
    var selected = operator.val();
    operator.html('');
    $.each(options, function(value, label) {
      operator.append('<option value="' + value + '">' + label + '</option>');
    });
    operator.val(selected);
  }

  /**
   * Add select list if appropriate for this operation
   * @param row: jQuery object
   * @param field: string
   * @param skip_fetch: boolean
   */
  function buildSelect(row, field, op, skip_fetch) {
    var multiSelect = '';
    // Operators that will get a single drop down list of choices.
    var dropDownSingleOps = ['=', '!='];
    // Multiple select drop down list.
    var dropDownMultipleOps = ['IN', 'NOT IN'];

    if ($.inArray(op, dropDownMultipleOps) > -1) {
      multiSelect = 'multiple="multiple"';
    }
    else if ($.inArray(op, dropDownSingleOps) < 0) {
      // If this op is neither supported by single or multiple selects, then we should not render a select list.
      removeSelect(row);
      return;
    }

    $('.crm-search-value select', row).remove();
    $('input[id^=value]', row)
      .hide()
      .after('<select class="crm-form-' + multiSelect.substr(0, 5) + 'select required" ' + multiSelect + '><option value="">' + ts('Loading') + '...</option></select>');

    // Avoid reloading state/county options IF already built, identified by skip_fetch
    if (skip_fetch) {
      buildOptions(row, field);
    }
    else {
      fetchOptions(row, field);
    }
  }

  /**
   * Retrieve option list for given row
   * @param row: jQuery object
   * @param field: string
   */
  function fetchOptions(row, field) {
    if (CRM.searchBuilder.fieldOptions[field] === 'yesno') {
      CRM.searchBuilder.fieldOptions[field] = [{key: 1, value: ts('Yes')}, {key: 0, value: ts('No')}];
    }
    if (typeof(CRM.searchBuilder.fieldOptions[field]) == 'string') {
      CRM.api(CRM.searchBuilder.fieldOptions[field], 'getoptions', {field: field, sequential: 1}, {
        success: function(result, settings) {
          var field = settings.field;
          if (result.count) {
            CRM.searchBuilder.fieldOptions[field] = result.values;
            buildOptions(settings.row, field);
          }
          else {
            removeSelect(settings.row);
          }
        },
        error: function(result, settings) {
          removeSelect(settings.row);
        },
        row: row,
        field: field
      });
    }
    else {
      buildOptions(row, field);
    }
  }

  /**
   * Populate option list for given row
   * @param row: jQuery object
   * @param field: string
   */
  function buildOptions(row, field) {
    var select = $('.crm-search-value select', row);
    var value = $('input[id^=value]', row).val();
    if (value.length && value.charAt(0) == '(' && value.charAt(value.length - 1) == ')') {
      value = value.slice(1, -1);
    }
    var options = value.split(',');
    if (select.attr('multiple') == 'multiple') {
      select.find('option').remove();
    }
    else {
      select.find('option').text(ts('- select -'));
      if (options.length > 1) {
        options = [options[0]];
      }
    }
    $.each(CRM.searchBuilder.fieldOptions[field], function(key, option) {
      var optionKey = option.key;
      if ($.inArray(field, CRM.searchBuilder.searchByLabelFields) >= 0) {
        optionKey = option.value;
      }
      var selected = ($.inArray(''+optionKey, options) > -1) ? 'selected="selected"' : '';
      select.append('<option value="' + optionKey + '"' + selected + '>' + option.value + '</option>');
    });
    select.change();
  }

  /**
   * Remove select options and restore input to a plain textfield
   * @param row: jQuery object
   */
  function removeSelect(row) {
    $('.crm-search-value input', row).show();
    $('.crm-search-value select', row).remove();
  }

  /**
   * Add a datepicker if appropriate for this operation
   * @param row: jQuery object
   */
  function buildDate(row, op) {
    var input = $('.crm-search-value input', row);
    // These are operations that should not get a datepicker
    var datePickerOp = ($.inArray(op, ['IN', 'NOT IN', 'LIKE', 'RLIKE']) < 0);
    if (!datePickerOp) {
      removeDate(row);
    }
    else if (!input.hasClass('hasDatepicker')) {
      input.addClass('dateplugin').datepicker({
        dateFormat: 'yymmdd',
        changeMonth: true,
        changeYear: true,
        yearRange: '-100:+20'
      });
    }
  }

  /**
   * Remove datepicker
   * @param row: jQuery object
   */
  function removeDate(row) {
    var input = $('.crm-search-value input', row);
    if (input.hasClass('hasDatepicker')) {
      input.removeClass('dateplugin').val('').datepicker('destroy');
    }
  }

  /**
   * Load and build select options for state IF country is chosen OR county options if state is chosen
   * @param mapper: string
   * @param value: integer
   * @param location_type: integer
   * @param section: section in which the country/state selection change occurred
   */
  function chainSelect(mapper, value, location_type, section) {
    var apiParams = {
      sequential: 1,
      field: (mapper == 'country_id') ?  'state_province' : 'county',
    };
    apiParams[mapper] = value;
    var fieldName = apiParams.field;
    CRM.api3('address', 'getoptions', apiParams, {
      success: function(result) {
        if (result.count) {
          CRM.searchBuilder.fieldOptions[fieldName] = result.values;
          $('select[id^=mapper_' + section + '][id$="_1"]').each(function() {
            var row = $(this).closest('tr');
            var op = $('select[id^=operator]', row).val();
            if ($(this).val() === fieldName && location_type === $('select[id^=mapper][id$="_2"]', row).val()) {
              buildSelect(row, fieldName, op, true);
            }
          });
        }
      }
    });
  }

  // Initialize display: Hide empty blocks & fields
  var newBlock = CRM.searchBuilder && CRM.searchBuilder.newBlock || 0;
  function initialize() {
    $('.crm-search-block', '#Builder').each(function(blockNo) {
      var block = $(this);
      var empty = blockNo + 1 > newBlock;
      var skippedRow = false;
      $('tr:not(.crm-search-builder-add-row)', block).each(function(rowNo) {
        var row = $(this);
        if ($('select:first', row).val() === '') {
          if (!skippedRow && (rowNo === 0 || blockNo + 1 == newBlock)) {
            skippedRow = true;
          }
          else {
            row.hide();
          }
        }
        else {
          empty = false;
        }
      });
      if (empty) {
        block.hide();
      }
    });
  }

  $(function($) {
    $('#crm-main-content-wrapper')
      // Reset and hide row
      .on('click', '.crm-reset-builder-row', function() {
        var row = $(this).closest('tr');
        $('input, select', row).val('').change();
        row.hide();
        // Hide entire block if this is the only visible row
        if (row.siblings(':visible').length < 2) {
          row.closest('.crm-search-block').hide();
        }
        return false;
      })
      // Add new field - if there's a hidden one, show it
      // Otherwise allow form to submit and fetch more from the server
      .on('click', 'input[name^=addMore]', function() {
        var table = $(this).closest('table');
        if ($('tr:hidden', table).length) {
          $('tr:hidden', table).first().show();
          return false;
        }
      })
      // Add new block - if there's a hidden one, show it
      // Otherwise allow form to submit and fetch more from the server
      .on('click', '#addBlock', function() {
        if ($('.crm-search-block:hidden', '#Builder').length) {
          var block = $('.crm-search-block:hidden', '#Builder').first();
          block.show();
          $('tr:first-child, tr.crm-search-builder-add-row', block).show();
          return false;
        }
      })
      // Handle field and operator selection
      .on('change', 'select[id^=mapper][id$="_1"], select[id^=operator]', handleUserInputField)
      // Handle option selection - update hidden value field
      .on('change', '.crm-search-value select', function() {
        var value = $(this).val() || '';
        if ($(this).attr('multiple') == 'multiple' && value.length) {
          value = value.join(',');
        }
        $(this).siblings('input').val(value);
        if (value !== '') {
          var mapper = $('#' + $(this).siblings('input').attr('id').replace('value_', 'mapper_') + '_1').val();
          var location_type = $('#' + $(this).siblings('input').attr('id').replace('value_', 'mapper_') + '_2').val();
          var section = $(this).siblings('input').attr('id').replace('value_', '').split('_')[0];
          if ($.inArray(mapper, ['state_province', 'country']) > -1) {
            chainSelect(mapper + '_id', value, location_type, section);
          }
        }
      })
      .on('crmLoad', function() {
        initialize();
        $('select[id^=mapper][id$="_1"]', '#Builder').each(handleUserInputField);
      });

    initialize();

    // Fetch initial options during page refresh - it's more efficient to bundle them in a single ajax request
    var initialFields = {}, fetchFields = false;
    $('select[id^=mapper][id$="_1"] option:selected', '#Builder').each(function() {
      var field = $(this).attr('value');
      if (typeof(CRM.searchBuilder.fieldOptions[field]) == 'string' && CRM.searchBuilder.fieldOptions[field] !== 'yesno') {
        initialFields[field] = [CRM.searchBuilder.fieldOptions[field], 'getoptions', {field: field, sequential: 1}];
        fetchFields = true;
      }
    });
    if (fetchFields) {
      CRM.api3(initialFields).done(function(data) {
        $.each(data, function(field, result) {
          CRM.searchBuilder.fieldOptions[field] = result.values;
        });
        $('select[id^=mapper][id$="_1"]', '#Builder').each(handleUserInputField);
      });
    } else {
      $('select[id^=mapper][id$="_1"]', '#Builder').each(handleUserInputField);
    }
  });
})(cj, CRM, CRM._);
