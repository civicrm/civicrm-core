// https://civicrm.org/licensing
/**
 * @see https://docs.civicrm.org/dev/en/latest/api/interfaces/#ajax
 * @see https://docs.civicrm.org/dev/en/latest/framework/ajax/
 */
(function($, CRM, undefined) {
  /**
   * @param string path
   * @param string|object query
   * @param string mode - optionally specify "front" or "back"
   */
  var tplURL;
  CRM.url = function (path, query, mode) {
    if (path === null) {
      throw new Error('null passed to CRM.url');
    }
    if (typeof path === 'object') {
      tplURL = path;
      return path;
    }
    if (!tplURL) {
      CRM.console('error', 'Error: CRM.url called before initialization');
    }
    if (!mode) {
      mode = CRM.config && CRM.config.isFrontend ? 'front' : 'back';
    }
    query = query || '';
    var url, frag, hash = '';
    if (path.indexOf('#') > -1) {
      hash = '#' + path.split('#')[1];
      path = path.split('#')[0];
    }
    frag = path.split('?');
    // Remove basepage as it can be changed on some CMS eg. WordPress frontend.
    frag[0] = frag[0].replace('civicrm/', '/');
    // Encode url path only if slashes in placeholder were also encoded
    if (tplURL[mode].indexOf('/crmajax-placeholder-url-path') >= 0) {
      url = tplURL[mode].replace('/crmajax-placeholder-url-path', frag[0]);
    } else {
      url = tplURL[mode].replace('%2Fcrmajax-placeholder-url-path', encodeURIComponent(frag[0]));
    }

    if (!query || (typeof query === 'object' && !Object.keys(query).length)) {
      url = url.replace(/[?&]civicrm-placeholder-url-query=1/, '');
    } else {
      url = url.replace('civicrm-placeholder-url-query=1', typeof query === 'string' ? query : $.param(query));
    }
    if (frag[1]) {
      url += (url.indexOf('?') < 0 ? '?' : '&') + frag[1];
    }
    return url + hash;
  };

  $.fn.crmURL = function () {
    return this.each(function() {
      if (this.href) {
        this.href = CRM.url(this.href);
      }
    });
  };

  // result is an array, but in js, an array is also an object
  // Assign all the metadata properties to it, mirroring the results arrayObject in php
  function arrayObject(data) {
    const result = data?.values || [];
    if (Array.isArray(result) && data?.constructor === Object) {
      Object.keys(data).forEach(key => {
        if (key !== 'values') {
          result[key] = data[key];
        }
      });
    }
    return result;
  }

  // Valid keys for the 4th `config` argument to CRM.api4
  const api4ConfigKeys = new Set(['index', 'delay', 'cache', 'signal']);
  // Calls waiting to be sent in a background batch
  const backgroundQueue = [];
  // In-flight cacheable calls keyed by serialized [entity, action, params(, index)]
  const inFlightCache = new Map();
  // Completed TTL-cached calls keyed by serialized [entity, action, params(, index)] -> {result, expires}
  const responseCache = new Map();
  let cacheGeneration = 0;
  let backgroundTimer = null;
  let backgroundDeadline = Infinity;

  function clearApi4Cache() {
    cacheGeneration++;
    inFlightCache.clear();
    responseCache.clear();
  }

  $(window).on('crmPopupFormSuccess crmFormSuccess', clearApi4Cache);

  function isGetAction(entity, action, params) {
    const isReadOnly = action === 'autocomplete' || action === 'checkAccess' ||
      (entity === 'SearchDisplay' && action === 'run') ||
      /^get([A-Z]|$)/.test(action);
    if (!isReadOnly) {
      return false;
    }
    if (params?.chain && typeof params.chain === 'object') {
      return Object.values(params.chain).every((chained) =>
        Array.isArray(chained) && isGetAction(chained[0], chained[1], chained[2])
      );
    }
    return true;
  }

  function normalizeApi4Config(config) {
    if (config === undefined || config === null) {
      return {};
    }
    if (!$.isPlainObject(config)) {
      return {index: config};
    }
    if (Object.keys(config).some((key) => !api4ConfigKeys.has(key))) {
      console.warn('Unrecognized config key(s) passed to CRM.api4; treating as index param:', config);
      return {index: config};
    }
    return config;
  }

  // Returns false (disabled), 0 (in-flight dedupe only), or positive ms TTL
  function resolveCacheTtl(cacheOption, readOnly) {
    const val = cacheOption ?? (readOnly ? 0 : false);
    if (val === true) {
      return 60000;
    }
    return typeof val === 'number' && val >= 0 ? val : false;
  }

  function getAbortReason(signal) {
    return signal.reason || new window.DOMException('Aborted', 'AbortError');
  }

  function buildApi4Call(entity, action, params, index) {
    return index !== undefined ? [entity, action, params, index] : [entity, action, params];
  }

  function getCachedResponse(key) {
    const cached = responseCache.get(key);
    if (!cached) {
      return undefined;
    }
    if (cached.expires > Date.now()) {
      return cached.result;
    }
    responseCache.delete(key);
    return undefined;
  }

  function attachAbortHandler(item) {
    if (!item.signal) {
      return;
    }
    const onAbort = () => {
      item.cleanup = null;
      const reason = getAbortReason(item.signal);
      const queuePos = backgroundQueue.indexOf(item);
      if (queuePos >= 0) {
        backgroundQueue.splice(queuePos, 1);
        if (!backgroundQueue.length) {
          clearTimeout(backgroundTimer);
          backgroundTimer = null;
          backgroundDeadline = Infinity;
        }
        item.reject(reason);
        return;
      }
      if (item.owner) {
        const subPos = item.owner.subscribers.indexOf(item);
        if (subPos >= 0) {
          item.owner.subscribers.splice(subPos, 1);
          if (item.owner.jqXHR && !item.owner.subscribers.length) {
            if (item.owner.cacheKey && inFlightCache.get(item.owner.cacheKey) === item.owner) {
              inFlightCache.delete(item.owner.cacheKey);
            }
            item.owner.jqXHR.abort();
          }
          item.reject(reason);
        }
      }
    };
    item.signal.addEventListener('abort', onAbort, {once: true});
    item.cleanup = () => {
      item.signal.removeEventListener('abort', onAbort);
      item.cleanup = null;
    };
  }

  function sendBackgroundQueue() {
    clearTimeout(backgroundTimer);
    backgroundTimer = null;
    backgroundDeadline = Infinity;
    const toSend = [];

    backgroundQueue.splice(0).forEach((item) => {
      let resolvedParams;
      try {
        // Params given as a function are evaluated now, so they reflect the state at send time
        resolvedParams = (typeof item.params === 'function' ? item.params() : item.params) || {};
      }
      catch (error) {
        item.cleanup?.();
        item.reject(error);
        return;
      }

      const call = buildApi4Call(item.entity, item.action, resolvedParams, item.index);
      const readOnly = isGetAction(item.entity, item.action, resolvedParams);
      if (!readOnly) {
        clearApi4Cache();
      }

      const ttl = resolveCacheTtl(item.cache, readOnly);
      if (ttl !== false) {
        const key = JSON.stringify(call);
        if (ttl > 0) {
          const cached = getCachedResponse(key);
          if (cached !== undefined) {
            item.cleanup?.();
            item.resolve(cached);
            return;
          }
        }
        const existing = inFlightCache.get(key);
        if (existing) {
          // Deduplicate against an already in-flight or earlier queued call
          existing.subscribers.push(item);
          existing.ttl = Math.max(existing.ttl, ttl);
          item.owner = existing;
          return;
        }
        item.cacheKey = key;
        item.ttl = ttl;
        inFlightCache.set(key, item);
      }

      item.call = call;
      item.generation = cacheGeneration;
      item.subscribers = [item];
      item.owner = item;
      toSend.push(item);
    });

    if (!toSend.length) {
      return;
    }

    CRM.api4(toSend.map((item) => item.call))
      .then((results) => toSend.forEach((item, index) => {
        if (item.cacheKey && inFlightCache.get(item.cacheKey) === item) {
          inFlightCache.delete(item.cacheKey);
        }
        const result = results[index];
        const isError = !result || 'error_code' in result;
        if (!isError && item.cacheKey && item.ttl > 0 && item.generation === cacheGeneration) {
          responseCache.set(item.cacheKey, {result, expires: Date.now() + item.ttl});
        }
        const method = isError ? 'reject' : 'resolve';
        item.subscribers.splice(0).forEach((sub) => {
          sub.cleanup?.();
          sub[method](result);
        });
      }), (error) => toSend.forEach((item) => {
        if (item.cacheKey && inFlightCache.get(item.cacheKey) === item) {
          inFlightCache.delete(item.cacheKey);
        }
        item.subscribers.splice(0).forEach((sub) => {
          sub.cleanup?.();
          sub.reject(error);
        });
      }));
  }

  /**
   * AJAX APIv4 client
   * @link https://docs.civicrm.org/dev/en/latest/api/interfaces/#ajax
   *
   * Single call: CRM.api4(entity, action, params, config)
   * Multi-call:  CRM.api4(calls)
   *
   * @param {string|Array|Object} entity
   *   Entity name for a single call, or an array/object of `[entity, action, params, index]` calls.
   * @param {string} [action]
   * @param {Object|Function} [params]
   *   Api params, or a function returning them (evaluated at send time; can cancel by throwing).
   * @param {Object|string|number|Array} [config]
   *   Config object for single calls (non-plain-object values, or objects with unrecognized keys, are treated as `index`):
   *   - index {string|number|Array|Object}: APIv4 result index
   *   - delay {boolean|number}: delay & batch with other delayed calls (`true` = 800ms, number = max wait in ms, `false` = immediate)
   *   - cache {boolean|number}: `false` = disabled (default for writes), `0` = in-flight deduplication only (default for get-like calls),
   *     `true` = 60000ms (1 minute), or positive number = ms to cache in memory (cleared on writes and form success)
   *   - signal {AbortSignal}: cancels a queued or in-flight request
   * @return {Promise}
   */
  CRM.api4 = function(entity, action, params, config) {
    if (typeof entity !== 'string') {
      const hasWrite = Object.values(entity).some((call) =>
        !Array.isArray(call) || !isGetAction(call[0], call[1], call[2])
      );
      if (hasWrite) {
        clearApi4Cache();
      }
      return new Promise((resolve, reject) => {
        $.post(CRM.url('civicrm/ajax/api4'), {
          calls: JSON.stringify(entity)
        })
          .done((data) => {
            Object.entries(data).forEach(([key, item]) => {
              data[key] = arrayObject(item);
            });
            resolve(data);
          })
          .fail((data) => {
            reject(data.responseJSON);
          });
      });
    }

    const opts = normalizeApi4Config(config);
    const signal = opts.signal;
    if (signal?.aborted) {
      return Promise.reject(getAbortReason(signal));
    }

    const maxWait = opts.delay === true ? 800 :
      (typeof opts.delay === 'number' && opts.delay >= 0 ? opts.delay : null);

    if (maxWait !== null) {
      if (typeof params !== 'function') {
        const resolvedParams = params || {};
        const ttl = resolveCacheTtl(opts.cache, isGetAction(entity, action, resolvedParams));
        if (ttl > 0) {
          const key = JSON.stringify(buildApi4Call(entity, action, resolvedParams, opts.index));
          const cached = getCachedResponse(key);
          if (cached !== undefined) {
            return Promise.resolve(cached);
          }
        }
      }
      return new Promise((resolve, reject) => {
        const item = {entity, action, params, index: opts.index, cache: opts.cache, ttl: 0, signal, owner: null, resolve, reject};
        attachAbortHandler(item);
        backgroundQueue.push(item);
        const deadline = Date.now() + maxWait;
        if (deadline < backgroundDeadline) {
          backgroundDeadline = deadline;
          clearTimeout(backgroundTimer);
          backgroundTimer = setTimeout(sendBackgroundQueue, maxWait);
        }
      });
    }

    let resolvedParams;
    try {
      resolvedParams = typeof params === 'function' ? params() : params;
    }
    catch (error) {
      return Promise.reject(error);
    }

    const readOnly = isGetAction(entity, action, resolvedParams);
    if (!readOnly) {
      clearApi4Cache();
    }

    const ttl = resolveCacheTtl(opts.cache, readOnly);
    const key = ttl !== false ? JSON.stringify(buildApi4Call(entity, action, resolvedParams || {}, opts.index)) : null;

    if (key && ttl > 0) {
      const cached = getCachedResponse(key);
      if (cached !== undefined) {
        return Promise.resolve(cached);
      }
    }

    return new Promise((resolve, reject) => {
      const sub = {ttl: ttl || 0, signal, owner: null, resolve, reject};
      attachAbortHandler(sub);

      if (key) {
        const existing = inFlightCache.get(key);
        if (existing) {
          existing.subscribers.push(sub);
          existing.ttl = Math.max(existing.ttl, sub.ttl);
          sub.owner = existing;
          return;
        }
      }

      const owner = {cacheKey: key, ttl: ttl || 0, generation: cacheGeneration, subscribers: [sub], jqXHR: null};
      sub.owner = owner;
      if (key) {
        inFlightCache.set(key, owner);
      }

      owner.jqXHR = $.post(CRM.url('civicrm/ajax/api4/' + entity + '/' + action), {
        params: JSON.stringify(resolvedParams),
        index: opts.index
      })
        .done((data) => {
          if (owner.cacheKey && inFlightCache.get(owner.cacheKey) === owner) {
            inFlightCache.delete(owner.cacheKey);
          }
          const result = arrayObject(data);
          if (owner.cacheKey && owner.ttl > 0 && owner.generation === cacheGeneration) {
            responseCache.set(owner.cacheKey, {result, expires: Date.now() + owner.ttl});
          }
          owner.subscribers.splice(0).forEach((s) => {
            s.cleanup?.();
            s.resolve(result);
          });
        })
        .fail((data) => {
          if (owner.cacheKey && inFlightCache.get(owner.cacheKey) === owner) {
            inFlightCache.delete(owner.cacheKey);
          }
          owner.subscribers.splice(0).forEach((s) => {
            s.cleanup?.();
            s.reject(data.responseJSON);
          });
        });
    });
  };

  /**
   * AJAX api
   * @link https://docs.civicrm.org/dev/en/latest/api/interfaces/#ajax
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
      type: params.action.indexOf('get') === 0 ? 'GET' : 'POST'
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

  $.widget('civi.crmSnippet', {
    options: {
      url: null,
      block: true,
      post: null,
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
      $.each((newUrl.split('?')[1] || '').split('&'), function(k, v) {
        var arg = v.split('=');
        args[arg[0]] = arg[1];
      });
      $.each((oldUrl.split('?')[1] || '').split('&'), function(k, v) {
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
    _onFailure: function(data, status) {
      var msg, title = ts('Network Error');
      if (this.options.block) this.element.unblock();
      this.element.trigger('crmAjaxFail', data);
      switch (status) {
        case 'Forbidden':
          title = ts('Access Denied');
          msg = ts('Ensure you are still logged in and have permission to access this feature.');
          break;
        default:
          msg = ts('Unable to reach the server. Please refresh this page in your browser and try again.');
      }
      CRM.alert(msg, title, 'error');
    },
    _onError: function(data) {
      this.element.attr('data-unsaved-changes', 'false').trigger('crmAjaxError', data);
      if (this.options.crmForm && this.options.crmForm.autoClose && this.element.data('uiDialog')) {
        this.element.dialog('close');
      }
    },
    _formatUrl: function(url, snippetType) {
      // Strip hash
      url = url.split('#')[0];
      // Add snippet argument to url
      if (snippetType) {
        if (url.search(/[&?]snippet=/) < 0) {
          url += (url.indexOf('?') < 0 ? '?' : '&') + 'snippet=' + snippetType;
        } else {
          url = url.replace(/([&?])snippet=[^&]*/, '$1snippet=' + snippetType);
        }
        // See Civi\Angular\AngularLoader
        if (snippetType === 'json' && CRM.angular) {
          url += '&crmAngularModules=' + CRM.angular.modules.join();
        }
      }
      return url;
    },
    // Hack to deal with civicrm legacy sort functionality
    _handleOrderLinks: function() {
      var that = this;
      $('a.crm-weight-arrow', that.element).click(function(e) {
        if (that.options.block) that.element.block();
        $.getJSON(that._formatUrl(this.href, 'json')).done(function() {
          that.refresh();
        });
        e.stopImmediatePropagation();
        return false;
      });
    },
    _ajax: function(url) {
      if (!this.options.post || !this.isOriginalUrl()) {
        return $.getJSON(url);
      }
      return $.post({
        url: url,
        dataType: 'json',
        data: this.options.post
      });
    },
    refresh: function() {
      var that = this,
        hash = this.options.url.split('#')[1],
        url = this._formatUrl(this.options.url, 'json');
      $(this.element).data('urlHash', hash);
      if (this.options.crmForm) $('form', this.element).ajaxFormUnbind();
      if (this.options.block) this.element.block();
      this._ajax(url).then(function(data) {
        if (data.status === 'redirect') {
          that.options.url = data.userContext;
          return that.refresh();
        }
        if (that.options.block) that.element.unblock();
        if (!$.isPlainObject(data)) {
          that._onFailure(data);
          return;
        }
        if (data.status === 'error') {
          that._onError(data);
          return;
        }
        if (data.settings) {
          $.extend(true, CRM, data.settings);
        }
        if (data.scriptUrls) {
          data.scriptUrls.forEach(function(scriptUrl) {
            if ($('script[src="' + scriptUrl + '"]').length === 0) {
              $('<script type="text/javascript" src="' + scriptUrl + '"></script>').appendTo('head');
            }
          });
        }
        if (data.styleUrls) {
          data.styleUrls.forEach(function(styleUrl) {
            if ($('link[href="' + styleUrl + '"]').length === 0) {
              $('<link rel="stylesheet" href="' + styleUrl + '">').appendTo('head');
            }
          });
        }
        data.url = url;
        that.element.trigger('crmUnload').trigger('crmBeforeLoad', data);
        that._beforeRemovingContent();
        that.element.html(data.content);
        that._handleOrderLinks();
        that.element.trigger('crmLoad', data);
        if (that.options.crmForm) that.element.trigger('crmFormLoad', data);
        // This is only needed by forms that load via ajax but submit without ajax, e.g. configure contribution page tabs
        // TODO: remove this when those forms have been converted to use ajax submit
        if (data.status === 'form_error' && $.isPlainObject(data.errors)) {
          that.element.trigger('crmFormError', data);
          $.each(data.errors, function(formElement, msg) {
            $('[name="'+formElement+'"]', that.element).crmError(msg);
          });
        }
      }, function(data, msg, status) {
        that._onFailure(data, status);
      });
    },
    // Perform any cleanup needed before removing/replacing content
    _beforeRemovingContent: function() {
      var that = this;
      // Save original content to be restored if widget is destroyed
      if (this._originalContent === null) {
        $('.blockUI', this.element).remove();
        this._originalContent = this.element.contents().detach();
      }
      if (this.options.crmForm) $('form', this.element).ajaxFormUnbind();
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
    exclude = '[href^="#"], [href^=javascript], [onclick], .no-popup, .cancel';

  CRM.loadPage = function(url, options) {
    var settings = {
      target: '#crm-ajax-dialog-' + (dialogCount++),
      dialog: (options && options.target) ? false : {}
    };
    if (options) $.extend(true, settings, options);
    settings.url = url;
    // Create new dialog
    if (settings.dialog) {
      settings.dialog = CRM.utils.adjustDialogDefaults(settings.dialog);
      $('<div id="' + settings.target.substring(1) + '"></div>')
        .dialog(settings.dialog)
        .parent().find('.ui-dialog-titlebar')
        .append($('<a class="crm-dialog-titlebar-print ui-dialog-titlebar-close" title="'+ts('Print window')+'" target="_blank" style="right:3.8em;"/>')
          .button({icons: {primary: 'fa-print'}, text: false}));
    }
    // Add handlers to new or existing dialog
    if ($(settings.target).data('uiDialog')) {
      $(settings.target)
        .on('dialogclose', function() {
          if (settings.dialog && $(this).attr('data-unsaved-changes') !== 'true') {
            $(this).crmSnippet('destroy').dialog('destroy').remove();
          }
        })
        .on('crmLoad', function(e, data) {
          // Set title
          if (e.target === $(settings.target)[0] && data && !settings.dialog.title && data.title) {
            $(this).dialog('option', 'title', data.title);
          }
          // Update print url
          $(this).parent().find('a.crm-dialog-titlebar-print').attr('href', $(this).data('civiCrmSnippet')._formatUrl($(this).crmSnippet('option', 'url'), '1'));
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
        refreshAction: ['next_new', 'upload_new'],
        cancelButton: '.cancel',
        openInline: 'a.open-inline, a.button, a.action-item, a.open-inline-noreturn',
        onCancel: function(event) {}
      }
    };
    // Move options that belong to crmForm. Others will be passed through to crmSnippet
    if (options) $.each(options, function(key, value) {
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
        $('#' + id).button({icons: {primary: 'fa-undo'}}).click(function(e) {
          widget.attr('data-unsaved-changes', 'false').dialog('open');
          e.preventDefault();
        });
      }
    }

    if (widget.data('uiDialog')) widget.on('dialogbeforeclose', function(e) {
      // CRM-14353 - Warn unsaved changes if user clicks close button or presses "esc"
      if (e.originalEvent) {
        cancelAction();
      }
    });

    widget.on('crmFormLoad.crmForm', function(event, data) {
      var $el = $(this).attr('data-unsaved-changes', 'false'),
        settings = $el.crmSnippet('option', 'crmForm');
      if (settings.cancelButton) $(settings.cancelButton, this).click(function(e) {
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
        // Validate all forms that are not angular-based
        $("form", this).not('crm-angular-js *').crmValidate();
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
            if ($el.crmSnippet('option', 'block')) $el.unblock();
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
        beforeSubmit: function(submission) {
          $.each(formErrors, function() {
            if (this && this.close) this.close();
          });
          if ($el.crmSnippet('option', 'block')) $el.block();
          $el.trigger('crmFormSubmit', submission);
        }
      }, settings.ajaxForm));
      if (settings.openInline) {
        settings.autoClose = $el.crmSnippet('isOriginalUrl');
        $(this).off('.openInline').on('click.openInline', settings.openInline, function(e) {
          if ($(this).is(exclude + ', .crm-popup, [target=crm-popup]')) {
            return;
          }
          if ($(this).hasClass('open-inline-noreturn')) {
            // Force reset of original url
            $el.data('civiCrmSnippet')._originalUrl = $(this).attr('href');
          }
          $el.crmSnippet('option', 'url', $(this).attr('href')).crmSnippet('refresh');
          e.preventDefault();
        });
      }
      if ($el.data('uiDialog')) {
        // Show form buttons as part of the dialog
        var buttonContainers = '.crm-submit-buttons, .action-link',
          buttons = [],
          added = [];
        $(buttonContainers, $el).find('.crm-form-submit, .crm-form-xbutton, a.button, button').each(function() {
          var $el = $(this),
            label = $el.is('input') ? $el.attr('value') : $el.text(),
            identifier = $el.attr('name') || $el.attr('href');
          $el.attr('tabindex', '-1');
          if (!identifier || identifier === '#' || $.inArray(identifier, added) < 0) {
            var $icon = $el.find('.icon, .crm-i'),
              button = {'data-identifier': identifier, text: label, click: function() {
                $el[0].click();
              }};
            if ($icon.length) {
              button.icons = {primary: $icon.attr('class')};
            } else {
              var action = $el.attr('crm-icon') || ($el.hasClass('cancel') ? 'fa-times' : 'fa-check');
              button.icons = {primary: action};
            }
            buttons.push(button);
            added.push(identifier);
          }
          // display:none causes the form to not submit when pressing "enter"
          $el.parents(buttonContainers).css({height: 0, padding: 0, margin: 0, overflow: 'hidden'}).attr('aria-hidden', 'true');
        });
        $el.dialog('option', 'buttons', buttons);
      }
      // Allow a button to prevent ajax submit
      $('input[data-no-ajax-submit=true], button[data-no-ajax-submit=true]').click(function() {
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
      formData = false;
    settings.dialog = settings.dialog || {};
    if (e.isDefaultPrevented() || !CRM.config.ajaxPopupsEnabled || !url || $el.is(exclude + ', .open-inline, .open-inline-noreturn')) {
      return;
    }
    // Sized based on css class
    if ($el.hasClass('small-popup')) {
      settings.dialog.width = 400;
      settings.dialog.height = 300;
    }
    else if ($el.hasClass('medium-popup')) {
      settings.dialog.width = '50%';
      settings.dialog.height = '30%';
    }
    var dialog = popup(url, settings);
    // Trigger events from the dialog on the original link element
    $el.trigger('crmPopupOpen', [dialog]);
    // Listen for success events and buffer them so we only trigger once
    dialog.on('crmFormSuccess.crmPopup crmPopupFormSuccess.crmPopup', function(e, data) {
      formData = data;
    });
    dialog.on('dialogclose.crmPopup', function(e, data) {
      if (formData) {
        $el.trigger('crmPopupFormSuccess', [dialog, formData]);
      }
      $el.trigger('crmPopupClose', [dialog, data]);
    });
    e.preventDefault();
  };
  /**
   * An event callback for CRM.popup or a standalone function to refresh the content around a given element
   * @param e {*} an event, or anything jQuery accepts (selector, element, jQuery object)
   */
  CRM.refreshParent = function(e) {
    // Use e.target if input smells like an event, otherwise assume it's a jQuery selector
    var $el = (e.stopPropagation && e.target) ? $(e.target) : $(e),
      $table = $el.closest('.dataTable');
    // Call native refresh method on ajax datatables
    if ($table.length && $.fn.DataTable.fnIsDataTable($table[0]) && $table.dataTable().fnSettings().sAjaxSource) {
      // Refresh ALL datatables - needed for contact relationship tab
      $.each($.fn.dataTable.fnTables(), function() {
        if ($(this).dataTable().fnSettings().sAjaxSource) $(this).unblock().dataTable().fnDraw();
      });
    }
    // Otherwise refresh the nearest crmSnippet
    else {
      $el.closest('.crm-ajax-container, #crm-main-content-wrapper').crmSnippet().crmSnippet('refresh');
    }
  };

  $(function($) {
    $('body')
      .on('click', 'a.crm-popup, a[target=crm-popup]', CRM.popup)
      // Close unsaved dialog messages
      .on('dialogopen', function(e) {
        $('.alert.unsaved-dialog .ui-notify-cross', '#crm-notification-container').click();
      })
      // Destroy old unsaved dialog
      .on('dialogcreate', function(e) {
        $('.ui-dialog-content.crm-ajax-container:hidden[data-unsaved-changes=true]').crmSnippet('destroy').dialog('destroy').remove();
      })
      // Ensure wysiwyg content is updated prior to ajax submit
      .on('form-pre-serialize', function(e) {
        $('.crm-wysiwyg-enabled', e.target).each(function() {
          CRM.wysiwyg.updateElement(this);
        });
      })
      // Auto-resize dialogs when loading content
      .on('crmLoad dialogopen', 'div.ui-dialog.ui-resizable.crm-container', function(e) {
        var
          $wrapper = $(this),
          $dialog = $wrapper.children('.ui-dialog-content');
        // small delay to allow contents to render
        window.setTimeout(function() {
          var currentHeight = $wrapper.outerHeight(),
            padding = currentHeight - $dialog.innerHeight(),
            newHeight = $dialog.prop('scrollHeight') + padding,
            menuHeight = $('#civicrm-menu').outerHeight();
          if ($('body').hasClass('crm-menubar-below-cms-menu')) {
            menuHeight += $('#civicrm-menu').offset().top;
          }
          var maxHeight = $(window).height() - menuHeight;
          newHeight = newHeight > maxHeight ? maxHeight : newHeight;
          if (newHeight > (currentHeight + 15)) {
            var at = $wrapper.css(['top', 'left']);
            $dialog.dialog('option', 'height', newHeight);
            // A theme's !important height can keep the dialog from growing. Setting the height re-applies the
            // stored position, so a dialog that did not grow is put back where it was
            if ($wrapper.outerHeight() > currentHeight + 1) {
              $dialog.dialog('option', 'position', {my: 'center', at: 'center center+' + (menuHeight / 2), of: window});
            }
            else {
              $wrapper.css(at);
            }
          }
        }, 500);
      });
  });

}(jQuery, CRM));
