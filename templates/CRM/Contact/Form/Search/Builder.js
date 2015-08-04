// http://civicrm.org/licensing
(function($, CRM) {
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
    var op = $('select[id^=operator]', row).val();

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
      buildSelect(row, field, op);
    }

    if ($.inArray(field, CRM.searchBuilder.dateFields) < 0) {
      removeDate(row);
    }
    else {
      buildDate(row, op);
    }
  }

  /**
   * Add select list if appropriate for this operation
   * @param row: jQuery object
   * @param field: string
   */
  function buildSelect(row, field, op) {
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

    fetchOptions(row, field);
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
      var selected = ($.inArray(''+option.key, options) > -1) ? 'selected="selected"' : '';
      select.append('<option value="' + option.key + '"' + selected + '>' + option.value + '</option>');
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
      if (typeof(CRM.searchBuilder.fieldOptions[field]) == 'string') {
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
})(cj, CRM);
