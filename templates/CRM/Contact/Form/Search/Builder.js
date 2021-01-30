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
    var row = $(this).closest('tr'),
      entity = $('select[id^=mapper][id$="_0"]', row).val(),
      field = $('select[id^=mapper][id$="_1"]', row).val();
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

    removeDate(row);

    // These Ops don't get any input field.
    var noFieldOps = ['', 'IS EMPTY', 'IS NOT EMPTY', 'IS NULL', 'IS NOT NULL'];

    if ($.inArray(op, noFieldOps) > -1) {
      // Hide the fields and return.
      $('.crm-search-value', row).hide().find('input[id^=value]').val('');
      return;
    }
    $('.crm-search-value', row).show();

    if (CRM.searchBuilder.fieldOptions[field]) {
      buildSelect(row, field, op, false);
    }
    // Add entityRef widget for all fields except an entity's own id
    else if (CRM.searchBuilder.fkEntities[field] && field !== (entity.toLowerCase() + '_id')) {
      buildEntityRef(row, field, op);
    }
    else {
      removeSelect(row);
    }

    if (CRM.searchBuilder.fieldTypes[field] === 'Date' || CRM.searchBuilder.fieldTypes[field] === 'Timestamp') {
      buildDate(row, op, CRM.searchBuilder.fieldTypes[field] === 'Timestamp');
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

  function getSelectType(op) {
    // Operators that will get a single drop down list of choices.
    var dropDownSingleOps = ['=', '!='];
    // Multiple select drop down list.
    var dropDownMultipleOps = ['IN', 'NOT IN'];

    if ($.inArray(op, dropDownMultipleOps) > -1) {
      return true;
    }
    if ($.inArray(op, dropDownSingleOps) > -1) {
      return false;
    }
  }

  function buildEntityRef(row, field, op) {
    var selectType = getSelectType(op);

    if (typeof selectType === 'undefined') {
      removeSelect(row);
      return;
    }

    $('input[id^=value]', row)
      .crmEntityRef({
        entity: CRM.searchBuilder.fkEntities[field],
        select: {multiple: selectType, placeholder: ts('Select'), allowClear: true}
      });
  }

  /**
   * Add select list if appropriate for this operation
   * @param row: jQuery object
   * @param field: string
   * @param skip_fetch: boolean
   */
  function buildSelect(row, field, op, skip_fetch) {
    var selectType = getSelectType(op);

    if (typeof selectType === 'undefined') {
      removeSelect(row);
      return;
    }

    $('input[id^=value]', row)
      .addClass('loading')
      .crmSelect2({data: [], disabled: true, multiple: selectType, placeholder: ts('Select'), allowClear: true});

    // Avoid reloading state/county options IF already built, identified by skip_fetch
    if (skip_fetch) {
      buildOptions(row, field, selectType);
    }
    else {
      fetchOptions(row, field, selectType);
    }
  }

  /**
   * Retrieve option list for given row
   * @param row: jQuery object
   * @param field: string
   */
  function fetchOptions(row, field, multiSelect) {
    if (CRM.searchBuilder.fieldOptions[field] === 'yesno') {
      CRM.searchBuilder.fieldOptions[field] = [{key: 1, value: ts('Yes')}, {key: 0, value: ts('No')}];
    }
    if (typeof(CRM.searchBuilder.fieldOptions[field]) == 'string') {
      CRM.api(CRM.searchBuilder.fieldOptions[field], 'getoptions', {field: field, sequential: 1}, {
        success: function(result, settings) {
          var field = settings.field;
          if (result.count) {
            CRM.searchBuilder.fieldOptions[field] = result.values;
            buildOptions(settings.row, field, multiSelect);
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
      buildOptions(row, field, multiSelect);
    }
  }

  /**
   * Populate option list for given row
   * @param row: jQuery object
   * @param field: string
   * @param multiSelect: bool
   */
  function buildOptions(row, field, multiSelect) {
    var $el = $('input[id^=value]', row).removeClass('loading'),
      value = $el.val();
    if (value.length && value.charAt(0) === '(' && value.charAt(value.length - 1) === ')') {
      $el.val(value.slice(1, -1));
    }
    $el.crmSelect2({
      multiple: multiSelect,
      placeholder: ts('Select'),
      allowClear: true,
      data: _.transform(CRM.searchBuilder.fieldOptions[field], function(options, opt) {
        options.push({id: opt.key, text: opt.value});
      }, [])
    });
  }

  /**
   * Remove select options and restore input to a plain textfield
   * @param row: jQuery object
   */
  function removeSelect(row) {
    $('input[id^=value]', row).crmEntityRef('destroy');
  }

  /**
   * Add a datepicker if appropriate for this operation
   * @param row: jQuery object
   */
  function buildDate(row, op, time) {
    var input = $('.crm-search-value input', row);
    // These are operations that should not get a datepicker
    var datePickerOp = ($.inArray(op, ['IN', 'NOT IN', 'LIKE', 'RLIKE']) < 0);
    if (!datePickerOp) {
      removeDate(row);
    }
    else if (!$('input.crm-hidden-date', row).length) {
      // Unfortunately the search builder form expects yyyymmdd and crmDatepicker gives yyyy-mm-dd so we have to fudge it
      var val = input.val();
      if (val && val.length === 8) {
        input.val(val.substr(0, 4) + '-' + val.substr(4, 2) + '-' + val.substr(6, 2));
      } else if (val && val.length === 14) {
        input.val(val.substr(0, 4) + '-' + val.substr(4, 2) + '-' + val.substr(6, 2) + ' ' + val.substr(8, 2) + ':' + val.substr(10, 2) + ':' + val.substr(12, 2));
      }
      input
        .on('change.searchBuilder', function() {
          if ($(this).val()) {
            $(this).val($(this).val().replace(/[: -]/g, ''));
          }
        })
        .crmDatepicker({
          time: time,
          yearRange: '-100:+20'
        })
        .triggerHandler('change', ['userInput']);
    }
  }

  /**
   * Remove datepicker
   * @param row: jQuery object
   */
  function removeDate(row) {
    $('.crm-search-value input.crm-hidden-date', row).off('.searchBuilder').crmDatepicker('destroy');
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
      .on('click', 'button[name^=addMore]', function() {
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
      .on('change', '.crm-search-value input[id^=value]', function() {
        var value = $(this).val() || '';
        if (value !== '') {
          var mapper = $('#' + $(this).attr('id').replace('value_', 'mapper_') + '_1').val();
          var location_type = $('#' + $(this).attr('id').replace('value_', 'mapper_') + '_2').val();
          var section = $(this).attr('id').replace('value_', '').split('_')[0];
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
