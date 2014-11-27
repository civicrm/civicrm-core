// https://civicrm.org/licensing
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
  text = CRM.strings[text] || text;
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
    var elementType = 'block';
  }

  /* This loop is used to display the blocks whose IDs are present within the showBlocks array */
  for (var i = 0; i < showBlocks.length; i++) {
    var myElement = document.getElementById(showBlocks[i]);
    /* getElementById returns null if element id doesn't exist in the document */
    if (myElement != null) {
      myElement.style.display = elementType;
    }
    else {
      alert('showBlocks array item not in .tpl = ' + showBlocks[i]);
    }
  }

  /* This loop is used to hide the blocks whose IDs are present within the hideBlocks array */
  for (var i = 0; i < hideBlocks.length; i++) {
    var myElement = document.getElementById(hideBlocks[i]);
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

  if (field_type == 'select') {
    var trigger = trigger_value.split("|");
    var selectedOptionValue = cj('#' + trigger_field_id).val();

    var target = target_element_id.split("|");
    for (var j = 0; j < target.length; j++) {
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
      var target = target_element_id.split("|");
      for (var j = 0; j < target.length; j++) {
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
    obj.value = procText + " ...";
  }
  cj(obj).closest('form').attr('data-warn-changes', 'false');
  if (document.getElementById) { // disable submit button for newer browsers
    obj.disabled = true;
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

CRM.utils = CRM.utils || {};
CRM.strings = CRM.strings || {};

(function ($, _, undefined) {
  "use strict";

  // Theme classes for unattached elements
  $.fn.select2.defaults.dropdownCssClass = $.ui.dialog.prototype.options.dialogClass = 'crm-container';

  // https://github.com/ivaynberg/select2/pull/2090
  $.fn.select2.defaults.width = 'resolve';

  // Workaround for https://github.com/ivaynberg/select2/issues/1246
  $.ui.dialog.prototype._allowInteraction = function(e) {
    return !!$(e.target).closest('.ui-dialog, .ui-datepicker, .select2-drop, .cke_dialog').length;
  };

  /**
   * Populate a select list, overwriting the existing options except for the placeholder.
   * @param select jquery selector - 1 or more select elements
   * @param options array in format returned by api.getoptions
   * @param placeholder string
   */
  CRM.utils.setOptions = function(select, options, placeholder) {
    $(select).each(function() {
      var
        $elect = $(this),
        val = $elect.val() || [],
        opts = placeholder || placeholder === '' ? '' : '[value!=""]',
        newOptions = '',
        theme = function(options) {
          _.each(options, function(option) {
            if (option.children) {
              newOptions += '<optgroup label="' + option.value + '">';
              theme(option.children);
              newOptions += '</optgroup>';
            } else {
              var selected = ($.inArray('' + option.key, val) > -1) ? 'selected="selected"' : '';
              newOptions += '<option value="' + option.key + '"' + selected + '>' + option.value + '</option>';
            }
          });
        };
      if (!$.isArray(val)) {
        val = [val];
      }
      $elect.find('option' + opts).remove();
      theme(options);
      if (typeof placeholder === 'string') {
        if ($elect.is('[multiple]')) {
          select.attr('placeholder', placeholder);
        } else {
          newOptions = '<option value="">' + placeholder + '</option>' + newOptions;
        }
      }
      $elect.append(newOptions);
      $elect.trigger('crmOptionsUpdated', $.extend({}, options)).trigger('change');
    });
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
    $(':input:visible, .select2-container:visible+:input.select2-offscreen', el).not('[type=submit], [type=button], .crm-action-menu').each(function () {
      var initialValue = $(this).data('crm-initial-value');
      // skip change of value for submit buttons
      if (initialValue !== undefined && !_.isEqual(initialValue, $(this).val())) {
        isDirty = true;
      }
    });
    return isDirty;
  };

  /**
   * Wrapper for select2 initialization function; supplies defaults
   * @param options object
   */
  $.fn.crmSelect2 = function(options) {
    return $(this).each(function () {
      var
        $el = $(this),
        settings = {allowClear: !$el.hasClass('required')};
      // quickform doesn't support optgroups so here's a hack :(
      $('option[value^=crm_optgroup]', this).each(function () {
        $(this).nextUntil('option[value^=crm_optgroup]').wrapAll('<optgroup label="' + $(this).text() + '" />');
        $(this).remove();
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

  /**
   * @see CRM_Core_Form::addEntityRef for docs
   * @param options object
   */
  $.fn.crmEntityRef = function(options) {
    options = options || {};
    options.select = options.select || {};
    return $(this).each(function() {
      var
        $el = $(this).off('.crmEntity'),
        entity = options.entity || $el.data('api-entity') || 'contact',
        selectParams = {};
      $el.data('api-entity', entity);
      $el.data('select-params', $.extend({}, $el.data('select-params') || {}, options.select));
      $el.data('api-params', $.extend({}, $el.data('api-params') || {}, options.api));
      $el.data('create-links', options.create || $el.data('create-links'));
      $el.addClass('crm-form-entityref crm-' + entity + '-ref');
      var settings = {
        // Use select2 ajax helper instead of CRM.api because it provides more value
        ajax: {
          url: CRM.url('civicrm/ajax/rest'),
          data: function (input, page_num) {
            var params = $el.data('api-params') || {};
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
          return row.label;
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
      if ($el.data('create-links') && entity.toLowerCase() === 'contact') {
        selectParams.formatInputTooShort = function() {
          var txt = $el.data('select-params').formatInputTooShort || $.fn.select2.defaults.formatInputTooShort.call(this);
          if ($el.data('create-links') && CRM.profileCreate && CRM.profileCreate.length) {
            txt += ' ' + ts('or') + '<br />' + formatSelect2CreateLinks($el);
          }
          return txt;
        };
        selectParams.formatNoMatches = function() {
          var txt = $el.data('select-params').formatNoMatches || $.fn.select2.defaults.formatNoMatches;
          return txt + (CRM.profileCreate ? ('<br />' + formatSelect2CreateLinks($el)) : '');
        };
        $el.on('select2-open.crmEntity', function() {
          var $el = $(this);
          $('#select2-drop').off('.crmEntity').on('click.crmEntity', 'a.crm-add-entity', function(e) {
            $el.select2('close');
            CRM.loadForm($(this).attr('href'), {
              dialog: {width: 500, height: 'auto'}
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
          });
        });
      }
      // Create new items inline - works for tags
      else if ($el.data('create-links')) {
        selectParams.createSearchChoice = function(term, data) {
          if (!_.findKey(data, {label: term})) {
            return {id: "0", term: term, label: term + ' (' + ts('new tag') + ')'};
          }
        };
        selectParams.tokenSeparators = [','];
        selectParams.createSearchChoicePosition = 'bottom';
      }
      $el.crmSelect2($.extend(settings, $el.data('select-params'), selectParams))
        .on('select2-selecting.crmEntity', function(e) {
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
    markup += '<div><div class="crm-select2-row-label '+(row.label_class || '')+'">' + row.label + '</div>';
    markup += '<div class="crm-select2-row-description">';
    $.each(row.description || [], function(k, text) {
      markup += '<p>' + text + '</p>';
    });
    markup += '</div></div></div>';
    return markup;
  };

  function formatSelect2CreateLinks($el) {
    var
      createLinks = $el.data('create-links'),
      api = $el.data('api-params') || {},
      type = api.params ? api.params.contact_type : null;
    if (createLinks === true) {
      createLinks = type ? _.where(CRM.profileCreate, {type: type}) : CRM.profileCreate;
    }
    var markup = '';
    _.each(createLinks, function(link) {
      markup += ' <a class="crm-add-entity crm-hover-button" href="' + link.url + '">';
      if (link.type) {
        markup += '<span class="icon ' + link.type + '-profile-icon"></span> ';
      }
      markup += link.label + '</a>';
    });
    return markup;
  }

  /**
   * Wrapper for jQuery validate initialization function; supplies defaults
   * @param options object
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
      if ($("input:radio[name=radio_ts]").size() == 1) {
        $("input:radio[name=radio_ts]").prop("checked", true);
      }
      $('.crm-select2:not(.select2-offscreen, .select2-container)', e.target).crmSelect2();
      $('.crm-form-entityref:not(.select2-offscreen, .select2-container)', e.target).crmEntityRef();
      $('select.crm-chain-select-control', e.target).off('.chainSelect').on('change.chainSelect', chainSelect);
      // Cache Form Input initial values
      $('form[data-warn-changes] :input', e.target).each(function() {
        $(this).data('crm-initial-value', $(this).val());
      });
    })
    .on('dialogopen', function(e) {
      var $el = $(e.target);
      // Modal dialogs should disable scrollbars
      if ($el.dialog('option', 'modal')) {
        $el.addClass('modal-dialog');
        $('body').css({overflow: 'hidden'});
      }
      // Add resize button
      if ($el.parent().hasClass('crm-container') && $el.dialog('option', 'resizable')) {
        $el.parent().find('.ui-dialog-titlebar').append($('<button class="crm-dialog-titlebar-resize ui-dialog-titlebar-close" title="'+ts('Toggle fullscreen')+'" style="right:2em;"/>').button({icons: {primary: 'ui-icon-newwin'}, text: false}));
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
    })
   ;

  // CRM-14353 - Warn of unsaved changes for forms which have opted in
  window.onbeforeunload = function() {
    if (CRM.utils.initialValueChanged($('form[data-warn-changes=true]:visible'))) {
      return ts('You have unsaved changes.');
     }
  };

  /**
   * Function to make multiselect boxes behave as fields in small screens
   */
  function advmultiselectResize() {
    var amswidth = $("#crm-container form:has(table.advmultiselect)").width();
    if (amswidth < 700) {
      $("form table.advmultiselect td").css('display', 'block');
    }
    else {
      $("form table.advmultiselect td").css('display', 'table-cell');
    }
    var contactwidth = $('#crm-container #mainTabContainer').width();
    if (contactwidth < 600) {
      $('#crm-container #mainTabContainer').addClass('narrowpage');
      $('#crm-container #mainTabContainer.narrowpage #contactTopBar td').each(function (index) {
        if (index > 1) {
          if (index % 2 == 0) {
            $(this).parent().after('<tr class="narrowadded"></tr>');
          }
          var item = $(this);
          $(this).parent().next().append(item);
        }
      });
    }
    else {
      $('#crm-container #mainTabContainer.narrowpage').removeClass('narrowpage');
      $('#crm-container #mainTabContainer #contactTopBar tr.narrowadded td').each(function () {
        var nitem = $(this);
        var parent = $(this).parent();
        $(this).parent().prev().append(nitem);
        if (parent.children().size() == 0) {
          parent.remove();
        }
      });
      $('#crm-container #mainTabContainer.narrowpage #contactTopBar tr.added').detach();
    }
    var cformwidth = $('#crm-container #Contact .contact_basic_information-section').width();

    if (cformwidth < 720) {
      $('#crm-container .contact_basic_information-section').addClass('narrowform');
      $('#crm-container .contact_basic_information-section table.form-layout-compressed td .helpicon').parent().addClass('hashelpicon');
      if (cformwidth < 480) {
        $('#crm-container .contact_basic_information-section').addClass('xnarrowform');
      }
      else {
        $('#crm-container .contact_basic_information-section.xnarrowform').removeClass('xnarrowform');
      }
    }
    else {
      $('#crm-container .contact_basic_information-section.narrowform').removeClass('narrowform');
      $('#crm-container .contact_basic_information-section.xnarrowform').removeClass('xnarrowform');
    }
  }

  advmultiselectResize();
  $(window).resize(advmultiselectResize);

  $.fn.crmtooltip = function () {
    $(document)
      .on('mouseover', 'a.crm-summary-link:not(.crm-processed)', function (e) {
        $(this).addClass('crm-processed');
        $(this).addClass('crm-tooltip-active');
        var topDistance = e.pageY - $(window).scrollTop();
        if (topDistance < 300 | topDistance < $(this).children('.crm-tooltip-wrapper').height()) {
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
        $(this).removeClass('crm-processed');
        $(this).removeClass('crm-tooltip-active crm-tooltip-down');
      })
      .on('click', 'a.crm-summary-link', false);
  };

  var helpDisplay, helpPrevious;
  CRM.help = function (title, params, url) {
    if (helpDisplay && helpDisplay.close) {
      // If the same link is clicked twice, just close the display - todo use underscore method for this comparison
      if (helpDisplay.isOpen && helpPrevious === JSON.stringify(params)) {
        helpDisplay.close();
        return;
      }
      helpDisplay.close();
    }
    helpPrevious = JSON.stringify(params);
    params.class_name = 'CRM_Core_Page_Inline_Help';
    params.type = 'page';
    helpDisplay = CRM.alert('...', title, 'crm-help crm-msg-loading', {expires: 0});
    $.ajax(url || CRM.url('civicrm/ajax/inline'),
      {
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
      }
    );
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
      error: function() {
        CRM.alert(ts('Sorry an error occurred and your information was not saved'), ts('Error'));
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
          $msg.fadeOut('slow', function() {$msg.remove()});
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
      modal: true,
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
    $.extend(settings, ($.isFunction(options) ? arguments[1] : options) || {});
    if (!settings.buttons && $.isPlainObject(settings.options)) {
      $.each(settings.options, function(op, label) {
        buttons.push({
          text: label,
          'data-op': op,
          icons: {primary: op === 'no' ? 'ui-icon-close' : 'ui-icon-check'},
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
      if (title == '') {
        var label = $('label[for="' + $(this).attr('name') + '"], label[for="' + $(this).attr('id') + '"]').not('[generated=true]');
        if (label.length) {
          label.addClass('crm-error');
          var $label = label.clone();
          if (text == '' && $('.crm-marker', $label).length > 0) {
            text = $('.crm-marker', $label).attr('title');
          }
          $('.crm-marker', $label).remove();
          title = $label.text();
        }
      }
      $(this).addClass('error');
    }
    var msg = CRM.alert(text, title, 'error', $.extend(extra, options));
    if ($(this).length) {
      var ele = $(this);
      setTimeout(function () {
        ele.one('change', function () {
          msg && msg.close && msg.close();
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
          })
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
        $(this).css({visibility: 'hidden'}).siblings('.crm-form-radio:checked').prop('checked', false).change();
        $(this).siblings('input:text').val('').change();
        return false;
      })
      .on('change', 'input.crm-form-radio:checked', function() {
        $(this).siblings('.crm-clear-link').css({visibility: ''});
      })

      // Allow normal clicking of links within accordions
      .on('click.crmAccordions', 'div.crm-accordion-header a', function (e) {
        e.stopPropagation();
      })
      // Handle accordions
      .on('click.crmAccordions', '.crm-accordion-header, .crm-collapsible .collapsible-title', function (e) {
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
   * @deprecated
   */
  $.fn.crmAccordions = function () {};
  /**
   * Collapse or expand an accordion
   * @param speed
   */
  $.fn.crmAccordionToggle = function (speed) {
    $(this).each(function () {
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
   * @param [optional] string format - currency representation of the number 1234.56
   * @return string
   */
  var currencyTemplate;
  CRM.formatMoney = function(value, format) {
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
})(jQuery, _);
