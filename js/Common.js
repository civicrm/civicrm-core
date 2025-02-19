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

var submitcount = 0;

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
    return !!$(e.target).closest('.ui-dialog, .ui-datepicker, .select2-drop, .cke_dialog, .ck-balloon-panel, #civicrm-menu').length;
  };

  // Implements jQuery hook.prop
  $.propHooks.disabled = {
    set: function (el, value, name) {
      // Sync button enabled status with wrapper css
      if ($(el).is('.crm-button.crm-form-submit')) {
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

  var scriptsLoaded = {};
  CRM.loadScript = function(url, appendCacheCode) {
    if (!scriptsLoaded[url]) {
      var script = document.createElement('script'),
        src = url;
      if (appendCacheCode !== false) {
        src += (_.includes(url, '?') ? '&r=' : '?r=') + CRM.config.resourceCacheCode;
      }
      scriptsLoaded[url] = $.Deferred();
      script.onload = function () {
        // Give the script time to execute
        window.setTimeout(function () {
          if (window.jQuery === CRM.$ && CRM.CMSjQuery) {
            window.jQuery = CRM.CMSjQuery;
          }
          scriptsLoaded[url].resolve();
        }, 100);
      };
      // Make jQuery global available while script is loading
      if (window.jQuery !== CRM.$) {
        CRM.CMSjQuery = window.jQuery;
        window.jQuery = CRM.$;
      }
      script.src = src;
      document.getElementsByTagName("head")[0].appendChild(script);
    }
    return scriptsLoaded[url];
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
      if (options.length == 0) {
        $elect.removeClass('required');
      } else if ($elect.hasClass('crm-field-required') && !$elect.hasClass('required')) {
        $elect.addClass('required');
      }
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

  CRM.utils.getOptions = function(select) {
    var options = [];
    $('option', select).each(function() {
      var option = {key: $(this).attr('value'), value: $(this).text()};
      if (option.key !== '') {
        options.push(option);
      }
    });
    return options;
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
    settings = $.extend({width: '65%', height: '40%', modal: true}, settings || {});
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
    if (settings.dialogClass && !_.includes(settings.dialogClass, 'crm-container')) {
      settings.dialogClass += ' crm-container';
    }
    return settings;
  };

  function formatCrmSelect2(row) {
    var icon = row.icon || $(row.element).data('icon'),
      color = row.color || $(row.element).data('color'),
      description = row.description || $(row.element).data('description'),
      ret = '';
    if (icon) {
      ret += '<i class="crm-i ' + icon + '" aria-hidden="true"></i> ';
    }
    if (color) {
      ret += '<span class="crm-select-item-color" style="background-color: ' + color + '"></span> ';
    }
    return ret + _.escape(row.text) + (description ? '<div class="crm-select2-row-description"><p>' + _.escape(description) + '</p></div>' : '');
  }

  /**
   * Helper to generate an icon with alt text.
   *
   * See also smarty `{icon}` and CRM_Core_Page::crmIcon() functions
   *
   * @param string icon
   *   The Font Awesome icon class to use.
   * @param string text
   *   Alt text to display.
   * @param mixed condition
   *   This will only display if this is truthy.
   *
   * @return string
   *   The formatted icon markup.
   */
  CRM.utils.formatIcon = function (icon, text, condition) {
    if (typeof condition !== 'undefined' && !condition) {
      return '';
    }
    var title = '';
    var sr = '';
    if (text) {
      text = _.escape(text);
      title = ' title="' + text + '"';
      sr = '<span class="sr-only">' + text + '</span>';
    }
    return '<i class="crm-i ' + icon + '"' + title + ' aria-hidden="true"></i>' + sr;
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
          .off('.crmSelect2')
          .select2('destroy');
      });
    }
    return $(this).each(function () {
      var
        $el = $(this),
        iconClass,
        settings = {
          allowClear: !$el.hasClass('required'),
          formatResult: formatCrmSelect2,
          formatSelection: formatCrmSelect2
        };

      // quickform doesn't support optgroups so here's a hack :(
      // Instead of using wrapAll or similar that repeatedly appends options to the group and redraw the page (=> very slow on large lists),
      // build bulk HTML and insert in single shot
      var optGroups = {};
      $('option[value^=crm_optgroup]', this).each(function () {
        var groupHtml = '';
          $(this).nextUntil('option[value^=crm_optgroup]').each(function () {
          groupHtml += this.outerHTML;
        });
        optGroups[$(this).text()] = groupHtml;
        $(this).remove();
      });
      var replacedHtml = '';
      for (var groupLabel in optGroups) {
        replacedHtml += '<optgroup label="' + groupLabel + '">' + optGroups[groupLabel] + '</optgroup>';
      }
      if (replacedHtml) {
        $el.html(replacedHtml);
      }

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
            out = '<i class="crm-i ' + iconClass + '" aria-hidden="true"></i> ' + out;
          }
          return out;
        };
      }

      $el
        .off('.crmSelect2')
        .on('select2-loaded.crmSelect2', function() {
          // Use description as title for each option
          $('.crm-select2-row-description', '#select2-drop').each(function() {
            $(this).closest('.select2-result-label').attr('title', $(this).text());
          });
          // Collapsible optgroups should be expanded when searching (searching happens within select2-drop for single selects, but within the element for multiselects; this handles both)
          if ($('#select2-drop.collapsible-optgroups-enabled .select2-search input.select2-input, .select2-dropdown-open.collapsible-optgroups .select2-search-field input.select2-input').val()) {
            $('#select2-drop.collapsible-optgroups-enabled li.select2-result-with-children')
              .addClass('optgroup-expanded');
          }
        })
        // Handle collapsible optgroups
        .on('select2-open', function(e) {
          var isCollapsible = $(e.target).hasClass('collapsible-optgroups');
          $('#select2-drop')
            .off('.collapseOptionGroup')
            .toggleClass('collapsible-optgroups-enabled', isCollapsible);
          if (isCollapsible) {
            $('#select2-drop')
              .on('click.collapseOptionGroup', '.select2-result-with-children > .select2-result-label', function() {
                $(this).parent().toggleClass('optgroup-expanded');
              })
              // If the first item in the list is an optgroup, expand it
              .find('li.select2-result-with-children:first-child').addClass('optgroup-expanded');
          }
        })
        .on('select2-close', function() {
          $('#select2-drop').off('.collapseOptionGroup').removeClass('collapsible-optgroups-enabled');
        });

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

  function getStaticOptions(staticItems) {
    var staticPresets = {
      user_contact_id: {
        id: 'user_contact_id',
        label: ts('Select Current User'),
        icon: 'fa-user-circle-o'
      }
    };

    return _.transform(staticItems || [], function(staticItems, option) {
      staticItems.push(_.isString(option) ? staticPresets[option] : option);
    });
  }

  function renderQuickAddMarkup(quickAddLinks) {
    if (!quickAddLinks || !quickAddLinks.length) {
      return '';
    }
    let markup = '<div class="crm-entityref-links crm-entityref-quick-add">';
    quickAddLinks.forEach((link) => {
      markup += ' <a class="crm-hover-button" href="' + _.escape(CRM.url(link.path)) + '">' +
        '<i class="crm-i ' + _.escape(link.icon) + '" aria-hidden="true"></i> ' +
        _.escape(link.title) + '</a>';
    });
    markup += '</div>';
    return markup;
  }

  function renderStaticOptionMarkup(staticItems) {
    if (!staticItems.length) {
      return '';
    }
    var markup = '<div class="crm-entityref-links crm-entityref-links-static">';
    _.each(staticItems, function(link) {
      markup += ' <a class="crm-hover-button" href="#' + _.escape(link.id) + '">' +
        '<i class="crm-i ' + _.escape(link.icon) + '" aria-hidden="true"></i> ' +
        _.escape(link.label) + '</a>';
    });
    markup += '</div>';
    return markup;
  }

  // Autocomplete based on APIv4 and Select2.
  $.fn.crmAutocomplete = function(entityName, apiParams, select2Options) {
    function getApiParams() {
      if (typeof apiParams === 'function') {
        return apiParams();
      }
      return apiParams || {};
    }
    function getQuickAddLinks(paths) {
      const links = [];
      if (paths && paths.length) {
        const apiParams = getApiParams();
        paths.forEach((path) => {
          let link = CRM.config.quickAdd.find((link) => link.path === path);
          if (link) {
            links.push({
              path: path + '#?' + $.param({
                parentFormName: apiParams.formName,
                parentFormFieldName: apiParams.fieldName,
              }),
              icon: link.icon,
              title: link.title,
            });
          }
        });
      }
      return links;
    }
    if (entityName === 'destroy') {
      return $(this).off('.crmEntity').crmSelect2('destroy');
    }
    select2Options = select2Options || {};
    return $(this).each(function() {
      const $el = $(this).off('.crmEntity');
      let staticItems = getStaticOptions(select2Options.static),
        quickAddLinks = getQuickAddLinks(select2Options.quickAdd),
        multiple = !!select2Options.multiple;

      $el.crmSelect2(_.extend({
        ajax: {
          quietMillis: 250,
          url: CRM.url('civicrm/ajax/api4/' + entityName + '/autocomplete'),
          data: function (input, pageNum) {
            return {params: JSON.stringify(_.assign({
              input: input,
              page: pageNum || 1
            }, getApiParams()))};
          },
          results: function(data) {
            return {
              results: data.values,
              more: data.count > data.countFetched
            };
          },
        },
        minimumInputLength: 1,
        formatResult: CRM.utils.formatSelect2Result,
        formatSelection: formatEntityRefSelection,
        escapeMarkup: _.identity,
        initSelection: function($el, callback) {
          var val = $el.val();
          if (val === '') {
            return;
          }
          var idsNeeded = _.difference(val.split(','), _.pluck(staticItems, 'id')),
            existing = _.filter(staticItems, function(item) {
              return _.includes(val.split(','), item.id);
            });
          // If we already have the data, just return it
          if (!idsNeeded.length) {
            callback(multiple ? existing : existing[0]);
          } else {
            var params = $.extend({}, getApiParams(), {ids: idsNeeded});
            CRM.api4(entityName, 'autocomplete', params).then(function (result) {
              callback(multiple ? result.concat(existing) : result[0]);
            });
          }
        },
        formatInputTooShort: function() {
          let html = _.escape($.fn.select2.defaults.formatInputTooShort.call(this));
          html += renderStaticOptionMarkup(staticItems);
          html += renderQuickAddMarkup(quickAddLinks);
          return html;
        },
        formatNoMatches: function() {
          let html = _.escape($.fn.select2.defaults.formatNoMatches);
          html += renderQuickAddMarkup(quickAddLinks);
          return html;
        }
      }, select2Options));

      $el.on('select2-open.crmEntity', function(){
        var $el = $(this);
        $('#select2-drop')
          .off('.crmEntity')
          // Add static item to selection when clicking static links
          .on('click.crmEntity', '.crm-entityref-links-static a', function() {
            let id = $(this).attr('href').substring(1),
              item = _.findWhere(staticItems, {id: id});
            $el.select2('close');
            if (multiple) {
              var selection = $el.select2('data');
              if (!_.findWhere(selection, {id: id})) {
                selection.push(item);
                $el.select2('data', selection, true);
              }
            } else {
              $el.select2('data', item, true);
            }
            return false;
          })
          // Pop-up Afform when clicking quick-add links
          .on('click.crmEntity', '.crm-entityref-quick-add a', function() {
            let url = $(this).attr('href');
            $el.select2('close');
            CRM.loadForm(url).on('crmFormSuccess', (e, data) => {
              // Quick-add Afform has been submitted, parse submission data for id of created entity
              const response = data.submissionResponse && data.submissionResponse[0];
              let createdId;
              if (typeof response === 'object') {
                let key = getApiParams().key || 'id';
                // Loop through entities created by the afform (there should be only one)
                Object.keys(response).forEach((entity) => {
                  if (Array.isArray(response[entity]) && response[entity][0] && response[entity][0][key]) {
                    createdId = response[entity][0][key];
                  }
                });
              }
              // Update field value with new id and the widget will automatically fetch the label
              if (createdId) {
                if (multiple && $el.val()) {
                  // Select2 v3 uses a string instead of array for multiple values
                  $el.val($el.val() + ',' + createdId).change();
                } else {
                  $el.val('' + createdId).change();
                }
              }
            });
            return false;
          });
      });
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
          .removeClass('crm-form-entityref crm-' + _.kebabCase(entity) + '-ref')
          .crmSelect2('destroy');
      });
    }
    options = options || {};
    options.select = options.select || {};
    return $(this).each(function() {
      var
        $el = $(this).off('.crmEntity'),
        entity = options.entity || $el.data('api-entity') || 'Contact',
        selectParams = {};
      // Legacy: fix entity name if passed in as snake case
      if (entity.charAt(0).toUpperCase() !== entity.charAt(0)) {
        entity = _.capitalize(_.camelCase(entity));
      }
      $el.data('api-entity', entity);
      $el.data('select-params', $.extend({}, $el.data('select-params') || {}, options.select));
      $el.data('api-params', $.extend(true, {}, $el.data('api-params') || {}, options.api));
      $el.data('create-links', options.create || $el.data('create-links'));

      $el.addClass('crm-form-entityref crm-' + _.kebabCase(entity) + '-ref');
      var settings = {
        // Use select2 ajax helper instead of CRM.api3 because it provides more value
        ajax: {
          url: CRM.url('civicrm/ajax/rest'),
          quietMillis: 300,
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
        formatSelection: formatEntityRefSelection,
        escapeMarkup: _.identity,
        initSelection: function($el, callback) {
          var
            multiple = !!$el.data('select-params').multiple,
            val = $el.val(),
            stored = $el.data('entity-value') || [];
          if (val === '') {
            return;
          }
          var idsNeeded = _.difference(val.split(','), _.pluck(stored, 'id'));
          var existing = _.remove(stored, function(item) {
            return _.includes(val.split(','), item.id);
          });
          // If we already have this data, just return it
          if (!idsNeeded.length) {
            callback(multiple ? existing : existing[0]);
          } else {
            var params = $.extend({}, $el.data('api-params') || {}, {id: idsNeeded.join(',')});
            CRM.api3($el.data('api-entity'), 'getlist', params).done(function(result) {
              callback(multiple ? result.values.concat(existing) : result.values[0]);
              // Trigger change (store data to avoid an infinite loop of lookups)
              $el.data('entity-value', result.values).trigger('change');
            });
          }
        }
      };
      // Create new items inline - works for tags
      if ($el.data('create-links') && entity === 'Tag') {
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
          var txt = _.escape($el.data('select-params').formatInputTooShort || $.fn.select2.defaults.formatInputTooShort.call(this));
          txt += entityRefFiltersMarkup($el) + renderEntityRefCreateLinks($el);
          return txt;
        };
        selectParams.formatNoMatches = function() {
          var txt = _.escape($el.data('select-params').formatNoMatches || $.fn.select2.defaults.formatNoMatches);
          txt += entityRefFiltersMarkup($el) + renderEntityRefCreateLinks($el);
          return txt;
        };
        $el.on('select2-open.crmEntity', function() {
          var $el = $(this);
          $('#select2-drop')
            .off('.crmEntity')
            .on('click.crmEntity', 'a.crm-add-entity', function(e) {
              var extra = $el.data('api-params').extra,
                formUrl = $(this).attr('href') + '&returnExtra=display_name,sort_name' + (extra ? (',' + extra) : '');
              $el.select2('close');
              CRM.loadForm(formUrl, {
                dialog: {width: '50%', height: 220}
              }).on('crmFormSuccess', function(e, data) {
                if (data.status === 'success' && data.id) {
                  if (!data.crmMessages) {
                    CRM.status(ts('%1 Created', {1: data.label || data.extra.display_name}));
                  }
                  data.label = data.label || data.extra.sort_name;
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
              if (filter.value && $(this).is('select')) {
                // Once a filter has been chosen, rerender create links and refocus the search box
                $el.select2('close');
                $el.select2('open');
              } else {
                $('.crm-entityref-links-create', '#select2-drop').replaceWith(renderEntityRefCreateLinks($el));
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

  CRM.utils.copyAttributes = function ($source, $target, attributes) {
    _.each(attributes, function(name) {
      if ($source.attr(name) !== undefined) {
        $target.attr(name, $source.attr(name));
      }
    });
  };

  CRM.utils.formatSelect2Result = function (row) {
    var markup = '<div class="crm-select2-row">';
    if (row.image !== undefined) {
      markup += '<div class="crm-select2-image"><img src="' + _.escape(row.image) + '"/></div>';
    }
    else if (row.icon_class) {
      markup += '<div class="crm-select2-icon"><div class="crm-icon ' + _.escape(row.icon_class) + '-icon"></div></div>';
    }
    markup += '<div><div class="crm-select2-row-label ' + _.escape(row.label_class || '') + '">' +
      (row.color ? '<span class="crm-select-item-color" style="background-color: ' + _.escape(row.color) + '"></span> ' : '') +
      (row.icon ? '<i class="crm-i ' + _.escape(row.icon) + '" aria-hidden="true"></i> ' : '') +
      _.escape((row.prefix !== undefined ? row.prefix + ' ' : '') + row.label + (row.suffix !== undefined ? ' ' + row.suffix : '')) +
      '</div>' +
      '<div class="crm-select2-row-description">';
    $.each(row.description || [], function(k, text) {
      markup += '<p>' + _.escape(text) + '</p> ';
    });
    markup += '</div></div></div>';
    return markup;
  };

  function formatEntityRefSelection(row) {
    return (row.color ? '<span class="crm-select-item-color" style="background-color: ' + _.escape(row.color) + '"></span> ' : '') +
      _.escape((row.prefix !== undefined ? row.prefix + ' ' : '') + row.label + (row.suffix !== undefined ? ' ' + row.suffix : ''));
  }

  function renderEntityRefCreateLinks($el) {
    var
      createLinks = $el.data('create-links'),
      params = getEntityRefApiParams($el).params,
      entity = $el.data('api-entity'),
      markup = '<div class="crm-entityref-links crm-entityref-links-create">';
    if (!createLinks || (createLinks === true && !CRM.config.entityRef.links[entity])) {
      return '';
    }
    if (createLinks === true) {
      if (!params.contact_type) {
        createLinks = CRM.config.entityRef.links[entity];
      }
      else if (typeof params.contact_type === 'string') {
        createLinks = _.where(CRM.config.entityRef.links[entity], {type: params.contact_type});
      } else {
        // lets assume it's an array with filters such as IN etc
        createLinks = [];
        _.each(params.contact_type, function(types) {
          _.each(types, function(type) {
            createLinks.push(_.findWhere(CRM.config.entityRef.links[entity], {type: type}));
          });
        });
      }
    }
    _.each(createLinks, function(link) {
      markup += ' <a class="crm-add-entity crm-hover-button" href="' + _.escape(link.url) + '">' +
        '<i class="crm-i ' + _.escape(link.icon || 'fa-plus-circle') + '" aria-hidden="true"></i> ' +
        _.escape(link.label) + '</a>';
    });
    markup += '</div>';
    return markup;
  }

  function getEntityRefFilters($el) {
    var
      entity = $el.data('api-entity'),
      filters = CRM.config.entityRef.filters[entity] || [],
      params = $.extend({params: {}}, $el.data('api-params') || {}).params,
      result = [];
    _.each(filters, function(filter) {
      _.defaults(filter, {type: 'select', 'attributes': {}, entity: entity});
      if (!params[filter.key]) {
        // Filter out options if params don't match its condition
        if (filter.condition && !_.isMatch(params, _.pick(filter.condition, _.keys(params)))) {
          return;
        }
        result.push(filter);
      }
      else if (filter.key == 'contact_type' && typeof params.contact_sub_type === 'undefined') {
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
      '<option value="">' + _.escape(ts('Refine search...')) + '</option>' +
      CRM.utils.renderOptions(filters, filter.key) +
      '</select>' + entityRefFilterValueMarkup($el, filter, filterSpec) + '</div>';
    return markup;
  }

  /**
   * Provide markup for entity ref filter value field
   */
  function entityRefFilterValueMarkup($el, filter, filterSpec) {
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
        var fieldName = _.last(filter.key.split('.')),
          options = [{key: '', value: ts('- select -')}];
        if (filterSpec.options) {
          options = options.concat(getEntityRefFilterOptions(fieldName, $el, filterSpec));
        }
        markup = '<select' + attrs + '>' + CRM.utils.renderOptions(options, filter.value) + '</select>';
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
      $valField = $(entityRefFilterValueMarkup($el, filter, filterSpec));
      $keyField.after($valField);
      if (filterSpec.type === 'select') {
        loadEntityRefFilterOptions(filter, filterSpec, $valField, $el);
      }
    } else {
      $('.crm-entityref-filter-value', '#select2-drop').hide().val('').change();
    }
  }

  /**
   * Fetch options for a filter from cache or ajax api
   */
  function loadEntityRefFilterOptions(filter, filterSpec, $valField, $el) {
    // Fieldname may be prefixed with joins - strip those out
    var fieldName = _.last(filter.key.split('.'));
    if (filterSpec.options) {
      CRM.utils.setOptions($valField, getEntityRefFilterOptions(fieldName, $el, filterSpec), false, filter.value);
      return;
    }
    $('.crm-entityref-filters select', '#select2-drop').prop('disabled', true);
    CRM.api3(filterSpec.entity, 'getoptions', {field: fieldName, context: 'search', sequential: 1})
      .done(function(result) {
        var entity = $el.data('api-entity').toLowerCase();
        // Store options globally so we don't have to look them up again
        filterSpec.options = result.values;
        $('.crm-entityref-filters select', '#select2-drop').prop('disabled', false);
        CRM.utils.setOptions($valField, getEntityRefFilterOptions(fieldName, $el, filterSpec), false, filter.value);
      });
  }

  function getEntityRefFilterOptions(fieldName, $el, filterSpec) {
    var values = _.cloneDeep(filterSpec.options),
      params = $.extend({params: {}}, $el.data('api-params') || {}).params;
    if (fieldName === 'contact_type' && params.contact_type) {
      values = _.remove(values, function(option) {
        return option.key.indexOf(params.contact_type + '__') === 0;
      });
    }
    return values;
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
      var validator = $(this).validate();
      var that = this;
      validator.settings = $.extend({}, validator.settings, CRM.validate._defaults, CRM.validate.params);
      // Call our custom validation handler.
      $(validator.currentForm).on("invalid-form.validate", validator.settings.invalidHandler);
      // Call any post-initialization callbacks
      if (CRM.validate.functions && CRM.validate.functions.length) {
        $.each(CRM.validate.functions, function(i, func) {
          func.call(that);
        });
      }
    });
  };

  // Submit-once
  var submitted = [],
    submitButton;
  function submitOnceForm(e) {
    if (e.isDefaultPrevented()) {
      return;
    }
    if (_.contains(submitted, e.target)) {
      return false;
    }
    submitted.push(e.target);
    // Spin submit button icon
    if (submitButton && $(submitButton, e.target).length) {
      // Dialog button
      if ($(e.target).closest('.ui-dialog .crm-ajax-container')) {
        var identifier = $(submitButton).attr('name') || $(submitButton).attr('href');
        if (identifier) {
          submitButton = $(e.target).closest('.ui-dialog').find('button[data-identifier="' + identifier + '"]')[0] || submitButton;
        }
      }
      var $icon = $(submitButton).siblings('.crm-i').add('.crm-i, .ui-icon', submitButton);
      $icon.data('origClass', $icon.attr('class')).removeClass().addClass('crm-i crm-submit-icon fa-spinner fa-pulse');
    }
  }

  // If form fails validation, restore button icon and reset the submitted array
  function submitFormInvalid(form) {
    submitted = [];
    $('.crm-i.crm-submit-icon').each(function() {
      if ($(this).data('origClass')) {
        $(this).removeClass().addClass($(this).data('origClass'));
      }
    });
  }

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
      $('.crm-sortable-list', e.target).sortable();
      $('table.crm-sortable', e.target).DataTable();
      $('table.crm-ajax-table', e.target).each(function() {
        var
          $table = $(this),
          script = CRM.config.resourceBase + 'js/jquery/jquery.crmAjaxTable.js';
        CRM.loadScript(script).done(function() {
          $table.crmAjaxTable();
        });
      });
      if ($("input:radio[name=radio_ts]").length == 1) {
        $("input:radio[name=radio_ts]").prop("checked", true);
      }
      $('.crm-select2:not(.select2-offscreen, .select2-container)', e.target).crmSelect2();
      $('.crm-form-entityref:not(.select2-offscreen, .select2-container)', e.target).crmEntityRef();
      $('.crm-form-autocomplete:not(.select2-offscreen, .select2-container)[data-api-entity]', e.target).each(function() {
        $(this).crmAutocomplete($(this).data('apiEntity'), $(this).data('apiParams'), $(this).data('selectParams'));
      });
      $('select.crm-chain-select-control', e.target).off('.chainSelect').on('change.chainSelect', chainSelect);
      $('.crm-form-text[data-crm-datepicker]', e.target).each(function() {
        $(this).crmDatepicker($(this).data('crmDatepicker'));
      });
      $('.crm-editable', e.target).not('thead *').each(function() {
        var $el = $(this);
        CRM.loadScript(CRM.config.resourceBase + 'js/jquery/jquery.crmEditable.js').done(function() {
          $el.crmEditable();
        });
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
      // Submit once handlers
      $('form[data-submit-once]', e.target)
        .submit(submitOnceForm)
        .on('invalid-form', submitFormInvalid);
      $('form[data-submit-once] button[type=submit]', e.target).click(function(e) {
        submitButton = e.target;
      });
    })
    .on('dialogopen', function(e) {
      var $el = $(e.target);
      $('body').addClass('ui-dialog-open');
      // Modal dialogs should disable scrollbars
      if ($el.dialog('option', 'modal')) {
        $el.addClass('modal-dialog');
        $('body').css({overflow: 'hidden'});
      }
      $el.parent().find('.ui-dialog-titlebar .ui-icon-closethick').removeClass('ui-icon-closethick').addClass('fa-times');
      // Add resize button
      if ($el.parent().hasClass('crm-container') && $el.dialog('option', 'resizable')) {
        $el.parent().find('.ui-dialog-titlebar').append($('<button class="crm-dialog-titlebar-resize ui-dialog-titlebar-close" title="'+ _.escape(ts('Toggle fullscreen'))+'" style="right:2em;"/>').button({icons: {primary: 'fa-expand'}, text: false}));
        $('.crm-dialog-titlebar-resize', $el.parent()).click(function(e) {
          if ($el.data('origSize')) {
            $el.dialog('option', $el.data('origSize'));
            $el.data('origSize', null);
            $(this).button('option', 'icons', {primary: 'fa-expand'});
          } else {
            var menuHeight = $('#civicrm-menu').outerHeight();
            if ($('body').hasClass('crm-menubar-below-cms-menu')) {
              menuHeight += $('#civicrm-menu').offset().top;
            }
            $el.data('origSize', {
              position: {my: 'center', at: 'center center+' + (menuHeight / 2), of: window},
              width: $el.dialog('option', 'width'),
              height: $el.dialog('option', 'height')
            });
            $el.dialog('option', {width: '100%', height: ($(window).height() - menuHeight), position: {my: "top", at: "top+"+menuHeight, of: window}});
            $(this).button('option', 'icons', {primary: 'fa-compress'});
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
      if ($('.ui-dialog-content:visible').not(e.target).length < 1) {
        $('body').removeClass('ui-dialog-open');
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
    var TOOLTIP_HIDE_DELAY = 300;

    $(document)
      .on('mouseover', 'a.crm-summary-link:not(.crm-processed)', function (e) {
        $(this).addClass('crm-processed crm-tooltip-active');
        var topDistance = e.pageY - $(window).scrollTop();
        if (topDistance < 300 || topDistance < $(this).children('.crm-tooltip-wrapper').height()) {
          $(this).addClass('crm-tooltip-down');
        }
        if (!$(this).children('.crm-tooltip-wrapper').length) {
          var tooltipContents = $(this)[0].hasAttribute('data-tooltip-url') ? $(this).attr('data-tooltip-url') : this.href;
          $(this).append('<div class="crm-tooltip-wrapper"><div class="crm-tooltip"></div></div>');
          $(this).children().children('.crm-tooltip')
            .html('<div class="crm-loading-element"></div>')
            .load(tooltipContents);
        }
      })
      .on('mouseleave', 'a.crm-summary-link', function () {
        var tooltipLink = $(this);
        setTimeout(function () {
          if (tooltipLink.filter(':hover').length === 0) {
            tooltipLink.removeClass('crm-processed crm-tooltip-active crm-tooltip-down');
          }
        }, TOOLTIP_HIDE_DELAY);
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
   * @see https://docs.civicrm.org/dev/en/latest/framework/ui/#notifications-and-confirmations
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
    var $msg = $('<div class="crm-status-box-outer status-start"><div class="crm-status-box-inner"><div class="crm-status-box-msg">' + _.escape(opts.start) + '</div></div></div>')
      .appendTo('body');
    $msg.css('min-width', $msg.width());
    function handle(status, data) {
      var endMsg = typeof(opts[status]) === 'function' ? opts[status](data) : opts[status];
      if (endMsg) {
        $msg.removeClass('status-start').addClass('status-' + status).find('.crm-status-box-msg').text(endMsg);
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
   * @see https://docs.civicrm.org/dev/en/latest/framework/ui/#notifications-and-confirmations
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
      options.expires = (options.expires === false || !CRM.config.allowAlertAutodismissal) ? 0 : parseInt(options.expires, 10);
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
      // strip html tags as they are not parsed in standard alerts
      alert($("<div/>").html(text).text());
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
   * @see https://docs.civicrm.org/dev/en/latest/framework/ui/#notifications-and-confirmations
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
   * @see https://docs.civicrm.org/dev/en/latest/framework/ui/#notifications-and-confirmations
   */
  $.fn.crmError = function (text, title, options) {
    title = title || '';
    text = text || '';
    options = options || {};

    var extra = {
      expires: 0
    }, label;
    if ($(this).length) {
      if (title === '') {
        label = $('label[for="' + $(this).attr('name') + '"], label[for="' + $(this).attr('id') + '"]').not('[generated=true]');
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
          ele.removeClass('crm-error');
          if (label) {
            label.removeClass('crm-error');
          }
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
        $(this).siblings('.crm-multiple-checkbox-radio-options').find('.crm-form-radio:checked').prop('checked', false).trigger('change', ['crmClear']);
        $(this).siblings('input:text').val('').trigger('change', ['crmClear']);
        return false;
      })
      .on('change keyup', 'input.crm-form-radio:checked, input[allowclear=1]', function(e, context) {
        if (context !== 'crmClear' && ($(this).is(':checked') || ($(this).is('[allowclear=1]') && $(this).val()))) {
          $(this).siblings('.crm-clear-link').css({visibility: ''});
          $(this).closest('.crm-multiple-checkbox-radio-options').siblings('.crm-clear-link').css({visibility: ''});
        }
        if (context !== 'crmClear' && $(this).is('[allowclear=1]') && $(this).val() === '') {
          $(this).siblings('.crm-clear-link').css({visibility: 'hidden'});
          $(this).closest('.crm-multiple-checkbox-radio-options').siblings('.crm-clear-link').css({visibility: 'hidden'});
        }
      })

      // Allow normal clicking of links within accordions
      .on('click.crmAccordions', 'div.crm-accordion-header a, .collapsible-title a', function (e) {
        e.stopPropagation();
      })
      // Handle accordions
      .on('click.crmAccordions', 'div.crm-accordion-header, fieldset.crm-accordion-header, .crm-collapsible .collapsible-title', function (e) {
        if ($(this).parent().hasClass('collapsed')) {
          $(this).next().css('display', 'none').slideDown(200);
        }
        else {
          $(this).next().css('display', 'block').slideUp(200);
        }
        $(this).parent().toggleClass('collapsed');
        e.preventDefault();
      });

    $().crmtooltip();
  });

  /**
   * Collapse or expand an accordion
   * @deprecated
   * @param speed
   */
  $.fn.crmAccordionToggle = function (speed) {
    $(this).each(function () {
      // Backward-compat, for when this older function is used on a newer <details> element
      if ($(this).is('details')) {
        this.open = !this.open;
        return;
      }
      if ($(this).hasClass('collapsed')) {
        $('.crm-accordion-body', this).first().css('display', 'none').slideDown(speed);
      }
      else {
        $('.crm-accordion-body', this).first().css('display', 'block').slideUp(speed);
      }
      $(this).toggleClass('collapsed');
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
    var precision, decimal, separator, sign, i, j, result;
    if (value === 'init' && format) {
      currencyTemplate = format;
      return;
    }
    format = format || currencyTemplate;
    if ((result = /1(.?)234(.?)56/.exec(format)) !== null) { // If value is formatted to 2 decimals
      precision = 2;
    }
    else if ((result = /1(.?)234(.?)6/.exec(format)) !== null) { // If value is formatted to 1 decimal
      precision = 1;
    }
    else if ((result = /1(.?)235/.exec(format)) !== null) { // If value is formatted to zero decimals
      precision = false;
    }
    else {
      return 'Invalid format passed to CRM.formatMoney';
    }
    separator = result[1];
    decimal = precision ? result[2] : false;
    sign = (value < 0) ? '-' : '';
    //extracting the absolute value of the integer part of the number and converting to string
    i = parseInt(value = Math.abs(value).toFixed(2)) + '';
    j = ((j = i.length) > 3) ? j % 3 : 0;
    result = sign + (j ? i.substr(0, j) + separator : '') + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + separator) + (precision ? decimal + Math.abs(value - i).toFixed(precision).slice(2) : '');
    if (onlyNumber) {
      return result;
    }
    switch (precision) {
      case 2:
        return format.replace(/1.*234.*56/, result);
      case 1:
        return format.replace(/1.*234.*6/, result);
      case false:
        return format.replace(/1.*235/, result);
    }
  };

  CRM.angRequires = function(name) {
    return CRM.angular.requires[name] || [];
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

  // Sugar methods for window.localStorage, with a fallback for older browsers
  var cacheItems = {};
  CRM.cache = {
    get: function (name, defaultValue) {
      try {
        if (localStorage.getItem('CRM' + name) !== null) {
          return JSON.parse(localStorage.getItem('CRM' + name));
        }
      } catch(e) {}
      return cacheItems[name] === undefined ? defaultValue : cacheItems[name];
    },
    set: function (name, value) {
      try {
        localStorage.setItem('CRM' + name, JSON.stringify(value));
      } catch(e) {}
      cacheItems[name] = value;
    },
    clear: function(name) {
      try {
        localStorage.removeItem('CRM' + name);
      } catch(e) {}
      delete cacheItems[name];
    }
  };



  // Determine if a user has a given permission.
  // @see CRM_Core_Resources::addPermissions
  CRM.checkPerm = function(perm) {
    return CRM.permissions && CRM.permissions[perm];
  };

  // Round while preserving sigfigs
  CRM.utils.sigfig = function(n, digits) {
    var len = ("" + n).length;
    var scale = Math.pow(10.0, len-digits);
    return Math.round(n / scale) * scale;
  };

  /**
   * Create a js Date object from a unix timestamp or a yyyy-mm-dd string
   * @param input
   * @returns {Date}
   */
  CRM.utils.makeDate = function(input) {
    switch (typeof input) {
      case 'object':
        // already a date object
        return input;

      case 'string':
        // convert iso format with or without dashes
        input = input.replace(/[- :]/g, '');
        var output = $.datepicker.parseDate('yymmdd', input.substr(0, 8));
        if (input.length === 14) {
          output.setHours(
            parseInt(input.substr(8, 2), 10),
            parseInt(input.substr(10, 2), 10),
            parseInt(input.substr(12, 2), 10)
          );
        }
        return output;

      case 'number':
        // convert unix timestamp
        return new Date(input * 1000);
    }
    throw 'Invalid input passed to CRM.utils.makeDate';
  };

  /**
   * Format a date (and optionally time) for output to the user
   *
   * @param {string|int|Date} input
   *   Input may be a js Date object, a unix timestamp or a 'yyyy-mm-dd' string
   * @param {string|null} dateFormat
   *   A string like 'yy-mm-dd' or null to use the system default
   * @param {int|bool} timeFormat
   *   Leave empty to omit time from the output (default)
   *   Or pass 12, 24, or true to use the system default for 12/24hr format
   * @returns {string}
   */
  CRM.utils.formatDate = function(input, dateFormat, timeFormat) {
    if (!input) {
      return '';
    }
    var date = CRM.utils.makeDate(input),
      output = $.datepicker.formatDate(dateFormat || CRM.config.dateInputFormat, date);
    if (timeFormat) {
      var hour = date.getHours(),
        min = date.getMinutes(),
        suf = '';
      if (timeFormat === 12 || (timeFormat === true && !CRM.config.timeIs24Hr)) {
        suf = ' ' + (hour < 12 ? ts('AM') : ts('PM'));
        if (hour === 0 || hour > 12) {
          hour = Math.abs(hour - 12);
        }
      } else if (hour < 10) {
        hour = '0' + hour;
      }
      output += ' ' + hour + ':' + (min < 10 ? '0' : '') + min + suf;
    }
    return output;
  };

  // Used to set appropriate text color for a given background
  CRM.utils.colorContrast = function (hexcolor) {
    hexcolor = hexcolor.replace(/[ #]/g, '');
    var r = parseInt(hexcolor.substr(0, 2), 16),
     g = parseInt(hexcolor.substr(2, 2), 16),
     b = parseInt(hexcolor.substr(4, 2), 16),
     yiq = ((r * 299) + (g * 587) + (b * 114)) / 1000;
    return (yiq >= 128) ? 'black' : 'white';
  };

  const ALPHANUMERIC = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
  CRM.utils.createRandom = function (length, charset) {
    charset = charset || ALPHANUMERIC;
    let result = '';
    const chars = charset.length;
    for (let i = 0; i < length; i++) {
      result += charset.charAt(Math.floor(Math.random() * chars));
    }
    return result;
  };

  // Port of CRM_Utils_String::munge()
  CRM.utils.munge = function (name, char = '_', len = 63) {
    name = name.trim().replace(/[^a-zA-Z0-9]+/g, char);
    if (!name.replace(/_/, '').length) {
      name = CRM.utils.createRandom(len, ALPHANUMERIC);
    }
    return len ? name.substring(0, len) : name;
  };

  // CVE-2015-9251 - Prevent auto-execution of scripts when no explicit dataType was provided
  $.ajaxPrefilter(function(s) {
    if (s.crossDomain) {
      s.contents.script = false;
    }
  });

  // CVE-2020-11022 and CVE-2020-11023  Passing HTML from untrusted sources - even after sanitizing it - to one of jQuery's DOM manipulation methods (i.e. .html(), .append(), and others) may execute untrusted code.
  $.htmlPrefilter = function(html) {
    // Prior to jQuery 3.5, jQuery converted XHTML-style self-closing tags to
    // their XML equivalent: e.g., "<div />" to "<div></div>". This is
    // problematic for several reasons, including that it's vulnerable to XSS
    // attacks. However, since this was jQuery's behavior for many years, many
    // Drupal modules and jQuery plugins may be relying on it. Therefore, we
    // preserve that behavior, but for a limited set of tags only, that we believe
    // to not be vulnerable. This is the set of HTML tags that satisfy all of the
    // following conditions:
    // - In DOMPurify's list of HTML tags. If an HTML tag isn't safe enough to
    //   appear in that list, then we don't want to mess with it here either.
    //   @see https://github.com/cure53/DOMPurify/blob/2.0.11/dist/purify.js#L128
    // - A normal element (not a void, template, text, or foreign element).
    //   @see https://html.spec.whatwg.org/multipage/syntax.html#elements-2
    // - An element that is still defined by the current HTML specification
    //   (not a deprecated element), because we do not want to rely on how
    //   browsers parse deprecated elements.
    //   @see https://developer.mozilla.org/en-US/docs/Web/HTML/Element
    // - Not 'html', 'head', or 'body', because this pseudo-XHTML expansion is
    //   designed for fragments, not entire documents.
    // - Not 'colgroup', because due to an idiosyncrasy of jQuery's original
    //   regular expression, it didn't match on colgroup, and we don't want to
    //   introduce a behavior change for that.
    var selfClosingTagsToReplace = [
      'a', 'abbr', 'address', 'article', 'aside', 'audio', 'b', 'bdi', 'bdo',
      'blockquote', 'button', 'canvas', 'caption', 'cite', 'code', 'data',
      'datalist', 'dd', 'del', 'details', 'dfn', 'div', 'dl', 'dt', 'em',
      'fieldset', 'figcaption', 'figure', 'footer', 'form', 'h1', 'h2', 'h3',
      'h4', 'h5', 'h6', 'header', 'hgroup', 'i', 'ins', 'kbd', 'label', 'legend',
      'li', 'main', 'map', 'mark', 'menu', 'meter', 'nav', 'ol', 'optgroup',
      'option', 'output', 'p', 'picture', 'pre', 'progress', 'q', 'rp', 'rt',
      'ruby', 's', 'samp', 'section', 'select', 'small', 'source', 'span',
      'strong', 'sub', 'summary', 'sup', 'table', 'tbody', 'td', 'tfoot', 'th',
      'thead', 'time', 'tr', 'u', 'ul', 'var', 'video'
    ];

    // Define regular expressions for <TAG/> and <TAG ATTRIBUTES/>. Doing this as
    // two expressions makes it easier to target <a/> without also targeting
    // every tag that starts with "a".
    var xhtmlRegExpGroup = '(' + selfClosingTagsToReplace.join('|') + ')';
    var whitespace = '[\\x20\\t\\r\\n\\f]';
    var rxhtmlTagWithoutSpaceOrAttributes = new RegExp('<' + xhtmlRegExpGroup + '\\/>', 'gi');
    var rxhtmlTagWithSpaceAndMaybeAttributes = new RegExp('<' + xhtmlRegExpGroup + '(' + whitespace + '[^>]*)\\/>', 'gi');

    // jQuery 3.5 also fixed a vulnerability for when </select> appears within
    // an <option> or <optgroup>, but it did that in local code that we can't
    // backport directly. Instead, we filter such cases out. To do so, we need to
    // determine when jQuery would otherwise invoke the vulnerable code, which it
    // uses this regular expression to determine. The regular expression changed
    // for version 3.0.0 and changed again for 3.4.0.
    // @see https://github.com/jquery/jquery/blob/1.5/jquery.js#L4958
    // @see https://github.com/jquery/jquery/blob/3.0.0/dist/jquery.js#L4584
    // @see https://github.com/jquery/jquery/blob/3.4.0/dist/jquery.js#L4712
    var rtagName = /<([\w:]+)/;

    // The regular expression that jQuery uses to determine which self-closing
    // tags to expand to open and close tags. This is vulnerable, because it
    // matches all tag names except the few excluded ones. We only use this
    // expression for determining vulnerability. The expression changed for
    // version 3, but we only need to check for vulnerability in versions 1 and 2,
    // so we use the expression from those versions.
    // @see https://github.com/jquery/jquery/blob/1.5/jquery.js#L4957
    var rxhtmlTag = /<(?!area|br|col|embed|hr|img|input|link|meta|param)(([\w:]+)[^>]*)\/>/gi;

    // This is how jQuery determines the first tag in the HTML.
    // @see https://github.com/jquery/jquery/blob/1.5/jquery.js#L5521
    var tag = ( rtagName.exec( html ) || [ "", "" ] )[ 1 ].toLowerCase();

    // It is not valid HTML for <option> or <optgroup> to have <select> as
    // either a descendant or sibling, and attempts to inject one can cause
    // XSS on jQuery versions before 3.5. Since this is invalid HTML and a
    // possible XSS attack, reject the entire string.
    // @see https://cve.mitre.org/cgi-bin/cvename.cgi?name=CVE-2020-11023
    if ((tag === 'option' || tag === 'optgroup') && html.match(/<\/?select/i)) {
      html = '';
    }

    // Retain jQuery's prior to 3.5 conversion of pseudo-XHTML, but for only
    // the tags in the `selfClosingTagsToReplace` list defined above.
    // @see https://github.com/jquery/jquery/blob/1.5/jquery.js#L5518
    // @see https://cve.mitre.org/cgi-bin/cvename.cgi?name=CVE-2020-11022
    html = html.replace(rxhtmlTagWithoutSpaceOrAttributes, "<$1></$1>");
    html = html.replace(rxhtmlTagWithSpaceAndMaybeAttributes, "<$1$2></$1>");

    return html;
  };

})(jQuery, _);
