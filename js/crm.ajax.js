// https://civicrm.org/licensing
/**
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/AJAX+Interface
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/Ajax+Pages+and+Forms
 */
(function($, CRM, undefined) {
  /**
   * @param string path
   * @param string|object query
   * @param string mode - optionally specify "front" or "back"
   */
  var tplURL;
  CRM.url = function (path, query, mode) {
    if (typeof path === 'object') {
      return tplURL = path;
    }
    if (!tplURL) {
      CRM.console('error', 'Error: CRM.url called before initialization');
    }
    if (!mode) {
      mode = CRM.config && CRM.config.isFrontend ? 'front' : 'back';
    }
    query = query || '';
    var frag = path.split('?');
    var url = tplURL[mode].replace("*path*", frag[0]);

    if (!query) {
      url = url.replace(/[?&]\*query\*/, '');
    }
    else {
      url = url.replace("*query*", typeof query === 'string' ? query : $.param(query));
    }
    if (frag[1]) {
      url += (url.indexOf('?') < 0 ? '?' : '&') + frag[1];
    }
    return url;
  };

  // @deprecated
  $.extend ({'crmURL':
    function (p, params) {
      CRM.console('warn', 'Calling crmURL from jQuery is deprecated. Please use CRM.url() instead.');
      return CRM.url(p, params);
    }
  });

  $.fn.crmURL = function () {
    return this.each(function() {
      if (this.href) {
        this.href = CRM.url(this.href);
      }
    });
  };

  /**
   * AJAX api
   */
  CRM.api3 = function(entity, action, params, status) {
    if (typeof(entity) === 'string') {
      params = {
        entity: entity,
        action: action.toLowerCase(),
        json: JSON.stringify(params || {})
      };
    } else {
      params = {
        entity: 'api3',
        action: 'call',
        json: JSON.stringify(entity)
      };
      status = action;
    }
    var ajax = $.ajax({
      url: CRM.url('civicrm/ajax/rest'),
      dataType: 'json',
      data: params,
      type: params.action.indexOf('get') < 0 ? 'POST' : 'GET'
    });
    if (status) {
      // Default status messages
      if (status === true) {
        status = {success: params.action === 'delete' ? ts('Removed') : ts('Saved')};
        if (params.action.indexOf('get') === 0) {
          status.start = ts('Loading...');
          status.success = null;
        }
      }
      var messages = status === true ? {} : status;
      CRM.status(status, ajax);
    }
    return ajax;
  };

  /**
   * @deprecated
   * AJAX api
   */
  CRM.api = function(entity, action, params, options) {
    // Default settings
    var settings = {
      context: null,
      success: function(result, settings) {
        return true;
      },
      error: function(result, settings) {
        $().crmError(result.error_message, ts('Error'));
        return false;
      },
      callBack: function(result, settings) {
        if (result.is_error == 1) {
          return settings.error.call(this, result, settings);
        }
        return settings.success.call(this, result, settings);
      },
      ajaxURL: 'civicrm/ajax/rest'
    };
    action = action.toLowerCase();
    // Default success handler
    switch (action) {
      case "update":
      case "create":
      case "setvalue":
      case "replace":
        settings.success = function() {
          CRM.status(ts('Saved'));
          return true;
        };
        break;
      case "delete":
        settings.success = function() {
          CRM.status(ts('Removed'));
          return true;
        };
    }
    params = {
      entity: entity,
      action: action,
      json: JSON.stringify(params)
    };
    // Pass copy of settings into closure to preserve its value during multiple requests
    (function(stg) {
      $.ajax({
        url: stg.ajaxURL.indexOf('http') === 0 ? stg.ajaxURL : CRM.url(stg.ajaxURL),
        dataType: 'json',
        data: params,
        type: action.indexOf('get') < 0 ? 'POST' : 'GET',
        success: function(result) {
          stg.callBack.call(stg.context, result, stg);
        }
      });
    })($.extend({}, settings, options));
  };

  /**
   * Backwards compatible with jQuery fn
   * @deprecated
   */
  $.fn.crmAPI = function(entity, action, params, options) {
    CRM.console('warn', 'Calling crmAPI from jQuery is deprecated. Please use CRM.api3() instead.');
    return CRM.api.call(this, entity, action, params, options);
  };

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
    _onError: function(data) {
      this.element.attr('data-unsaved-changes', 'false').trigger('crmAjaxError', data);
      if (this.options.crmForm && this.options.crmForm.autoClose && this.element.data('uiDialog')) {
        this.element.dialog('close');
      }
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
      this.options.crmForm && $('form', this.element).ajaxFormUnbind();
      if (this._originalContent === null) {
        this._originalContent = this.element.contents().detach();
      }
      this.options.block && this.element.block();
      $.getJSON(url, function(data) {
        that.options.block && that.element.unblock();
        if (!$.isPlainObject(data)) {
          that._onFailure(data);
          return;
        }
        if (data.status === 'error') {
          that._onError(data);
          return;
        }
        data.url = url;
        that.element.trigger('crmUnload').trigger('crmBeforeLoad', data);
        that._beforeRemovingContent();
        that.element.html(data.content);
        that._handleOrderLinks();
        that.element.trigger('crmLoad', data);
        that.options.crmForm && that.element.trigger('crmFormLoad', data);
      }).fail(function() {
        that._onFailure();
      });
    },
    // Perform any cleanup needed before removing/replacing content
    _beforeRemovingContent: function() {
      var that = this;
      if (window.tinyMCE && tinyMCE.editors) {
        $.each(tinyMCE.editors, function(k) {
          if ($.contains(that.element[0], this.getElement())) {
            this.remove();
          }
        });
      }
      this.options.crmForm && $('form', this.element).ajaxFormUnbind();
    },
    _destroy: function() {
      this.element.removeClass('crm-ajax-container').trigger('crmUnload');
      this._beforeRemovingContent();
      if (this._originalContent !== null) {
        this.element.empty().append(this._originalContent);
      }
    }
  });

  var dialogCount = 0,
    exclude = '[href^=#], [href^=javascript], [onclick], .no-popup, .cancel';

  CRM.loadPage = function(url, options) {
    var settings = {
      target: '#crm-ajax-dialog-' + (dialogCount++),
      dialog: false
    };
    if (!options || !options.target) {
      settings.dialog = {
        modal: true,
        width: '65%',
        height: '75%'
      };
    }
    options && $.extend(true, settings, options);
    settings.url = url;
    // Create new dialog
    if (settings.dialog) {
      // HACK: jQuery UI doesn't support relative height
      if (typeof settings.dialog.height === 'string' && settings.dialog.height.indexOf('%') > 0) {
        settings.dialog.height = parseInt($(window).height() * (parseFloat(settings.dialog.height)/100), 10);
      }
      // Increase percent width on small screens
      if (typeof settings.dialog.width === 'string' && settings.dialog.width.indexOf('%') > 0) {
        var screenWidth = $(window).width(),
          percentage = parseInt(settings.dialog.width.replace('%', ''), 10),
          gap = 100-percentage;
        if (screenWidth < 701) {
          settings.dialog.width = '100%';
        }
        else if (screenWidth < 1400) {
          settings.dialog.width = '' + parseInt(percentage+gap-((screenWidth - 700)/7*(gap)/100), 10) + '%';
        }
      }
      $('<div id="'+ settings.target.substring(1) +'"><div class="crm-loading-element">' + ts('Loading') + '...</div></div>').dialog(settings.dialog);
      $(settings.target)
        .on('dialogclose', function() {
          if ($(this).attr('data-unsaved-changes') !== 'true') {
            $(this).crmSnippet('destroy').dialog('destroy').remove();
          }
        })
        .on('crmLoad', function(e, data) {
          // Set title
          if (e.target === $(settings.target)[0] && data && !settings.dialog.title && data.title) {
            $(this).dialog('option', 'title', data.title);
          }
          // Adjust height to fit content (small delay to allow elements to render)
          window.setTimeout(function() {
            var currentHeight = $(settings.target).parent().outerHeight(),
              padding = currentHeight - $(settings.target).height(),
              newHeight = $(settings.target).prop('scrollHeight') + padding,
              menuHeight = $('#civicrm-menu').outerHeight(),
              maxHeight = $(window).height() - menuHeight;
            newHeight = newHeight > maxHeight ? maxHeight : newHeight;
            if (newHeight > (currentHeight + 15)) {
              $(settings.target).dialog('option', {
                position: {my: 'center', at: 'center center+' + (menuHeight / 2), of: window},
                height: newHeight
              });
            }
          }, 500);
        });
    }
    $(settings.target).crmSnippet(settings).crmSnippet('refresh');
    return $(settings.target);
  };
  CRM.loadForm = function(url, options) {
    var formErrors = [], settings = {
      crmForm: {
        ajaxForm: {},
        autoClose: true,
        validate: true,
        refreshAction: ['next_new', 'submit_savenext', 'upload_new'],
        cancelButton: '.cancel',
        openInline: 'a.open-inline, a.button, a.action-item',
        onCancel: function(event) {}
      }
    };
    // Move options that belong to crmForm. Others will be passed through to crmSnippet
    options && $.each(options, function(key, value) {
      if (typeof(settings.crmForm[key]) !== 'undefined') {
        settings.crmForm[key] = value;
      }
      else {
        settings[key] = value;
      }
    });

    var widget = CRM.loadPage(url, settings).off('.crmForm');

    // CRM-14353 - Warn of unsaved changes for all forms except those which have opted out
    function cancelAction() {
      var dirty = CRM.utils.initialValueChanged($('form:not([data-warn-changes=false])', widget));
      widget.attr('data-unsaved-changes', dirty ? 'true' : 'false');
      if (dirty) {
        var id = widget.attr('id') + '-unsaved-alert',
          title = widget.dialog('option', 'title'),
          alert = CRM.alert('<p>' + ts('%1 has not been saved.', {1: title}) + '</p><p><a href="#" id="' + id + '">' + ts('Restore') + '</a></p>', ts('Unsaved Changes'), 'alert unsaved-dialog', {expires: 60000});
        $('#' + id).button({icons: {primary: 'ui-icon-arrowreturnthick-1-w'}}).click(function(e) {
          widget.attr('data-unsaved-changes', 'false').dialog('open');
          e.preventDefault();
        });
      }
    }

    widget.data('uiDialog') && widget.on('dialogbeforeclose', function(e) {
      // CRM-14353 - Warn unsaved changes if user clicks close button or presses "esc"
      if (e.originalEvent) {
        cancelAction();
      }
    });

    widget.on('crmFormLoad.crmForm', function(event, data) {
      var $el = $(this).attr('data-unsaved-changes', 'false'),
        settings = $el.crmSnippet('option', 'crmForm');
      settings.cancelButton && $(settings.cancelButton, this).click(function(e) {
        e.preventDefault();
        var returnVal = settings.onCancel.call($el, e);
        if (returnVal !== false) {
          $el.trigger('crmFormCancel', e);
          if ($el.data('uiDialog') && settings.autoClose) {
            cancelAction();
            $el.dialog('close');
          }
          else if (!settings.autoClose) {
            $el.crmSnippet('resetUrl').crmSnippet('refresh');
          }
        }
      });
      if (settings.validate) {
        $("form", this).crmValidate();
      }
      $("form:not('[data-no-ajax-submit=true]')", this).ajaxForm($.extend({
        url: data.url.replace(/reset=1[&]?/, ''),
        dataType: 'json',
        success: function(response) {
          if (response.content === undefined) {
            $el.trigger('crmFormSuccess', response);
            // Reset form for e.g. "save and new"
            if (response.userContext && (response.status === 'redirect' || (settings.refreshAction && $.inArray(response.buttonName, settings.refreshAction) >= 0))) {
              // Force reset of original url
              $el.data('civiCrmSnippet')._originalUrl = response.userContext;
              $el.crmSnippet('resetUrl').crmSnippet('refresh');
            }
            // Close if we are on the original url or the action was "delete" (in which case returning to view may be inappropriate)
            else if ($el.data('uiDialog') && (settings.autoClose || response.action === 8)) {
              $el.dialog('close');
            }
            else if (settings.autoClose === false) {
              $el.crmSnippet('resetUrl').crmSnippet('refresh');
            }
          }
          else {
            $el.crmSnippet('option', 'block') && $el.unblock();
            response.url = data.url;
            $el.html(response.content).trigger('crmLoad', response).trigger('crmFormLoad', response);
            if (response.status === 'form_error') {
              formErrors = [];
              $el.trigger('crmFormError', response);
              $.each(response.errors || [], function(formElement, msg) {
                formErrors.push($('[name="'+formElement+'"]', $el).crmError(msg));
              });
            }
          }
        },
        beforeSerialize: function(form, options) {
          if (window.CKEDITOR && window.CKEDITOR.instances) {
            $.each(CKEDITOR.instances, function() {
              this.updateElement && this.updateElement();
            });
          }
          if (window.tinyMCE && tinyMCE.editors) {
            $.each(tinyMCE.editors, function() {
              this.save();
            });
          }
        },
        beforeSubmit: function(submission) {
          $.each(formErrors, function() {
            this && this.close && this.close();
          });
          $el.crmSnippet('option', 'block') && $el.block();
          $el.trigger('crmFormSubmit', submission);
        }
      }, settings.ajaxForm));
      if (settings.openInline) {
        settings.autoClose = $el.crmSnippet('isOriginalUrl');
        $(settings.openInline, this).not(exclude + ', .crm-popup').click(function(event) {
          $el.crmSnippet('option', 'url', $(this).attr('href')).crmSnippet('refresh');
          return false;
        });
      }
      // Show form buttons as part of the dialog
      if ($el.data('uiDialog')) {
        var buttonContainers = '.crm-submit-buttons, .action-link',
          buttons = [],
          added = [];
        $(buttonContainers, $el).find('input.crm-form-submit, a.button').each(function() {
          var $el = $(this),
            label = $el.is('input') ? $el.attr('value') : $el.text(),
            identifier = $el.attr('name') || $el.attr('href');
          if (!identifier || identifier === '#' || $.inArray(identifier, added) < 0) {
            var $icon = $el.find('.icon'),
              button = {'data-identifier': identifier, text: label, click: function() {
                $el.click();
              }};
            if ($icon.length) {
              button.icons = {primary: $icon.attr('class')};
            } else {
              var action = $el.hasClass('cancel') ? 'close' : (identifier.substr(identifier.length-4) === '_new' ? 'plus' : 'check');
              button.icons = {primary: 'ui-icon-' + action};
            }
            buttons.push(button);
            added.push(identifier);
          }
          // display:none causes the form to not submit when pressing "enter"
          $el.parents(buttonContainers).css({height: 0, padding: 0, margin: 0, overflow: 'hidden'});
        });
        $el.dialog('option', 'buttons', buttons);
      }
      // Allow a button to prevent ajax submit
      $('input[data-no-ajax-submit=true]').click(function() {
        $(this).closest('form').ajaxFormUnbind();
      });
      // For convenience, focus the first field
      $('input[type=text], textarea, select', this).filter(':visible').first().not('.dateplugin').focus();
    });
    return widget;
  };
  /**
   * Handler for jQuery click event e.g. $('a').click(CRM.popup);
   */
  CRM.popup = function(e) {
    var $el = $(this).first(),
      url = $el.attr('href'),
      popup = $el.data('popup-type') === 'page' ? CRM.loadPage : CRM.loadForm,
      settings = $el.data('popup-settings') || {},
      formSuccess = false;
    settings.dialog = settings.dialog || {};
    if (e.isDefaultPrevented() || !CRM.config.ajaxPopupsEnabled || !url || $el.is(exclude)) {
      return;
    }
    // Sized based on css class
    if ($el.hasClass('small-popup')) {
      settings.dialog.width = 400;
      settings.dialog.height = 300;
    }
    else if ($el.hasClass('medium-popup')) {
      settings.dialog.width = settings.dialog.height = '50%';
    }
    var dialog = popup(url, settings);
    // Trigger events from the dialog on the original link element
    $el.trigger('crmPopupOpen', [dialog]);
    // Listen for success events and buffer them so we only trigger once
    dialog.on('crmFormSuccess.crmPopup crmPopupFormSuccess.crmPopup', function() {
      formSuccess = true;
    });
    dialog.on('dialogclose.crmPopup', function(e, data) {
      if (formSuccess) {
        $el.trigger('crmPopupFormSuccess', [dialog, data]);
      }
      $el.trigger('crmPopupClose', [dialog, data]);
    });
    e.preventDefault();
  };
  /**
   * An event callback for CRM.popup or a standalone function to refresh the content around a given element
   * @param e {event|selector}
   */
  CRM.refreshParent = function(e) {
    // Use e.target if input smells like an event, otherwise assume it's a jQuery selector
    var $el = (e.stopPropagation && e.target) ? $(e.target) : $(e),
      $table = $el.closest('.dataTable');
    // Call native refresh method on ajax datatables
    if ($table.length && $.fn.DataTable.fnIsDataTable($table[0]) && $table.dataTable().fnSettings().sAjaxSource) {
      // Refresh ALL datatables - needed for contact relationship tab
      $.each($.fn.dataTable.fnTables(), function() {
        $(this).dataTable().fnSettings().sAjaxSource && $(this).unblock().dataTable().fnDraw();
      });
    }
    // Otherwise refresh the nearest crmSnippet
    else {
      $el.closest('.crm-ajax-container, #crm-main-content-wrapper').crmSnippet().crmSnippet('refresh');
    }
  };

  $(function($) {
    $('body')
      .on('click', 'a.crm-popup', CRM.popup)
      // Close unsaved dialog messages
      .on('dialogopen', function(e) {
        $('.alert.unsaved-dialog .ui-notify-cross', '#crm-notification-container').click();
      })
      // Destroy old unsaved dialog
      .on('dialogcreate', function(e) {
        $('.ui-dialog-content.crm-ajax-container:hidden[data-unsaved-changes=true]').crmSnippet('destroy').dialog('destroy').remove();
      });
  });

}(jQuery, CRM));
