// https://civicrm.org/licensing
/* global CRM:true */
var CRM = CRM || {};
var cj = CRM.$ = jQuery;
CRM._ = _;

/**
 * Short-named function for string translation, defined in global scope so it's available everywhere.
 *
 * @param text string for translating
 * @param params object key:value of additional parameters
 *
 * @return string
 */
function ts(text, params) {
  "use strict";
  var d = (params && params.domain) ? ('strings::' + params.domain) : null;
  if (d && CRM[d] && CRM[d][text]) {
    text = CRM[d][text];
  }
  else if (CRM.strings[text]) {
    text = CRM.strings[text];
  }
  if (typeof(params) === 'object') {
    for (var i in params) {
      if (typeof(params[i]) === 'string' || typeof(params[i]) === 'number') {
        // sprintf emulation: escape % characters in the replacements to avoid conflicts
        text = text.replace(new RegExp('%' + i, 'g'), String(params[i]).replace(/%/g, '%-crmescaped-'));
      }
    }
    return text.replace(/%-crmescaped-/g, '%');
  }
  return text;
}

// Legacy code - ignore warnings
/* jshint ignore:start */

/**
 *  This function is called by default at the bottom of template files which have forms that have
 *  conditionally displayed/hidden sections and elements. The PHP is responsible for generating
 *  a list of 'blocks to show' and 'blocks to hide' and the template passes these parameters to
 *  this function.
 *
 * @deprecated
 * @param  showBlocks Array of element Id's to be displayed
 * @param  hideBlocks Array of element Id's to be hidden
 * @param elementType Value to set display style to for showBlocks (e.g. 'block' or 'table-row' or ...)
 */
function on_load_init_blocks(showBlocks, hideBlocks, elementType) {
  if (elementType == null) {
    elementType = 'block';
  }

  var myElement, i;

  /* This loop is used to display the blocks whose IDs are present within the showBlocks array */
  for (i = 0; i < showBlocks.length; i++) {
    myElement = document.getElementById(showBlocks[i]);
    /* getElementById returns null if element id doesn't exist in the document */
    if (myElement != null) {
      myElement.style.display = elementType;
    }
    else {
      alert('showBlocks array item not in .tpl = ' + showBlocks[i]);
    }
  }

  /* This loop is used to hide the blocks whose IDs are present within the hideBlocks array */
  for (i = 0; i < hideBlocks.length; i++) {
    myElement = document.getElementById(hideBlocks[i]);
    /* getElementById returns null if element id doesn't exist in the document */
    if (myElement != null) {
      myElement.style.display = 'none';
    }
    else {
      alert('showBlocks array item not in .tpl = ' + hideBlocks[i]);
    }
  }
}

/**
 *  This function is called when we need to show or hide a related form element (target_element)
 *  based on the value (trigger_value) of another form field (trigger_field).
 *
 * @deprecated
 * @param  trigger_field_id     HTML id of field whose onchange is the trigger
 * @param  trigger_value        List of integers - option value(s) which trigger show-element action for target_field
 * @param  target_element_id    HTML id of element to be shown or hidden
 * @param  target_element_type  Type of element to be shown or hidden ('block' or 'table-row')
 * @param  field_type           Type of element radio/select
 * @param  invert               Boolean - if true, we HIDE target on value match; if false, we SHOW target on value match
 */
function showHideByValue(trigger_field_id, trigger_value, target_element_id, target_element_type, field_type, invert) {
  var target, j;

  if (field_type == 'select') {
    var trigger = trigger_value.split("|");
    var selectedOptionValue = cj('#' + trigger_field_id).val();

    target = target_element_id.split("|");
    for (j = 0; j < target.length; j++) {
      if (invert) {
        cj('#' + target[j]).show();
      }
      else {
        cj('#' + target[j]).hide();
      }
      for (var i = 0; i < trigger.length; i++) {
        if (selectedOptionValue == trigger[i]) {
          if (invert) {
            cj('#' + target[j]).hide();
          }
          else {
            cj('#' + target[j]).show();
          }
        }
      }
    }

  }
  else {
    if (field_type == 'radio') {
      target = target_element_id.split("|");
      for (j = 0; j < target.length; j++) {
        if (cj('[name="' + trigger_field_id + '"]:first').is(':checked')) {
          if (invert) {
            cj('#' + target[j]).hide();
          }
          else {
            cj('#' + target[j]).show();
          }
        }
        else {
          if (invert) {
            cj('#' + target[j]).show();
          }
          else {
            cj('#' + target[j]).hide();
          }
        }
      }
    }
  }
}

/**
 * Function to change button text and disable one it is clicked
 * @deprecated
 * @param obj object - the button clicked
 * @param formID string - the id of the form being submitted
 * @param string procText - button text after user clicks it
 * @return bool
 */
var submitcount = 0;
/* Changes button label on submit, and disables button after submit for newer browsers.
 Puts up alert for older browsers. */
function submitOnce(obj, formId, procText) {
  // if named button clicked, change text
  if (obj.value != null) {
    cj('input[name=' + obj.name + ']').val(procText + " ...");
  }
  cj(obj).closest('form').attr('data-warn-changes', 'false');
  if (document.getElementById) { // disable submit button for newer browsers
    cj('input[name=' + obj.name + ']').attr("disabled", true);
    document.getElementById(formId).submit();
    return true;
  }
  else { // for older browsers
    if (submitcount == 0) {
      submitcount++;
      return true;
    }
    else {
      alert("Your request is currently being processed ... Please wait.");
      return false;
    }
  }
}

/**
 * Function to show / hide the row in optionFields
 * @deprecated
 * @param index string, element whose innerHTML is to hide else will show the hidden row.
 */
function showHideRow(index) {
  if (index) {
    cj('tr#optionField_' + index).hide();
    if (cj('table#optionField tr:hidden:first').length) {
      cj('div#optionFieldLink').show();
    }
  }
  else {
    cj('table#optionField tr:hidden:first').show();
    if (!cj('table#optionField tr:hidden:last').length) {
      cj('div#optionFieldLink').hide();
    }
  }
  return false;
}

/* jshint ignore:end */

if (!CRM.utils) CRM.utils = {};
if (!CRM.strings) CRM.strings = {};
if (!CRM.vars) CRM.vars = {};

