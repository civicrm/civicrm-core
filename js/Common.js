// https://civicrm.org/licensing
var CRM = CRM || {};
var cj = jQuery;

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
  if (target_element_type == null) {
    var target_element_type = 'block';
  }
  else {
    if (target_element_type == 'table-row') {
      var target_element_type = '';
    }
  }

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
        if (cj('[name="' + trigger_field_id + '"]').is(':checked')) {
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
 * @deprecated
 */
function popUp(URL) {
  day = new Date();
  id = day.getTime();
  eval("page" + id + " = window.open(URL, '" + id + "', 'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=0,width=640,height=420,left = 202,top = 184');");
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
CRM.validate = CRM.validate || {
  params: {},
  functions: []
};

(function ($, undefined) {
  "use strict";

  $.fn.select2.defaults.dropdownCssClass = 'crm-container';
  // https://github.com/ivaynberg/select2/pull/2090
  $.fn.select2.defaults.width = 'resolve';

  // Workaround for https://github.com/ivaynberg/select2/issues/1246
  $.ui.dialog.prototype._allowInteraction = function(e) {
    return !!$(e.target).closest('.ui-dialog, .ui-datepicker, .select2-drop').length;
  };

  /**
   * Populate a select list, overwriting the existing options except for the placeholder.
   * @param $el jquery collection - 1 or more select elements
   * @param options array in format returned by api.getoptions
   * @param removePlaceholder bool
   */
  CRM.utils.setOptions = function($el, options, removePlaceholder) {
    $el.each(function() {
      var
        $elect = $(this),
        val = $elect.val() || [],
        opts = removePlaceholder ? '' : '[value!=""]';
      if (typeof(val) !== 'array') {
        val = [val];
      }
      $elect.find('option' + opts).remove();
      _.each(options, function(option) {
        var selected = ($.inArray(''+option.key, val) > -1) ? 'selected="selected"' : '';
        $elect.append('<option value="' + option.key + '"' + selected + '>' + option.value + '</option>');
      });
      $elect.trigger('crmOptionsUpdated', $.extend({}, options)).trigger('change');
    });
  };

  /**
   * Wrapper for select2 initialization function; supplies defaults
   * @param options object
   */
  $.fn.crmSelect2 = function(options) {
    return $(this).each(function () {
      var
        $el = $(this),
        defaults = {allowClear: !$el.hasClass('required')};
      // quickform doesn't support optgroups so here's a hack :(
      $('option[value^=crm_optgroup]', this).each(function () {
        $(this).nextUntil('option[value^=crm_optgroup]').wrapAll('<optgroup label="' + $(this).text() + '" />');
        $(this).remove();
      });
      // Defaults for single-selects
      if ($el.is('select:not([multiple])')) {
        defaults.minimumResultsForSearch = 10;
        if ($('option:first', this).val() === '') {
          defaults.placeholderOption = 'first';
        }
      }
      $el.select2($.extend(defaults, $el.data('select-params') || {}, options || {}));
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
        $el = $(this),
        entity = options.entity || $el.data('api-entity') || 'contact',
        selectParams = {};
      $el.data('api-entity', entity);
      $el.data('select-params', $.extend({}, $el.data('select-params') || {}, options.select));
      $el.data('api-params', $.extend({}, $el.data('api-params') || {}, options.api));
      $el.data('create-links', options.create || $el.data('create-links'));
      $el.addClass('crm-ajax-select crm-' + entity + '-ref');
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
        formatResult: formatSelect2Result,
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
              callback(multiple ? result.values : result.values[0])
            });
          }
        }
      };
      if ($el.data('create-links')) {
        selectParams.formatInputTooShort = function() {
          var txt = $el.data('select-params').formatInputTooShort || $.fn.select2.defaults.formatInputTooShort.call(this);
          if ($el.data('create-links')) {
            txt += ' ' + ts('or') + '<br />' + formatSelect2CreateLinks($el);
          }
          return txt;
        };
        selectParams.formatNoMatches = function() {
          var txt = $el.data('select-params').formatNoMatches || $.fn.select2.defaults.formatNoMatches;
          return txt + '<br />' + formatSelect2CreateLinks($el);
        };
        $el.off('.createLinks').on('select2-open.createLinks', function() {
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
      $el.crmSelect2($.extend(settings, $el.data('select-params'), selectParams));
    });
  };

  function formatSelect2Result(row) {
    var markup = '<div class="crm-select2-row">';
    if (row.image !== undefined) {
      markup += '<div class="crm-select2-image"><img src="' + row.image + '"/></div>';
    }
    else if (row.icon_class) {
      markup += '<div class="crm-select2-icon"><div class="crm-icon ' + row.icon_class + '-icon"></div></div>';
    }
    markup += '<div><div class="crm-select2-row-label">' + row.label + '</div>';
    markup += '<div class="crm-select2-row-description">';
    $.each(row.description || [], function(k, text) {
      markup += '<p>' + text + '</p>';
    });
    markup += '</div></div></div>';
    return markup;
  }

  function formatSelect2CreateLinks($el) {
    var
      createLinks = $el.data('create-links'),
      api = $el.data('api-params') || {},
      type = api.params ? api.params.contact_type : null;
    if (createLinks === true) {
      createLinks = type ? _.where(CRM.profile.contactCreate, {type: type}) : CRM.profile.contactCreate;
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

  // Initialize widgets
  $(document)
    .on('crmLoad', function(e) {
      $('table.row-highlight', e.target)
        .off('.rowHighlight')
        .on('change.rowHighlight', 'input.select-row, input.select-rows', function () {
          var target, table = $(this).closest('table');
          if ($(this).hasClass('select-rows')) {
            target = $('tbody tr', table);
            $('input.select-row', table).prop('checked', $(this).prop('checked'));
          }
          else {
            target = $(this).closest('tr');
            $('input.select-rows', table).prop('checked', $(".select-row:not(':checked')", table).length < 1);
          }
          target.toggleClass('crm-row-selected', $(this).is(':checked'));
        })
        .find('input.select-row:checked').parents('tr').addClass('crm-row-selected');
      $('.crm-select2:not(.select2-offscreen)', e.target).crmSelect2();
      $('.crm-form-entityref:not(.select2-offscreen)', e.target).crmEntityRef();
    })
    // Modal dialogs should disable scrollbars
    .on('dialogopen', function(e) {
      if ($(e.target).dialog('option', 'modal')) {
        $(e.target).addClass('modal-dialog');
        $('body').css({overflow: 'hidden'});
      }
    })
    .on('dialogclose', function(e) {
      if ($('.ui-dialog .modal-dialog').not(e.target).length < 1) {
        $('body').css({overflow: ''});
      }
    });

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
  $(window).resize(function () {
    advmultiselectResize();
  });

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
  CRM.confirm = function (buttons, options, cancelLabel) {
    var dialog, callbacks = {};
    cancelLabel = cancelLabel || ts('Cancel');
    var settings = {
      title: ts('Confirm Action'),
      message: ts('Are you sure you want to continue?'),
      resizable: false,
      modal: true,
      width: 'auto',
      close: function () {
        $(dialog).remove();
      },
      buttons: {}
    };

    settings.buttons[cancelLabel] = function () {
      dialog.dialog('close');
    };
    options = options || {};
    $.extend(settings, options);
    if (typeof(buttons) === 'function') {
      callbacks[ts('Continue')] = buttons;
    }
    else {
      callbacks = buttons;
    }
    $.each(callbacks, function (label, callback) {
      settings.buttons[label] = function () {
        if (callback.call(dialog) !== false) {
          dialog.dialog('close');
        }
      };
    });
    dialog = $('<div class="crm-container crm-confirm-dialog"></div>')
      .html(options.message)
      .appendTo('body')
      .dialog(settings);
    return dialog;
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
      // ignore autocomplete fields
      if ($(this).is('.ac_input')) {
        return;
      }

      $('.ui-notify-message.error a.ui-notify-close').click();
      $(this).removeClass('error');
      $(this).next('span.crm-error').remove();
      $('label[for="' + $(this).attr('name') + '"], label[for="' + $(this).attr('id') + '"]')
        .removeClass('crm-error')
        .find('.crm-error').removeClass('crm-error');
    });
  }

  /**
   * @see https://wiki.civicrm.org/confluence/display/CRMDOC/Ajax+Pages+and+Forms
   */
  $.widget('civi.crmSnippet', {
    options: {
      url: null,
      block: true,
      crmForm: null
    },
    _originalContent: null,
    _originalUrl: null,
    isOriginalUrl: function() {
      var 
        args = {}, 
        same = true,
        newUrl = this._formatUrl(this.options.url),
        oldUrl = this._formatUrl(this._originalUrl);
      // Compare path
      if (newUrl.split('?')[0] !== oldUrl.split('?')[0]) {
        return false;
      }
      // Compare arguments
      $.each(newUrl.split('?')[1].split('&'), function(k, v) {
        var arg = v.split('=');
        args[arg[0]] = arg[1];
      });
      $.each(oldUrl.split('?')[1].split('&'), function(k, v) {
        var arg = v.split('=');
        if (args[arg[0]] !== undefined && arg[1] !== args[arg[0]]) {
          same = false;
        }
      });
      return same;
    },
    resetUrl: function() {
      this.options.url = this._originalUrl;
    },
    _create: function() {
      this.element.addClass('crm-ajax-container');
      if (!this.element.is('.crm-container *')) {
        this.element.addClass('crm-container');
      }
      this._handleOrderLinks();
      // Set default if not supplied
      this.options.url = this.options.url || document.location.href;
      this._originalUrl = this.options.url;
    },
    _onFailure: function(data) {
      this.options.block && this.element.unblock();
      this.element.trigger('crmAjaxFail', data);
      CRM.alert(ts('Unable to reach the server. Please refresh this page in your browser and try again.'), ts('Network Error'), 'error');
    },
    _formatUrl: function(url) {
      // Strip hash
      url = url.split('#')[0];
      // Add snippet argument to url
      if (url.search(/[&?]snippet=/) < 0) {
        url += (url.indexOf('?') < 0 ? '?' : '&') + 'snippet=json';
      } else {
        url = url.replace(/snippet=[^&]*/, 'snippet=json');
      }
      return url;
    },
    // Hack to deal with civicrm legacy sort functionality
    _handleOrderLinks: function() {
      var that = this;
      $('a.crm-weight-arrow', that.element).click(function(e) {
        that.options.block && that.element.block();
        $.getJSON(that._formatUrl(this.href)).done(function() {
          that.refresh();
        });
        e.stopImmediatePropagation();
        return false;
      });
    },
    refresh: function() {
      var that = this;
      var url = this._formatUrl(this.options.url);
      this.options.block && $('.blockOverlay', this.element).length < 1 && this.element.block();
      $.getJSON(url, function(data) {
        if (typeof(data) != 'object' || typeof(data.content) != 'string') {
          that._onFailure(data);
          return;
        }
        data.url = url;
        that.element.trigger('crmBeforeLoad', data);
        if (that._originalContent === null) {
          that._originalContent = that.element.contents().detach();
        }
        that.element.html(data.content);
        that._handleOrderLinks();
        that.element.trigger('crmLoad', data);
        that.options.crmForm && that.element.trigger('crmFormLoad', data);
      }).fail(function() {
          that._onFailure();
        });
    },
    _destroy: function() {
      this.element.removeClass('crm-ajax-container');
      if (this._originalContent !== null) {
        this.element.empty().append(this._originalContent);
      }
    }
  });

  var dialogCount = 0;
  /**
   * @see https://wiki.civicrm.org/confluence/display/CRMDOC/Ajax+Pages+and+Forms
   */
  CRM.loadPage = function(url, options) {
    var settings = {
      target: '#crm-ajax-dialog-' + (dialogCount++),
      dialog: false
    };
    if (!options || !options.target) {
      settings.dialog = {
        modal: true,
        width: '65%',
        height: parseInt($(window).height() * .75)
      };
    }
    options && $.extend(true, settings, options);
    settings.url = url;
    // Create new dialog
    if (settings.dialog) {
      $('<div id="'+ settings.target.substring(1) +'"><div class="crm-loading-element">' + ts('Loading') + '...</div></div>').dialog(settings.dialog);
      $(settings.target).on('dialogclose', function() {
        $(this).dialog('destroy').remove();
      });
    }
    if (settings.dialog && !settings.dialog.title) {
      $(settings.target).on('crmLoad', function(e, data) {
        if (e.target === $(settings.target)[0] && data && data.title) {
          $(this).dialog('option', 'title', data.title);
        }
      });
    }
    $(settings.target).crmSnippet(settings).crmSnippet('refresh');
    return $(settings.target);
  };
  /**
   * @see https://wiki.civicrm.org/confluence/display/CRMDOC/Ajax+Pages+and+Forms
   */
  CRM.loadForm = function(url, options) {
    var settings = {
      crmForm: {
        ajaxForm: {},
        autoClose: true,
        validate: true,
        refreshAction: ['next_new', 'submit_savenext'],
        cancelButton: '.cancel.form-submit',
        openInline: 'a.button:not("[href=#], .no-popup")',
        onCancel: function(event) {},
        onError: function(data) {
          var $el = $(this);
          $el.html(data.content).trigger('crmLoad', data).trigger('crmFormLoad', data).trigger('crmFormError', data);
          if (typeof(data.errors) == 'object') {
            $.each(data.errors, function(formElement, msg) {
              $('[name="'+formElement+'"]', $el).crmError(msg);
            });
          }
        }
      }
    };
    // Hack to make delete dialogs smaller
    if (url.indexOf('/delete') > 0 || url.indexOf('action=delete') > 0) {
      settings.dialog = {
        width: 400,
        height: 300
      };
    }
    // Move options that belong to crmForm. Others will be passed through to crmSnippet
    options && $.each(options, function(key, value) {
      if (typeof(settings.crmForm[key]) !== 'undefined') {
        settings.crmForm[key] = value;
      }
      else {
        settings[key] = value;
      }
    });

    var widget = CRM.loadPage(url, settings);

    widget.on('crmFormLoad', function(event, data) {
      var $el = $(this);
      var settings = $el.crmSnippet('option', 'crmForm');
      settings.cancelButton && $(settings.cancelButton, this).click(function(event) {
        var returnVal = settings.onCancel.call($el, event);
        if (returnVal !== false) {
          $el.trigger('crmFormCancel', event);
          if ($el.data('uiDialog') && settings.autoClose) {
            $el.dialog('close');
          }
          else if (!settings.autoClose) {
            $el.crmSnippet('resetUrl').crmSnippet('refresh');
          }
        }
        return returnVal === false;
      });
      if (settings.validate) {
        $("form", this).validate(typeof(settings.validate) == 'object' ? settings.validate : CRM.validate.params);
      }
      $("form", this).ajaxForm($.extend({
        url: data.url.replace(/reset=1[&]?/, ''),
        dataType: 'json',
        success: function(response) {
          if (response.status !== 'form_error') {
            $el.crmSnippet('option', 'block') && $el.unblock();
            $el.trigger('crmFormSuccess', response);
            // Reset form for e.g. "save and new"
            if (response.userContext && settings.refreshAction && $.inArray(response.buttonName, settings.refreshAction) >= 0) {
              $el.crmSnippet('option', 'url', response.userContext).crmSnippet('refresh');
            }
            else if ($el.data('uiDialog') && settings.autoClose) {
              $el.dialog('close');
            }
            else if (settings.autoClose === false) {
              $el.crmSnippet('resetUrl').crmSnippet('refresh');
            }
          }
          else {
            response.url = data.url;
            settings.onError.call($el, response);
          }
        },
        beforeSerialize: function(form, options) {
          if (window.CKEDITOR && window.CKEDITOR.instances) {
            $.each(CKEDITOR.instances, function() {
              this.updateElement && this.updateElement();
            });
          }
        },
        beforeSubmit: function(submission) {
          $el.crmSnippet('option', 'block') && $el.block();
          $el.trigger('crmFormSubmit', submission);
        }
      }, settings.ajaxForm));
      if (settings.openInline) {
        settings.autoClose = $el.crmSnippet('isOriginalUrl');
        $(settings.openInline, this).click(function(event) {
          $el.crmSnippet('option', 'url', $(this).attr('href')).crmSnippet('refresh');
          return false;
        });
      }
      // For convenience, focus the first field
      $('input[type=text], textarea, select', this).filter(':visible').first().focus();
    });
    return widget;
  };

  // Preprocess all cj ajax calls to display messages
  $(document).ajaxSuccess(function(event, xhr, settings) {
    try {
      if ((!settings.dataType || settings.dataType == 'json') && xhr.responseText) {
        var response = $.parseJSON(xhr.responseText);
        if (typeof(response.crmMessages) == 'object') {
          $.each(response.crmMessages, function(n, msg) {
            CRM.alert(msg.text, msg.title, msg.type, msg.options);
          })
        }
      }
    }
    // Suppress errors
    catch (e) {}
  });

  /**
   * Temporary stub to get around name conflict with legacy jQuery.autocomplete plugin
   * FIXME: Remove this before 4.5 release
   */
  $.widget('civi.crmAutocomplete', $.ui.autocomplete, {});

  $(function () {
    // Trigger crmLoad on initial content for consistency. It will also be triggered for ajax-loaded content.
    $('.crm-container').trigger('crmLoad');

    if ($('#crm-notification-container').length) {
      // Initialize notifications
      $('#crm-notification-container').notify();
      messagesFromMarkup.call($('#crm-container'));
    }

    // bind the event for image popup
    $('body')
      .on('click', 'a.crm-image-popup', function() {
        var o = $('<div class="crm-container crm-custom-image-popup"><img src=' + $(this).attr('href') + '></div>');

        CRM.confirm('',
          {
            title: ts('Preview'),
            message: o
          },
          ts('Done')
        );
        return false;
      })

      .on('click', function (event) {
        $('.btn-slide-active').removeClass('btn-slide-active').find('.panel').hide();
        if ($(event.target).is('.btn-slide')) {
          $(event.target).addClass('btn-slide-active').find('.panel').show();
        }
      })

      .on('click', 'a.crm-option-edit-link', function() {
        var
          link = $(this),
          optionsChanged = false;
        CRM.loadForm(this.href, {openInline: 'a:not("[href=#], .no-popup")'})
          .on('crmFormSuccess', function() {
            optionsChanged = true;
          })
          .on('dialogclose', function() {
            if (optionsChanged) {
              link.trigger('crmOptionsEdited');
              var $elects = $('select[data-option-edit-path="' + link.data('option-edit-path') + '"]');
              if ($elects.data('api-entity') && $elects.data('api-field')) {
                CRM.api3($elects.data('api-entity'), 'getoptions', {sequential: 1, field: $elects.data('api-field')})
                  .done(function (data) {
                    CRM.utils.setOptions($elects, data.values);
                  });
              }
            }
          });
        return false;
      })
      // Handle clear button for form elements
      .on('click', 'a.crm-clear-link', function() {
        $(this).css({visibility: 'hidden'}).siblings('.crm-form-radio:checked').prop('checked', false).change();
        $(this).siblings('input:text').val('').change();
        return false;
      })
      .on('change', 'input.crm-form-radio:checked', function() {
        $(this).siblings('.crm-clear-link').css({visibility: ''});
      });
    $().crmtooltip();
  });

  $.fn.crmAccordions = function (speed) {
    var container = $(this).length > 0 ? $(this) : $('.crm-container');
    speed = speed === undefined ? 200 : speed;
    container
      .off('click.crmAccordions')
      // Allow normal clicking of links
      .on('click.crmAccordions', 'div.crm-accordion-header a', function (e) {
        e.stopPropagation && e.stopPropagation();
      })
      .on('click.crmAccordions', '.crm-accordion-header, .crm-collapsible .collapsible-title', function () {
        if ($(this).parent().hasClass('collapsed')) {
          $(this).next().css('display', 'none').slideDown(speed);
        }
        else {
          $(this).next().css('display', 'block').slideUp(speed);
        }
        $(this).parent().toggleClass('collapsed');
        return false;
      });
  };
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
   * @param value
   * @param format - currency representation of the number 1234.56
   * @return string
   * @see CRM_Core_Resources::addCoreResources
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
})(jQuery);
