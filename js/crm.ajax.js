// https://civicrm.org/licensing
/**
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/AJAX+Interface
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/Ajax+Pages+and+Forms
 */
(function($, CRM) {
  /**
   * Almost like {crmURL} but on the client side
   * eg: var url = CRM.url('civicrm/contact/view', {reset:1,cid:42});
   * or: $('a.my-link').crmURL();
   */
  var tplURL = '/civicrm/example?placeholder';
  var urlInitted = false;
  CRM.url = function (p, params) {
    if (p == "init") {
      tplURL = params;
      urlInitted = true;
      return;
    }
    if (!urlInitted) {
      console && console.log && console.log('Warning: CRM.url called before initialization');
    }
    params = params || '';
    var frag = p.split ('?');
    var url = tplURL.replace("civicrm/example", frag[0]);

    if (typeof(params) == 'string') {
      url = url.replace("placeholder", params);
    }
    else {
      url = url.replace("placeholder", $.param(params));
    }
    if (frag[1]) {
      url += (url.indexOf('?') === (url.length - 1) ? '' : '&') + frag[1];
    }
    // remove trailing "?"
    if (url.indexOf('?') === (url.length - 1)) {
      url = url.slice(0, (url.length - 1));
    }
    return url;
  };

  // Backwards compatible with jQuery fn
  $.extend ({'crmURL':
    function (p, params) {
      console && console.log && console.log('Calling crmURL from jQuery is deprecated. Please use CRM.url() instead.');
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
    console && console.log && console.log('Calling crmAPI from jQuery is deprecated. Please use CRM.api() instead.');
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
      this.options.block && $('.blockOverlay', this.element).length < 1 && this.element.block();
      $.getJSON(url, function(data) {
        if (typeof(data) != 'object' || typeof(data.content) != 'string') {
          that._onFailure(data);
          return;
        }
        data.url = url;
        that.element.trigger('crmBeforeLoad', data).html(data.content);
        that._handleOrderLinks();
        that.element.trigger('crmLoad', data);
        that.options.crmForm && that.element.trigger('crmFormLoad', data);
      }).fail(function() {
        that._onFailure();
      });
    },
    _destroy: function() {
      this.element.removeClass('crm-ajax-container');
      this.options.crmForm && $('form', this.element).ajaxFormUnbind();
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
      $('<div id="'+ settings.target.substring(1) +'"><div class="crm-loading-element">' + ts('Loading') + '...</div></div>').dialog(settings.dialog);
      $(settings.target).on('dialogclose', function() {
        if ($(this).attr('data-unsaved-changes') !== 'true') {
          $(this).crmSnippet('destroy').dialog('destroy').remove();
        }
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
  CRM.loadForm = function(url, options) {
    var settings = {
      crmForm: {
        ajaxForm: {},
        autoClose: true,
        validate: true,
        refreshAction: ['next_new', 'submit_savenext', 'upload_new'],
        cancelButton: '.cancel',
        openInline: 'a.open-inline, a.button, a.action-item',
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

    function cancelAction() {
      var dirty = CRM.utils.initialValueChanged(widget),
        title = widget.dialog('option', 'title');
      widget.attr('data-unsaved-changes', dirty ? 'true' : 'false').dialog('close');
      if (dirty) {
        var id = widget.attr('id') + '-unsaved-alert',
          alert = CRM.alert('<p>' + ts('%1 has not been saved.', {1: title}) + '</p><p><a href="#" id="' + id + '">' + ts('Restore') + '</a></p>', ts('Unsaved Changes'), 'alert unsaved-dialog', {expires: 60000});
        $('#' + id).button({icons: {primary: 'ui-icon-arrowreturnthick-1-w'}}).click(function(e) {
          widget.attr('data-unsaved-changes', 'false').dialog('open');
          e.preventDefault();
        });
      }
    }
    if (widget.data('uiDialog')) {
      // This is a bit harsh but we are removing jQuery UI's event handler from the close button and adding our own
      $('.ui-dialog-titlebar-close').first().off().click(cancelAction);
    }

    widget.on('crmFormLoad.crmForm', function(event, data) {
      var $el = $(this)
        .attr('data-unsaved-changes', 'false');
      var settings = $el.crmSnippet('option', 'crmForm');
      settings.cancelButton && $(settings.cancelButton, this).click(function(e) {
        e.preventDefault();
        var returnVal = settings.onCancel.call($el, e);
        if (returnVal !== false) {
          $el.trigger('crmFormCancel', e);
          if ($el.data('uiDialog') && settings.autoClose) {
            cancelAction();
          }
          else if (!settings.autoClose) {
            $el.crmSnippet('resetUrl').crmSnippet('refresh');
          }
        }
      });
      if (settings.validate) {
        $("form", this).validate(typeof(settings.validate) == 'object' ? settings.validate : CRM.validate.params);
      }
      $("form:not('[data-no-ajax-submit=true]')", this).ajaxForm($.extend({
        url: data.url.replace(/reset=1[&]?/, ''),
        dataType: 'json',
        success: function(response) {
          if (response.status !== 'form_error') {
            $el.crmSnippet('option', 'block') && $el.unblock();
            $el.trigger('crmFormSuccess', response);
            // Reset form for e.g. "save and new"
            if (response.userContext && (response.status === 'redirect' || (settings.refreshAction && $.inArray(response.buttonName, settings.refreshAction) >= 0))) {
              // Force reset of original url
              $el.data('civiCrmSnippet')._originalUrl = response.userContext;
              $el.crmSnippet('resetUrl').crmSnippet('refresh');
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
        $(settings.openInline, this).not(exclude + ', .crm-popup').click(function(event) {
          $el.crmSnippet('option', 'url', $(this).attr('href')).crmSnippet('refresh');
          return false;
        });
      }
      // For convenience, focus the first field
      $('input[type=text], textarea, select', this).filter(':visible').first().not('.dateplugin').focus();
    });
    return widget;
  };
  /**
   * Handler for jQuery click event e.g. $('a').click(CRM.popup)
   * @returns {boolean}
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
    else if ($el.hasClass('huge-popup')) {
      settings.dialog.height = '90%';
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
   * An event callback for CRM.popup or a standalone function to refresh the content around a popup link
   * @param e event|selector
   */
  CRM.refreshParent = function(e) {
    // Use e.target if input smells like an event, otherwise assume it's a jQuery selector
    var $el = (e.stopPropagation && e.target) ? $(e.target) : $(e),
      $table = $el.closest('.dataTable');
    // Call native refresh method on ajax datatables
    if ($table && $.fn.DataTable.fnIsDataTable($table[0]) && $table.dataTable().fnSettings().sAjaxSource) {
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