(function ($, _, undefined) {
  "use strict";
  /* jshint validthis: true */

  // Theme classes for unattached elements
  $.fn.select2.defaults.dropdownCssClass = $.ui.dialog.prototype.options.dialogClass = 'crm-container';

  // https://github.com/ivaynberg/select2/pull/2090
  $.fn.select2.defaults.width = 'resolve';

  // Workaround for https://github.com/ivaynberg/select2/issues/1246
  $.ui.dialog.prototype._allowInteraction = function(e) {
    return !!$(e.target).closest('.ui-dialog, .ui-datepicker, .select2-drop, .cke_dialog, #civicrm-menu').length;
  };

  // Implements jQuery hook.prop
  $.propHooks.disabled = {
    set: function (el, value, name) {
      // Sync button enabled status with wrapper css
      if ($(el).is('span.crm-button > input.crm-form-submit')) {
        $(el).parent().toggleClass('crm-button-disabled', !!value);
      }
      // Sync button enabled status with dialog button
      if ($(el).is('.ui-dialog input.crm-form-submit')) {
        $(el).closest('.ui-dialog').find('.ui-dialog-buttonset button[data-identifier='+ $(el).attr('name') +']').prop('disabled', value);
      }
      if ($(el).is('.crm-form-date-wrapper .crm-hidden-date')) {
        $(el).siblings().prop('disabled', value);
      }
    }
  };

  /**
   * Populate a select list, overwriting the existing options except for the placeholder.
   * @param select jquery selector - 1 or more select elements
   * @param options array in format returned by api.getoptions
   * @param placeholder string|bool - new placeholder or false (default) to keep the old one
   * @param value string|array - will silently update the element with new value without triggering change
   */
  CRM.utils.setOptions = function(select, options, placeholder, value) {
    $(select).each(function() {
      var
        $elect = $(this),
        val = value || $elect.val() || [],
        opts = placeholder || placeholder === '' ? '' : '[value!=""]';
      $elect.find('option' + opts).remove();
      var newOptions = CRM.utils.renderOptions(options, val);
      if (typeof placeholder === 'string') {
        if ($elect.is('[multiple]')) {
          select.attr('placeholder', placeholder);
        } else {
          newOptions = '<option value="">' + placeholder + '</option>' + newOptions;
        }
      }
      $elect.append(newOptions);
      if (!value) {
        $elect.trigger('crmOptionsUpdated', $.extend({}, options)).trigger('change');
      }
    });
  };

  /**
   * Render an option list
   * @param options {array}
   * @param val {string} default value
   * @param escapeHtml {bool}
   * @return string
   */
  CRM.utils.renderOptions = function(options, val, escapeHtml) {
    var rendered = '',
      esc = escapeHtml === false ? _.identity : _.escape;
    if (!$.isArray(val)) {
      val = [val];
    }
    _.each(options, function(option) {
      if (option.children) {
        rendered += '<optgroup label="' + esc(option.value) + '">' +
        CRM.utils.renderOptions(option.children, val) +
        '</optgroup>';
      } else {
        var selected = ($.inArray('' + option.key, val) > -1) ? 'selected="selected"' : '';
        rendered += '<option value="' + esc(option.key) + '"' + selected + '>' + esc(option.value) + '</option>';
      }
    });
    return rendered;
  };

  function chainSelect() {
    var $form = $(this).closest('form'),
      $target = $('select[data-name="' + $(this).data('target') + '"]', $form),
      data = $target.data(),
      val = $(this).val();
    $target.prop('disabled', true);
    if ($target.is('select.crm-chain-select-control')) {
      $('select[data-name="' + $target.data('target') + '"]', $form).prop('disabled', true).blur();
    }
    if (!(val && val.length)) {
      CRM.utils.setOptions($target.blur(), [], data.emptyPrompt);
    } else {
      $target.addClass('loading');
      $.getJSON(CRM.url(data.callback), {_value: val}, function(vals) {
        $target.prop('disabled', false).removeClass('loading');
        CRM.utils.setOptions($target, vals || [], (vals && vals.length ? data.selectPrompt : data.nonePrompt));
      });
    }
  }

  /**
   * Compare Form Input values against cached initial value.
   *
   * @return {Boolean} true if changes have been made.
   */
  CRM.utils.initialValueChanged = function(el) {
    var isDirty = false;
    $(':input:visible, .select2-container:visible+:input:hidden', el).not('[type=submit], [type=button], .crm-action-menu, :disabled').each(function () {
      var
        initialValue = $(this).data('crm-initial-value'),
        currentValue = $(this).is(':checkbox, :radio') ? $(this).prop('checked') : $(this).val();
      // skip change of value for submit buttons
      if (initialValue !== undefined && !_.isEqual(initialValue, currentValue)) {
        isDirty = true;
      }
    });
    return isDirty;
  };

  /**
   * This provides defaults for ui.dialog which either need to be calculated or are different from global defaults
   *
   * @param settings
   * @returns {*}
   */
  CRM.utils.adjustDialogDefaults = function(settings) {
    settings = $.extend({width: '65%', height: '65%', modal: true}, settings || {});
    // Support relative height
    if (typeof settings.height === 'string' && settings.height.indexOf('%') > 0) {
      settings.height = parseInt($(window).height() * (parseFloat(settings.height)/100), 10);
    }
    // Responsive adjustment - increase percent width on small screens
    if (typeof settings.width === 'string' && settings.width.indexOf('%') > 0) {
      var screenWidth = $(window).width(),
        percentage = parseInt(settings.width.replace('%', ''), 10),
        gap = 100-percentage;
      if (screenWidth < 701) {
        settings.width = '100%';
      }
      else if (screenWidth < 1400) {
        settings.width = '' + parseInt(percentage+gap-((screenWidth - 700)/7*(gap)/100), 10) + '%';
      }
    }
    return settings;
  };

  /**
   * Wrapper for select2 initialization function; supplies defaults
   * @param options object
   */
  $.fn.crmSelect2 = function(options) {
    if (options === 'destroy') {
      return $(this).each(function() {
        $(this)
          .removeClass('crm-ajax-select')
          .select2('destroy');
      });
    }
    return $(this).each(function () {
      var
        $el = $(this),
        iconClass,
        settings = {allowClear: !$el.hasClass('required')};
      // quickform doesn't support optgroups so here's a hack :(
      $('option[value^=crm_optgroup]', this).each(function () {
        $(this).nextUntil('option[value^=crm_optgroup]').wrapAll('<optgroup label="' + $(this).text() + '" />');
        $(this).remove();
      });

      // quickform does not support disabled option, so yet another hack to
      // add disabled property for option values
      $('option[value^=crm_disabled_opt]', this).attr('disabled', 'disabled');

      // Placeholder icon - total hack hikacking the escapeMarkup function but select2 3.5 dosn't have any other callbacks for this :(
      if ($el.is('[class*=fa-]')) {
        settings.escapeMarkup = function (m) {
          var out = _.escape(m),
            placeholder = settings.placeholder || $el.data('placeholder') || $el.attr('placeholder') || $('option[value=""]', $el).text();
          if (m.length && placeholder === m) {
            iconClass = $el.attr('class').match(/(fa-\S*)/)[1];
            out = '<i class="crm-i ' + iconClass + '"></i> ' + out;
          }
          return out;
        };
      }

      // Defaults for single-selects
      if ($el.is('select:not([multiple])')) {
        settings.minimumResultsForSearch = 10;
        if ($('option:first', this).val() === '') {
          settings.placeholderOption = 'first';
        }
      }
      $.extend(settings, $el.data('select-params') || {}, options || {});
      if (settings.ajax) {
        $el.addClass('crm-ajax-select');
      }
      $el.select2(settings);
    });
  };

  /**
   * @see CRM_Core_Form::addEntityRef for docs
   * @param options object
   */
  $.fn.crmEntityRef = function(options) {
    if (options === 'destroy') {
      return $(this).each(function() {
        var entity = $(this).data('api-entity') || '';
        $(this)
          .off('.crmEntity')
          .removeClass('crm-form-entityref crm-' + entity.toLowerCase() + '-ref')
          .crmSelect2('destroy');
      });
    }
    options = options || {};
    options.select = options.select || {};
    return $(this).each(function() {
      var
        $el = $(this).off('.crmEntity'),
        entity = options.entity || $el.data('api-entity') || 'contact',
        selectParams = {};
      $el.data('api-entity', entity);
      $el.data('select-params', $.extend({}, $el.data('select-params') || {}, options.select));
      $el.data('api-params', $.extend(true, {}, $el.data('api-params') || {}, options.api));
      $el.data('create-links', options.create || $el.data('create-links'));
      $el.addClass('crm-form-entityref crm-' + entity.toLowerCase() + '-ref');
      var settings = {
        // Use select2 ajax helper instead of CRM.api3 because it provides more value
        ajax: {
          url: CRM.url('civicrm/ajax/rest'),
          data: function (input, page_num) {
            var params = getEntityRefApiParams($el);
            params.input = input;
            params.page_num = page_num;
            return {
              entity: $el.data('api-entity'),
              action: 'getlist',
              json: JSON.stringify(params)
            };
          },
          results: function(data) {
            return {more: data.more_results, results: data.values || []};
          }
        },
        minimumInputLength: 1,
        formatResult: CRM.utils.formatSelect2Result,
        formatSelection: function(row) {
          return (row.prefix !== undefined ? row.prefix + ' ' : '') + row.label + (row.suffix !== undefined ? ' ' + row.suffix : '');
        },
        escapeMarkup: function (m) {return m;},
        initSelection: function($el, callback) {
          var
            multiple = !!$el.data('select-params').multiple,
            val = $el.val(),
            stored = $el.data('entity-value') || [];
          if (val === '') {
            return;
          }
          // If we already have this data, just return it
          if (!_.xor(val.split(','), _.pluck(stored, 'id')).length) {
            callback(multiple ? stored : stored[0]);
          } else {
            var params = $.extend({}, $el.data('api-params') || {}, {id: val});
            CRM.api3($el.data('api-entity'), 'getlist', params).done(function(result) {
              callback(multiple ? result.values : result.values[0]);
              // Trigger change (store data to avoid an infinite loop of lookups)
              $el.data('entity-value', result.values).trigger('change');
            });
          }
        }
      };
      // Create new items inline - works for tags
      if ($el.data('create-links') && entity.toLowerCase() === 'tag') {
        selectParams.createSearchChoice = function(term, data) {
          if (!_.findKey(data, {label: term})) {
            return {id: "0", term: term, label: term + ' (' + ts('new tag') + ')'};
          }
        };
        selectParams.tokenSeparators = [','];
        selectParams.createSearchChoicePosition = 'bottom';
        $el.on('select2-selecting.crmEntity', function(e) {
          if (e.val === "0") {
            // Create a new term
            e.object.label = e.object.term;
            CRM.api3(entity, 'create', $.extend({name: e.object.term}, $el.data('api-params').params || {}))
              .done(function(created) {
                var
                  val = $el.select2('val'),
                  data = $el.select2('data'),
                  item = {id: created.id, label: e.object.term};
                if (val === "0") {
                  $el.select2('data', item, true);
                }
                else if ($.isArray(val) && $.inArray("0", val) > -1) {
                  _.remove(data, {id: "0"});
                  data.push(item);
                  $el.select2('data', data, true);
                }
              });
          }
        });
      }
      else {
        selectParams.formatInputTooShort = function() {
          var txt = $el.data('select-params').formatInputTooShort || $.fn.select2.defaults.formatInputTooShort.call(this);
          txt += entityRefFiltersMarkup($el) + renderEntityRefCreateLinks($el);
          return txt;
        };
        selectParams.formatNoMatches = function() {
          var txt = $el.data('select-params').formatNoMatches || $.fn.select2.defaults.formatNoMatches;
          txt += entityRefFiltersMarkup($el) + renderEntityRefCreateLinks($el);
          return txt;
        };
        $el.on('select2-open.crmEntity', function() {
          var $el = $(this);
          renderEntityRefFilterValue($el);
          $('#select2-drop')
            .off('.crmEntity')
            .on('click.crmEntity', 'a.crm-add-entity', function(e) {
              $el.select2('close');
              CRM.loadForm($(this).attr('href'), {
                dialog: {width: 500, height: 220}
              }).on('crmFormSuccess', function(e, data) {
                if (data.status === 'success' && data.id) {
                  CRM.status(ts('%1 Created', {1: data.label}));
                  if ($el.select2('container').hasClass('select2-container-multi')) {
                    var selection = $el.select2('data');
                    selection.push(data);
                    $el.select2('data', selection, true);
                  } else {
                    $el.select2('data', data, true);
                  }
                }
              });
              return false;
            })
            .on('change.crmEntity', '.crm-entityref-filter-value', function() {
              var filter = $el.data('user-filter') || {};
              filter.value = $(this).val();
              $(this).toggleClass('active', !!filter.value);
              $el.data('user-filter', filter);
              if (filter.value) {
                // Once a filter has been chosen, rerender create links and refocus the search box
                $el.select2('close');
                $el.select2('open');
              } else {
                $('.crm-entityref-links', '#select2-drop').replaceWith(renderEntityRefCreateLinks($el));
              }
            })
            .on('change.crmEntity', 'select.crm-entityref-filter-key', function() {
              var filter = {key: $(this).val()};
              $(this).toggleClass('active', !!filter.key);
              $el.data('user-filter', filter);
              renderEntityRefFilterValue($el);
              $('.crm-entityref-filter-key', '#select2-drop').focus();
            });
        });
      }
      $el.crmSelect2($.extend(settings, $el.data('select-params'), selectParams));
    });
  };

  /**
   * Combine api-params with user-filter
   * @param $el
   * @returns {*}
   */
  function getEntityRefApiParams($el) {
    var
      params = $.extend({params: {}}, $el.data('api-params') || {}),
      // Prevent original data from being modified - $.extend and _.clone don't cut it, they pass nested objects by reference!
      combined = _.cloneDeep(params),
      filter = $.extend({}, $el.data('user-filter') || {});
    if (filter.key && filter.value) {
      // Fieldname may be prefixed with joins
      var fieldName = _.last(filter.key.split('.'));
      // Special case for contact type/sub-type combo
      if (fieldName === 'contact_type' && (filter.value.indexOf('__') > 0)) {
        combined.params[filter.key] = filter.value.split('__')[0];
        combined.params[filter.key.replace('contact_type', 'contact_sub_type')] = filter.value.split('__')[1];
      } else {
        // Allow json-encoded api filters e.g. {"BETWEEN":[123,456]}
        combined.params[filter.key] = filter.value.charAt(0) === '{' ? $.parseJSON(filter.value) : filter.value;
      }
    }
    return combined;
  }

  function copyAttributes($source, $target, attributes) {
    _.each(attributes, function(name) {
      if ($source.attr(name) !== undefined) {
        $target.attr(name, $source.attr(name));
      }
    });
  }

  /**
   * @see http://wiki.civicrm.org/confluence/display/CRMDOC/crmDatepicker
   */
  $.fn.crmDatepicker = function(options) {
    return $(this).each(function() {
      if ($(this).is('.crm-form-date-wrapper .crm-hidden-date')) {
        // Already initialized - destroy
        $(this)
          .off('.crmDatepicker')
          .css('display', '')
          .removeClass('crm-hidden-date')
          .siblings().remove();
        $(this).unwrap();
      }
      if (options === 'destroy') {
        return;
      }
      var
        $dataField = $(this).wrap('<span class="crm-form-date-wrapper" />'),
        settings = _.cloneDeep(options || {}),
        $dateField = $(),
        $timeField = $(),
        $clearLink = $(),
        hasDatepicker = settings.date !== false && settings.date !== 'yy',
        type = hasDatepicker ? 'text' : 'number';

      if (settings.allowClear !== undefined ? settings.allowClear : !$dataField.is('.required, [required]')) {
        $clearLink = $('<a class="crm-hover-button crm-clear-link" title="'+ ts('Clear') +'"><i class="crm-i fa-times"></i></a>')
          .insertAfter($dataField);
      }
      if (settings.time !== false) {
        $timeField = $('<input>').insertAfter($dataField);
        copyAttributes($dataField, $timeField, ['class', 'disabled']);
        $timeField
          .addClass('crm-form-text crm-form-time')
          .attr('placeholder', $dataField.attr('time-placeholder') === undefined ? ts('Time') : $dataField.attr('time-placeholder'))
          .change(updateDataField)
          .timeEntry({
            spinnerImage: '',
            show24Hours: settings.time === true || settings.time === undefined ? CRM.config.timeIs24Hr : settings.time == '24'
          });
      }
      if (settings.date !== false) {
        // Render "number" field for year-only format, calendar popup for all other formats
        $dateField = $('<input type="' + type + '">').insertAfter($dataField);
        copyAttributes($dataField, $dateField, ['placeholder', 'style', 'class', 'disabled']);
        $dateField.addClass('crm-form-' + type);
        if (hasDatepicker) {
          settings.minDate = settings.minDate ? CRM.utils.makeDate(settings.minDate) : null;
          settings.maxDate = settings.maxDate ? CRM.utils.makeDate(settings.maxDate) : null;
          settings.dateFormat = typeof settings.date === 'string' ? settings.date : CRM.config.dateInputFormat;
          settings.changeMonth = _.includes(settings.dateFormat, 'm');
          settings.changeYear = _.includes(settings.dateFormat, 'y');
          if (!settings.yearRange && settings.minDate !== null && settings.maxDate !== null) {
            settings.yearRange = '' + CRM.utils.formatDate(settings.minDate, 'yy') + ':' + CRM.utils.formatDate(settings.maxDate, 'yy');
          }
          $dateField.addClass('crm-form-date').datepicker(settings);
        } else {
          $dateField.attr('min', settings.minDate ? CRM.utils.formatDate(settings.minDate, 'yy') : '1000');
          $dateField.attr('max', settings.maxDate ? CRM.utils.formatDate(settings.maxDate, 'yy') : '4000');
        }
        $dateField.change(updateDataField);
      }
      // Rudimentary validation. TODO: Roll into use of jQUery validate and ui.datepicker.validation
      function isValidDate() {
        // FIXME: parseDate doesn't work with incomplete date formats; skip validation if no month, day or year in format
        var lowerFormat = settings.dateFormat.toLowerCase();
        if (lowerFormat.indexOf('y') < 0 || lowerFormat.indexOf('m') < 0 || lowerFormat.indexOf('d') < 0) {
          return true;
        }
        try {
          $.datepicker.parseDate(settings.dateFormat, $dateField.val());
          return true;
        } catch (e) {
          return false;
        }
      }
      function updateInputFields(e, context) {
        var val = $dataField.val(),
          time = null;
        if (context !== 'userInput' && context !== 'crmClear') {
          if (hasDatepicker) {
            $dateField.datepicker('setDate', _.includes(val, '-') ? $.datepicker.parseDate('yy-mm-dd', val) : null);
          } else if ($dateField.length) {
            $dateField.val(val.slice(0, 4));
          }
          if ($timeField.length) {
            if (val.length === 8) {
              time = val;
            } else if (val.length === 19) {
              time = val.split(' ')[1];
            }
            $timeField.timeEntry('setTime', time);
          }
        }
        $clearLink.css('visibility', val ? 'visible' : 'hidden');
      }
      function updateDataField(e, context) {
        // The crmClear event wipes all the field values anyway, so no need to respond
        if (context !== 'crmClear') {
          var val = '';
          if ($dateField.val()) {
            if (hasDatepicker && isValidDate()) {
              val = $.datepicker.formatDate('yy-mm-dd', $dateField.datepicker('getDate'));
              $dateField.removeClass('crm-error');
            } else if (!hasDatepicker) {
              val = $dateField.val() + '-01-01';
            } else {
              $dateField.addClass('crm-error');
            }
          }
          if ($timeField.val()) {
            val += (val ? ' ' : '') + $timeField.timeEntry('getTime').toTimeString().substr(0, 8);
          }
          $dataField.val(val).trigger('change', ['userInput']);
        }
      }
      $dataField.hide().addClass('crm-hidden-date').on('change.crmDatepicker', updateInputFields);
      updateInputFields();
    });
  };

  $.fn.crmAjaxTable = function() {
    // Strip the ids from ajax urls to make pageLength storage more generic
    function simplifyUrl(ajax) {
      // Datatables ajax prop could be a url string or an object containing the url
      var url = typeof ajax === 'object' ? ajax.url : ajax;
      return typeof url === 'string' ? url.replace(/[&?]\w*id=\d+/g, '') : null;
    }

    return $(this).each(function() {
      // Recall pageLength for this table
      var url = simplifyUrl($(this).data('ajax'));
      if (url && window.localStorage && localStorage['dataTablePageLength:' + url]) {
        $(this).data('pageLength', localStorage['dataTablePageLength:' + url]);
      }
      // Declare the defaults for DataTables
      var defaults = {
        "processing": true,
        "serverSide": true,
        "aaSorting": [],
        "dom": '<"crm-datatable-pager-top"lfp>rt<"crm-datatable-pager-bottom"ip>',
        "pageLength": 25,
        "pagingType": "full_numbers",
        "drawCallback": function(settings) {
          //Add data attributes to cells
          $('thead th', settings.nTable).each( function( index ) {
            $.each(this.attributes, function() {
              if(this.name.match("^cell-")) {
                var cellAttr = this.name.substring(5);
                var cellValue = this.value;
                $('tbody tr', settings.nTable).each( function() {
                  $('td:eq('+ index +')', this).attr( cellAttr, cellValue );
                });
              }
            });
          });
          //Reload table after draw
          $(settings.nTable).trigger('crmLoad');
        }
      };
      //Include any table specific data
      var settings = $.extend(true, defaults, $(this).data('table'));
      // Remember pageLength
      $(this).on('length.dt', function(e, settings, len) {
        if (settings.ajax && window.localStorage) {
          localStorage['dataTablePageLength:' + simplifyUrl(settings.ajax)] = len;
        }
      });
      //Make the DataTables call
      $(this).DataTable(settings);
    });
  };

  CRM.utils.formatSelect2Result = function (row) {
    var markup = '<div class="crm-select2-row">';
    if (row.image !== undefined) {
      markup += '<div class="crm-select2-image"><img src="' + row.image + '"/></div>';
    }
    else if (row.icon_class) {
      markup += '<div class="crm-select2-icon"><div class="crm-icon ' + row.icon_class + '-icon"></div></div>';
    }
    markup += '<div><div class="crm-select2-row-label '+(row.label_class || '')+'">' +
      (row.prefix !== undefined ? row.prefix + ' ' : '') + row.label + (row.suffix !== undefined ? ' ' + row.suffix : '') +
      '</div>' +
      '<div class="crm-select2-row-description">';
    $.each(row.description || [], function(k, text) {
      markup += '<p>' + text + '</p>';
    });
    markup += '</div></div></div>';
    return markup;
  };

  function renderEntityRefCreateLinks($el) {
    var
      createLinks = $el.data('create-links'),
      params = getEntityRefApiParams($el).params,
      markup = '<div class="crm-entityref-links">';
    if (!createLinks || $el.data('api-entity').toLowerCase() !== 'contact') {
      return '';
    }
    if (createLinks === true) {
      createLinks = params.contact_type ? _.where(CRM.config.entityRef.contactCreate, {type: params.contact_type}) : CRM.config.entityRef.contactCreate;
    }
    _.each(createLinks, function(link) {
      var icon;
      switch (link.type) {
        case 'Individual':
          icon = 'fa-user';
          break;

        case 'Organization':
          icon = 'fa-building';
          break;

        case 'Household':
          icon = 'fa-home';
          break;
      }
      markup += ' <a class="crm-add-entity crm-hover-button" href="' + link.url + '">';
      if (icon) {
        markup += '<i class="crm-i ' + icon + '"></i> ';
      }
      markup += link.label + '</a>';
    });
    markup += '</div>';
    return markup;
  }

  function getEntityRefFilters($el) {
    var
      entity = $el.data('api-entity').toLowerCase(),
      filters = $.extend([], CRM.config.entityRef.filters[entity] || []),
      params = $.extend({params: {}}, $el.data('api-params') || {}).params,
      result = [];
    $.each(filters, function() {
      var filter = $.extend({type: 'select', 'attributes': {}, entity: entity}, this);
      if (typeof params[filter.key] === 'undefined') {
        result.push(filter);
      }
      else if (filter.key == 'contact_type' && typeof params.contact_sub_type === 'undefined') {
        filter.options = _.remove(filter.options, function(option) {
          return option.key.indexOf(params.contact_type + '__') === 0;
        });
        result.push(filter);
      }
    });
    return result;
  }

  /**
   * Provide markup for entity ref filters
   */
  function entityRefFiltersMarkup($el) {
    var
      filters = getEntityRefFilters($el),
      filter = $el.data('user-filter') || {},
      filterSpec = filter.key ? _.find(filters, {key: filter.key}) : null;
    if (!filters.length) {
      return '';
    }
    var markup = '<div class="crm-entityref-filters">' +
      '<select class="crm-entityref-filter-key' + (filter.key ? ' active' : '') + '">' +
      '<option value="">' + ts('Refine search...') + '</option>' +
      CRM.utils.renderOptions(filters, filter.key) +
      '</select>' + entityRefFilterValueMarkup(filter, filterSpec) + '</div>';
    return markup;
  }

  /**
   * Provide markup for entity ref filter value field
   */
  function entityRefFilterValueMarkup(filter, filterSpec) {
    var markup = '';
    if (filterSpec) {
      var attrs = '',
        attributes = _.cloneDeep(filterSpec.attributes);
      if (filterSpec.type !== 'select') {
        attributes.type = filterSpec.type;
        attributes.value = typeof filter.value !== 'undefined' ? filter.value : '';
      }
      attributes.class = 'crm-entityref-filter-value' + (filter.value ? ' active' : '');
      $.each(attributes, function (attr, val) {
        attrs += ' ' + attr + '="' + val + '"';
      });
      if (filterSpec.type === 'select') {
        markup = '<select' + attrs + '><option value="">' + ts('- select -') + '</option>';
        if (filterSpec.options) {
          markup += CRM.utils.renderOptions(filterSpec.options, filter.value);
        }
        markup += '</select>';
      } else {
        markup = '<input' + attrs + '/>';
      }
    }
    return markup;
  }

  /**
   * Render the entity ref filter value field
   */
  function renderEntityRefFilterValue($el) {
    var
      filter = $el.data('user-filter') || {},
      filterSpec = filter.key ? _.find(getEntityRefFilters($el), {key: filter.key}) : null,
      $keyField = $('.crm-entityref-filter-key', '#select2-drop'),
      $valField = null;
    if (filterSpec) {
      $('.crm-entityref-filter-value', '#select2-drop').remove();
      $valField = $(entityRefFilterValueMarkup(filter, filterSpec));
      $keyField.after($valField);
      if (filterSpec.type === 'select' && !filterSpec.options) {
        loadEntityRefFilterOptions(filter, filterSpec, $valField, $el);
      }
    } else {
      $('.crm-entityref-filter-value', '#select2-drop').hide().val('').change();
    }
  }

  /**
   * Fetch options for a filter via ajax api
   */
  function loadEntityRefFilterOptions(filter, filterSpec, $valField, $el) {
    $valField.prop('disabled', true);
    // Fieldname may be prefixed with joins - strip those out
    var fieldName = _.last(filter.key.split('.'));
    CRM.api3(filterSpec.entity, 'getoptions', {field: fieldName, context: 'search', sequential: 1})
      .done(function(result) {
        var entity = $el.data('api-entity').toLowerCase(),
          globalFilterSpec = _.find(CRM.config.entityRef.filters[entity], {key: filter.key}) || {};
        // Store options globally so we don't have to look them up again
        globalFilterSpec.options = result.values;
        $valField.prop('disabled', false);
        CRM.utils.setOptions($valField, result.values);
        $valField.val(filter.value || '');
      });
  }

  //CRM-15598 - Override url validator method to allow relative url's (e.g. /index.htm)
  $.validator.addMethod("url", function(value, element) {
    if (/^\//.test(value)) {
      // Relative url: prepend dummy path for validation.
      value = 'http://domain.tld' + value;
    }
    // From jQuery Validation Plugin v1.12.0
    return this.optional(element) || /^(https?|s?ftp):\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i.test(value);
  });

  /**
   * Wrapper for jQuery validate initialization function; supplies defaults
   */
  $.fn.crmValidate = function(params) {
    return $(this).each(function () {
      var that = this,
        settings = $.extend({}, CRM.validate._defaults, CRM.validate.params);
      $(this).validate(settings);
      // Call any post-initialization callbacks
      if (CRM.validate.functions && CRM.validate.functions.length) {
        $.each(CRM.validate.functions, function(i, func) {
          func.call(that);
        });
      }
    });
  };

  // Initialize widgets
  $(document)
    .on('crmLoad', function(e) {
      $('table.row-highlight', e.target)
        .off('.rowHighlight')
        .on('change.rowHighlight', 'input.select-row, input.select-rows', function (e, data) {
          var filter, $table = $(this).closest('table');
          if ($(this).hasClass('select-rows')) {
            filter = $(this).prop('checked') ? ':not(:checked)' : ':checked';
            $('input.select-row' + filter, $table).prop('checked', $(this).prop('checked')).trigger('change', 'master-selected');
          }
          else {
            $(this).closest('tr').toggleClass('crm-row-selected', $(this).prop('checked'));
            if (data !== 'master-selected') {
              $('input.select-rows', $table).prop('checked', $(".select-row:not(':checked')", $table).length < 1);
            }
          }
        })
        .find('input.select-row:checked').parents('tr').addClass('crm-row-selected');
      $('table.crm-sortable', e.target).DataTable();
      $('table.crm-ajax-table', e.target).each(function() {
        var
          $table = $(this),
          $accordion = $table.closest('.crm-accordion-wrapper.collapsed, .crm-collapsible.collapsed');
        // For tables hidden by collapsed accordions, wait.
        if ($accordion.length) {
          $accordion.one('crmAccordion:open', function() {
            $table.crmAjaxTable();
          });
        } else {
          $table.crmAjaxTable();
        }
      });
      if ($("input:radio[name=radio_ts]").size() == 1) {
        $("input:radio[name=radio_ts]").prop("checked", true);
      }
      $('.crm-select2:not(.select2-offscreen, .select2-container)', e.target).crmSelect2();
      $('.crm-form-entityref:not(.select2-offscreen, .select2-container)', e.target).crmEntityRef();
      $('select.crm-chain-select-control', e.target).off('.chainSelect').on('change.chainSelect', chainSelect);
      $('.crm-form-text[data-crm-datepicker]', e.target).each(function() {
        $(this).crmDatepicker($(this).data('crmDatepicker'));
      });
      // Cache Form Input initial values
      $('form[data-warn-changes] :input', e.target).each(function() {
        $(this).data('crm-initial-value', $(this).is(':checkbox, :radio') ? $(this).prop('checked') : $(this).val());
      });
      $('textarea.crm-form-wysiwyg', e.target).each(function() {
        if ($(this).hasClass("collapsed")) {
          CRM.wysiwyg.createCollapsed(this);
        } else {
          CRM.wysiwyg.create(this);
        }
      });
    })
    .on('dialogopen', function(e) {
      var $el = $(e.target);
      // Modal dialogs should disable scrollbars
      if ($el.dialog('option', 'modal')) {
        $el.addClass('modal-dialog');
        $('body').css({overflow: 'hidden'});
      }
      $el.parent().find('.ui-dialog-titlebar .ui-icon-closethick').removeClass('ui-icon-closethick').addClass('fa-times');
      // Add resize button
      if ($el.parent().hasClass('crm-container') && $el.dialog('option', 'resizable')) {
        $el.parent().find('.ui-dialog-titlebar').append($('<button class="crm-dialog-titlebar-resize ui-dialog-titlebar-close" title="'+ts('Toggle fullscreen')+'" style="right:2em;"/>').button({icons: {primary: 'fa-expand'}, text: false}));
        $('.crm-dialog-titlebar-resize', $el.parent()).click(function(e) {
          if ($el.data('origSize')) {
            $el.dialog('option', $el.data('origSize'));
            $el.data('origSize', null);
          } else {
            var menuHeight = $('#civicrm-menu').outerHeight();
            $el.data('origSize', {
              position: {my: 'center', at: 'center center+' + (menuHeight / 2), of: window},
              width: $el.dialog('option', 'width'),
              height: $el.dialog('option', 'height')
            });
            $el.dialog('option', {width: '100%', height: ($(window).height() - menuHeight), position: {my: "top", at: "top+"+menuHeight, of: window}});
          }
          $el.trigger('dialogresize');
          e.preventDefault();
        });
      }
    })
    .on('dialogclose', function(e) {
      // Restore scrollbars when closing modal
      if ($('.ui-dialog .modal-dialog:visible').not(e.target).length < 1) {
        $('body').css({overflow: ''});
      }
    })
    .on('submit', function(e) {
      // CRM-14353 - disable changes warn when submitting a form
      $('[data-warn-changes]').attr('data-warn-changes', 'false');
    });

  // CRM-14353 - Warn of unsaved changes for forms which have opted in
  window.onbeforeunload = function() {
    if (CRM.utils.initialValueChanged($('form[data-warn-changes=true]:visible'))) {
      return ts('You have unsaved changes.');
    }
  };

  $.fn.crmtooltip = function () {
    $(document)
      .on('mouseover', 'a.crm-summary-link:not(.crm-processed)', function (e) {
        $(this).addClass('crm-processed crm-tooltip-active');
        var topDistance = e.pageY - $(window).scrollTop();
        if (topDistance < 300 || topDistance < $(this).children('.crm-tooltip-wrapper').height()) {
          $(this).addClass('crm-tooltip-down');
        }
        if (!$(this).children('.crm-tooltip-wrapper').length) {
          $(this).append('<div class="crm-tooltip-wrapper"><div class="crm-tooltip"></div></div>');
          $(this).children().children('.crm-tooltip')
            .html('<div class="crm-loading-element"></div>')
            .load(this.href);
        }
      })
      .on('mouseout', 'a.crm-summary-link', function () {
        $(this).removeClass('crm-processed crm-tooltip-active crm-tooltip-down');
      })
      .on('click', 'a.crm-summary-link', false);
  };

  var helpDisplay, helpPrevious;
  // Non-ajax example:
  //   CRM.help('Example title', 'Here is some text to describe this example');
  // Ajax example (will load help id "foo" from templates/CRM/bar.tpl):
  //   CRM.help('Example title', {id: 'foo', file: 'CRM/bar'});
  CRM.help = function (title, params, url) {
    var ajax = typeof params !== 'string';
    if (helpDisplay && helpDisplay.close) {
      // If the same link is clicked twice, just close the display
      if (helpDisplay.isOpen && _.isEqual(helpPrevious, params)) {
        helpDisplay.close();
        return;
      }
      helpDisplay.close();
    }
    helpPrevious = _.cloneDeep(params);
    helpDisplay = CRM.alert(ajax ? '...' : params, title, 'crm-help ' + (ajax ? 'crm-msg-loading' : 'info'), {expires: 0});
    if (ajax) {
      if (!url) {
        url = CRM.url('civicrm/ajax/inline');
        params.class_name = 'CRM_Core_Page_Inline_Help';
        params.type = 'page';
      }
      $.ajax(url, {
        data: params,
        dataType: 'html',
        success: function (data) {
          $('#crm-notification-container .crm-help .notify-content:last').html(data);
          $('#crm-notification-container .crm-help').removeClass('crm-msg-loading').addClass('info');
        },
        error: function () {
          $('#crm-notification-container .crm-help .notify-content:last').html('Unable to load help file.');
          $('#crm-notification-container .crm-help').removeClass('crm-msg-loading').addClass('error');
        }
      });
    }
  };
  /**
   * @see https://wiki.civicrm.org/confluence/display/CRMDOC/Notification+Reference
   */
  CRM.status = function(options, deferred) {
    // For simple usage without async operations you can pass in a string. 2nd param is optional string 'error' if this is not a success msg.
    if (typeof options === 'string') {
      return CRM.status({start: options, success: options, error: options})[deferred === 'error' ? 'reject' : 'resolve']();
    }
    var opts = $.extend({
      start: ts('Saving...'),
      success: ts('Saved'),
      error: function(data) {
        var msg = $.isPlainObject(data) && data.error_message;
        CRM.alert(msg || ts('Sorry an error occurred and your information was not saved'), ts('Error'), 'error');
      }
    }, options || {});
    var $msg = $('<div class="crm-status-box-outer status-start"><div class="crm-status-box-inner"><div class="crm-status-box-msg">' + opts.start + '</div></div></div>')
      .appendTo('body');
    $msg.css('min-width', $msg.width());
    function handle(status, data) {
      var endMsg = typeof(opts[status]) === 'function' ? opts[status](data) : opts[status];
      if (endMsg) {
        $msg.removeClass('status-start').addClass('status-' + status).find('.crm-status-box-msg').html(endMsg);
        window.setTimeout(function() {
          $msg.fadeOut('slow', function() {
            $msg.remove();
          });
        }, 2000);
      } else {
        $msg.remove();
      }
    }
    return (deferred || new $.Deferred())
      .done(function(data) {
        // If the server returns an error msg call the error handler
        var status = $.isPlainObject(data) && (data.is_error || data.status === 'error') ? 'error' : 'success';
        handle(status, data);
      })
      .fail(function(data) {
        handle('error', data);
      });
  };

  // Convert an Angular promise to a jQuery promise
  CRM.toJqPromise = function(aPromise) {
    var jqDeferred = $.Deferred();
    aPromise.then(
      function(data) { jqDeferred.resolve(data); },
      function(data) { jqDeferred.reject(data); }
      // should we also handle progress events?
    );
    return jqDeferred.promise();
  };

  CRM.toAPromise = function($q, jqPromise) {
    var aDeferred = $q.defer();
    jqPromise.then(
      function(data) { aDeferred.resolve(data); },
      function(data) { aDeferred.reject(data); }
      // should we also handle progress events?
    );
    return aDeferred.promise;
  };

  /**
   * @see https://wiki.civicrm.org/confluence/display/CRMDOC/Notification+Reference
   */
  CRM.alert = function (text, title, type, options) {
    type = type || 'alert';
    title = title || '';
    options = options || {};
    if ($('#crm-notification-container').length) {
      var params = {
        text: text,
        title: title,
        type: type
      };
      // By default, don't expire errors and messages containing links
      var extra = {
        expires: (type == 'error' || text.indexOf('<a ') > -1) ? 0 : (text ? 10000 : 5000),
        unique: true
      };
      options = $.extend(extra, options);
      options.expires = options.expires === false ? 0 : parseInt(options.expires, 10);
      if (options.unique && options.unique !== '0') {
        $('#crm-notification-container .ui-notify-message').each(function () {
          if (title === $('h1', this).html() && text === $('.notify-content', this).html()) {
            $('.icon.ui-notify-close', this).click();
          }
        });
      }
      return $('#crm-notification-container').notify('create', params, options);
    }
    else {
      if (title.length) {
        text = title + "\n" + text;
      }
      alert(text);
      return null;
    }
  };

  /**
   * Close whichever alert contains the given node
   *
   * @param node
   */
  CRM.closeAlertByChild = function (node) {
    $(node).closest('.ui-notify-message').find('.icon.ui-notify-close').click();
  };

  /**
   * @see https://wiki.civicrm.org/confluence/display/CRMDOC/Notification+Reference
   */
  CRM.confirm = function (options) {
    var dialog, url, msg, buttons = [], settings = {
      title: ts('Confirm'),
      message: ts('Are you sure you want to continue?'),
      url: null,
      width: 'auto',
      height: 'auto',
      resizable: false,
      dialogClass: 'crm-container crm-confirm',
      close: function () {
        $(this).dialog('destroy').remove();
      },
      options: {
        no: ts('Cancel'),
        yes: ts('Continue')
      }
    };
    if (options && options.url) {
      settings.resizable = true;
      settings.height = '50%';
    }
    $.extend(settings, ($.isFunction(options) ? arguments[1] : options) || {});
    settings = CRM.utils.adjustDialogDefaults(settings);
    if (!settings.buttons && $.isPlainObject(settings.options)) {
      $.each(settings.options, function(op, label) {
        buttons.push({
          text: label,
          'data-op': op,
          icons: {primary: op === 'no' ? 'fa-times' : 'fa-check'},
          click: function() {
            var event = $.Event('crmConfirm:' + op);
            $(this).trigger(event);
            if (!event.isDefaultPrevented()) {
              dialog.dialog('close');
            }
          }
        });
      });
      // Order buttons so that "no" goes on the right-hand side
      settings.buttons = _.sortBy(buttons, 'data-op').reverse();
    }
    url = settings.url;
    msg = url ? '' : settings.message;
    delete settings.options;
    delete settings.message;
    delete settings.url;
    dialog = $('<div class="crm-confirm-dialog"></div>').html(msg || '').dialog(settings);
    if ($.isFunction(options)) {
      dialog.on('crmConfirm:yes', options);
    }
    if (url) {
      CRM.loadPage(url, {target: dialog});
    }
    else {
      dialog.trigger('crmLoad');
    }
    return dialog;
  };

  /** provides a local copy of ts for a domain */
  CRM.ts = function(domain) {
    return function(message, options) {
      if (domain) {
        options = $.extend(options || {}, {domain: domain});
      }
      return ts(message, options);
    };
  };

  CRM.addStrings = function(domain, strings) {
    var bucket = (domain == 'civicrm' ? 'strings' : 'strings::' + domain);
    CRM[bucket] = CRM[bucket] || {};
    _.extend(CRM[bucket], strings);
  };

  /**
   * @see https://wiki.civicrm.org/confluence/display/CRMDOC/Notification+Reference
   */
  $.fn.crmError = function (text, title, options) {
    title = title || '';
    text = text || '';
    options = options || {};

    var extra = {
      expires: 0
    };
    if ($(this).length) {
      if (title === '') {
        var label = $('label[for="' + $(this).attr('name') + '"], label[for="' + $(this).attr('id') + '"]').not('[generated=true]');
        if (label.length) {
          label.addClass('crm-error');
          var $label = label.clone();
          if (text === '' && $('.crm-marker', $label).length > 0) {
            text = $('.crm-marker', $label).attr('title');
          }
          $('.crm-marker', $label).remove();
          title = $label.text();
        }
      }
      $(this).addClass('crm-error');
    }
    var msg = CRM.alert(text, title, 'error', $.extend(extra, options));
    if ($(this).length) {
      var ele = $(this);
      setTimeout(function () {
        ele.one('change', function () {
          if (msg && msg.close) msg.close();
          ele.removeClass('error');
          label.removeClass('crm-error');
        });
      }, 1000);
    }
    return msg;
  };

  // Display system alerts through js notifications
  function messagesFromMarkup() {
    $('div.messages:visible', this).not('.help').not('.no-popup').each(function () {
      var text, title = '';
      $(this).removeClass('status messages');
      var type = $(this).attr('class').split(' ')[0] || 'alert';
      type = type.replace('crm-', '');
      $('.icon', this).remove();
      if ($('.msg-text', this).length > 0) {
        text = $('.msg-text', this).html();
        title = $('.msg-title', this).html();
      }
      else {
        text = $(this).html();
      }
      var options = $(this).data('options') || {};
      $(this).remove();
      // Duplicates were already removed server-side
      options.unique = false;
      CRM.alert(text, title, type, options);
    });
    // Handle qf form errors
    $('form :input.error', this).one('blur', function() {
      $('.ui-notify-message.error a.ui-notify-close').click();
      $(this).removeClass('error');
      $(this).next('span.crm-error').remove();
      $('label[for="' + $(this).attr('name') + '"], label[for="' + $(this).attr('id') + '"]')
        .removeClass('crm-error')
        .find('.crm-error').removeClass('crm-error');
    });
  }

  /**
   * Improve blockUI when used with jQuery dialog
   */
  var originalBlock = $.fn.block,
    originalUnblock = $.fn.unblock;

  $.fn.block = function(opts) {
    if ($(this).is('.ui-dialog-content')) {
      originalBlock.call($(this).parents('.ui-dialog'), opts);
      return $(this);
    }
    return originalBlock.call(this, opts);
  };
  $.fn.unblock = function(opts) {
    if ($(this).is('.ui-dialog-content')) {
      originalUnblock.call($(this).parents('.ui-dialog'), opts);
      return $(this);
    }
    return originalUnblock.call(this, opts);
  };

  // Preprocess all CRM ajax calls to display messages
  $(document).ajaxSuccess(function(event, xhr, settings) {
    try {
      if ((!settings.dataType || settings.dataType == 'json') && xhr.responseText) {
        var response = $.parseJSON(xhr.responseText);
        if (typeof(response.crmMessages) == 'object') {
          $.each(response.crmMessages, function(n, msg) {
            CRM.alert(msg.text, msg.title, msg.type, msg.options);
          });
        }
        if (response.backtrace) {
          CRM.console('log', response.backtrace);
        }
        if (typeof response.deprecated === 'string') {
          CRM.console('warn', response.deprecated);
        }
      }
    }
    // Ignore errors thrown by parseJSON
    catch (e) {}
  });

  $(function () {
    $.blockUI.defaults.message = null;
    $.blockUI.defaults.ignoreIfBlocked = true;

    if ($('#crm-container').hasClass('crm-public')) {
      $.fn.select2.defaults.dropdownCssClass = $.ui.dialog.prototype.options.dialogClass = 'crm-container crm-public';
    }

    // Trigger crmLoad on initial content for consistency. It will also be triggered for ajax-loaded content.
    $('.crm-container').trigger('crmLoad');

    if ($('#crm-notification-container').length) {
      // Initialize notifications
      $('#crm-notification-container').notify();
      messagesFromMarkup.call($('#crm-container'));
    }

    $('body')
      // bind the event for image popup
      .on('click', 'a.crm-image-popup', function(e) {
        CRM.confirm({
          title: ts('Preview'),
          resizable: true,
          // Prevent overlap with the menubar
          maxHeight: $(window).height() - 30,
          position: {my: 'center', at: 'center center+15', of: window},
          message: '<div class="crm-custom-image-popup"><img style="max-width: 100%" src="' + $(this).attr('href') + '"></div>',
          options: null
        });
        e.preventDefault();
      })

      .on('click', function (event) {
        $('.btn-slide-active').removeClass('btn-slide-active').find('.panel').hide();
        if ($(event.target).is('.btn-slide')) {
          $(event.target).addClass('btn-slide-active').find('.panel').show();
        }
      })

      // Handle clear button for form elements
      .on('click', 'a.crm-clear-link', function() {
        $(this).css({visibility: 'hidden'}).siblings('.crm-form-radio:checked').prop('checked', false).trigger('change', ['crmClear']);
        $(this).siblings('input:text').val('').trigger('change', ['crmClear']);
        return false;
      })
      .on('change', 'input.crm-form-radio:checked', function() {
        $(this).siblings('.crm-clear-link').css({visibility: ''});
      })

      // Allow normal clicking of links within accordions
      .on('click.crmAccordions', 'div.crm-accordion-header a, .collapsible-title a', function (e) {
        e.stopPropagation();
      })
      // Handle accordions
      .on('click.crmAccordions', '.crm-accordion-header, .crm-collapsible .collapsible-title', function (e) {
        var action = 'open';
        if ($(this).parent().hasClass('collapsed')) {
          $(this).next().css('display', 'none').slideDown(200);
        }
        else {
          $(this).next().css('display', 'block').slideUp(200);
          action = 'close';
        }
        $(this).parent().toggleClass('collapsed').trigger('crmAccordion:' + action);
        e.preventDefault();
      });

    $().crmtooltip();
  });

  /**
   * Collapse or expand an accordion
   * @param speed
   */
  $.fn.crmAccordionToggle = function (speed) {
    $(this).each(function () {
      var action = 'open';
      if ($(this).hasClass('collapsed')) {
        $('.crm-accordion-body', this).first().css('display', 'none').slideDown(speed);
      }
      else {
        $('.crm-accordion-body', this).first().css('display', 'block').slideUp(speed);
        action = 'close';
      }
      $(this).toggleClass('collapsed').trigger('crmAccordion:' + action);
    });
  };

  /**
   * Clientside currency formatting
   * @param number value
   * @param [optional] boolean onlyNumber - if true, we return formatted amount without currency sign
   * @param [optional] string format - currency representation of the number 1234.56
   * @return string
   */
  var currencyTemplate;
  CRM.formatMoney = function(value, onlyNumber, format) {
    var decimal, separator, sign, i, j, result;
    if (value === 'init' && format) {
      currencyTemplate = format;
      return;
    }
    format = format || currencyTemplate;
    result = /1(.?)234(.?)56/.exec(format);
    if (result === null) {
      return 'Invalid format passed to CRM.formatMoney';
    }
    separator = result[1];
    decimal = result[2];
    sign = (value < 0) ? '-' : '';
    //extracting the absolute value of the integer part of the number and converting to string
    i = parseInt(value = Math.abs(value).toFixed(2)) + '';
    j = ((j = i.length) > 3) ? j % 3 : 0;
    result = sign + (j ? i.substr(0, j) + separator : '') + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + separator) + (2 ? decimal + Math.abs(value - i).toFixed(2).slice(2) : '');
    if ( onlyNumber ) {
      return result;
    }
    return format.replace(/1.*234.*56/, result);
  };

  CRM.console = function(method, title, msg) {
    if (window.console) {
      method = $.isFunction(console[method]) ? method : 'log';
      if (msg === undefined) {
        return console[method](title);
      } else {
        return console[method](title, msg);
      }
    }
  };

  // Determine if a user has a given permission.
  // @see CRM_Core_Resources::addPermissions
  CRM.checkPerm = function(perm) {
    return CRM.permissions[perm];
  };

  // Round while preserving sigfigs
  CRM.utils.sigfig = function(n, digits) {
    var len = ("" + n).length;
    var scale = Math.pow(10.0, len-digits);
    return Math.round(n / scale) * scale;
  };

  // Create a js Date object from a unix timestamp or a yyyy-mm-dd string
  CRM.utils.makeDate = function(input) {
    switch (typeof input) {
      case 'object':
        // already a date object
        return input;

      case 'string':
        // convert iso format
        return $.datepicker.parseDate('yy-mm-dd', input.substr(0, 10));

      case 'number':
        // convert unix timestamp
        return new Date(input * 1000);
    }
    throw 'Invalid input passed to CRM.utils.makeDate';
  };

  // Format a date for output to the user
  // Input may be a js Date object, a unix timestamp or a yyyy-mm-dd string
  CRM.utils.formatDate = function(input, outputFormat) {
    return input ? $.datepicker.formatDate(outputFormat || CRM.config.dateInputFormat, CRM.utils.makeDate(input)) : '';
  };
})(jQuery, _);
