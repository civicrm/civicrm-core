// https://civicrm.org/licensing
/* global CRM, ts */
/*jshint loopfunc: true */
(function($) {
  'use strict';
  // Constructor for dashboard object.
  $.fn.dashboard = function(options) {
    // Public properties of dashboard.
    var dashboard = {};
    dashboard.element = this.empty();
    dashboard.ready = false;
    dashboard.columns = [];
    dashboard.widgets = {};

    /**
     * Public methods of dashboard.
     */

    // Saves the order of widgets for all columns including the widget.minimized status to options.ajaxCallbacks.saveColumns.
    dashboard.saveColumns = function(showStatus) {
      // Update the display status of the empty placeholders.
      $.each(dashboard.columns, function(c, col) {
        if ( typeof col == 'object' ) {
          // Are there any visible children of the column (excluding the empty placeholder)?
          if (col.element.children(':visible').not(col.emptyPlaceholder).length > 0) {
            col.emptyPlaceholder.hide();
          }
          else {
            col.emptyPlaceholder.show();
          }
        }
      });

      // Don't save any changes to the server unless the dashboard has finished initiating.
      if (!dashboard.ready) {
        return;
      }

      // Build a list of params to post to the server.
      var params = {};

      // For each column...
      $.each(dashboard.columns, function(c, col) {

        // IDs of the sortable elements in this column.
        var ids = (typeof col == 'object') ? col.element.sortable('toArray') : [];

        // For each id...
        $.each(ids, function(w, id) {
          if (typeof id == 'string') {
            // Chop 'widget-' off of the front so that we have the real widget id.
            id = id.substring('widget-'.length);
            // Add one flat property to the params object that will look like an array element to the PHP server.
            // Unfortunately jQuery doesn't do this for us.
            if (typeof dashboard.widgets[id] == 'object') params['columns[' + c + '][' + id + ']'] = (dashboard.widgets[id].minimized ? '1' : '0');
          }
        });
      });

      // The ajaxCallback settings overwrite any duplicate properties.
      $.extend(params, opts.ajaxCallbacks.saveColumns.data);
      var post = $.post(opts.ajaxCallbacks.saveColumns.url, params, function() {
        invokeCallback(opts.callbacks.saveColumns, dashboard);
      });
      if (showStatus !== false) {
        CRM.status({}, post);
      }
    };

    /**
     * Private properties of dashboard.
     */

    // Used to determine whether two resort events are resulting from the same UI event.
    var currentReSortEvent = null;

    // Merge in the caller's options with the defaults.
    var opts = $.extend({}, $.fn.dashboard.defaults, options);

    var localCache = window.localStorage && localStorage.dashboard ? JSON.parse(localStorage.dashboard) : {};

    init(opts.widgetsByColumn);

    return dashboard;

    /**
     * Private methods of dashboard.
     */

    // Initialize widget columns.
    function init(widgets) {
      var markup = '<li class="empty-placeholder">' + opts.emptyPlaceholderInner + '</li>';

      // Build the dashboard in the DOM.  For each column...
      // (Don't iterate on widgets since this will break badly if the dataset has empty columns.)
      var emptyDashboard = true;
      for (var c = 0; c < opts.columns; c++) {
          // Save the column to both the public scope for external accessibility and the local scope for readability.
          var col = dashboard.columns[c] = {
              initialWidgets: [],
              element: $('<ul id="column-' + c + '" class="column column-' + c + '"></ul>').appendTo(dashboard.element)
          };

          // Add the empty placeholder now, hide it and save it.
          col.emptyPlaceholder = $(markup).appendTo(col.element).hide();

          // For each widget in this column.
          $.each(widgets[c], function(num, item) {
            var id = (num+1) + '-' + item.id;
            col.initialWidgets[id] = dashboard.widgets[item.id] = widget($.extend({
              element: $('<li class="widget"></li>').appendTo(col.element),
              initialColumn: col
            }, item));
            emptyDashboard = false;
          });
      }

      if (emptyDashboard) {
        emptyDashboardCondition();
      } else {
        completeInit();
      }

      invokeCallback(opts.callbacks.init, dashboard);
    }

    // function that is called when dashboard is empty
    function emptyDashboardCondition( ) {
        $(".show-refresh").hide( );
        $("#empty-message").show( );
    }

    // Cache dashlet info in localStorage
    function saveLocalCache() {
      localCache = {};
      $.each(dashboard.widgets, function(id, widget) {
        localCache[id] = {
          content: widget.content,
          lastLoaded: widget.lastLoaded,
          minimized: widget.minimized
        };
      });
      if (window.localStorage) {
        localStorage.dashboard = JSON.stringify(localCache);
      }
    }

    // Contructors for each widget call this when initialization has finished so that dashboard can complete it's intitialization.
    function completeInit() {
      // Only do this once.
      if (dashboard.ready) {
          return;
      }

      // Make widgets sortable across columns.
      dashboard.sortableElement = $('.column').sortable({
        connectWith: ['.column'],

        // The class of the element by which widgets are draggable.
        handle: '.widget-header',

        // The class of placeholder elements (the 'ghost' widget showing where the dragged item would land if released now.)
        placeholder: 'placeholder',
        activate: function(event, ui) {
          var h= $(ui.item).height();
          $('.placeholder').css('height', h +'px');
        },

        opacity: 0.2,

        // Maks sure that only widgets are sortable, and not empty placeholders.
        items: '> .widget',

        forcePlaceholderSize: true,

        // Callback functions.
        update: resorted,
        start: hideEmptyPlaceholders
      });

      // Update empty placeholders.
      dashboard.saveColumns();
      dashboard.ready = true;
      invokeCallback(opts.callbacks.ready, dashboard);

      // Auto-refresh widgets when content is stale
      window.setInterval(function() {
        if (!document.hasFocus || document.hasFocus()) {
          $.each(dashboard.widgets, function (i, widget) {
            if (!widget.cacheIsFresh()) {
              widget.reloadContent();
            }
          });
        }
      }, 5000);
    }

    // Callback for when any list has changed (and the user has finished resorting).
    function resorted(e, ui) {
        // Only do anything if we haven't already handled resorts based on changes from this UI DOM event.
        // (resorted() gets invoked once for each list when an item is moved from one to another.)
        if (!currentReSortEvent || e.originalEvent != currentReSortEvent) {
            currentReSortEvent = e.originalEvent;
            dashboard.saveColumns();
        }
    }

    // Callback for when a user starts resorting a list.  Hides all the empty placeholders.
    function hideEmptyPlaceholders(e, ui) {
        for (var c in dashboard.columns) {
            if( (typeof dashboard.columns[c]) == 'object' ) dashboard.columns[c].emptyPlaceholder.hide();
        }
    }

    // @todo use an event library to register, bind to and invoke events.
    //  @param callback is a function.
    //  @param theThis is the context given to that function when it executes.  It becomes 'this' inside of that function.
    function invokeCallback(callback, theThis, parameterOne) {
        if (callback) {
            callback.call(theThis, parameterOne);
        }
    }

    /**
     * widget object
     *    Private sub-class of dashboard
     * Constructor starts
     */
    function widget(widget) {
      // Merge default options with the options defined for this widget.
      widget = $.extend({}, $.fn.dashboard.widget.defaults, localCache[widget.id] || {}, widget);

      /**
       * Public methods of widget.
       */

      // Toggles the minimize() & maximize() methods.
      widget.toggleMinimize = function() {
        if (widget.minimized) {
          widget.maximize();
        }
        else {
          widget.minimize();
        }

        widget.hideSettings();
      };
      widget.minimize = function() {
        $('.widget-content', widget.element).slideUp(opts.animationSpeed);
        $(widget.controls.minimize.element)
          .addClass('fa-caret-right')
          .removeClass('fa-caret-down')
          .attr('title', ts('Expand'));
        widget.minimized = true;
        saveLocalCache();
      };
      widget.maximize = function() {
        $(widget.controls.minimize.element)
          .removeClass( 'fa-caret-right' )
          .addClass( 'fa-caret-down' )
          .attr('title', ts('Collapse'));
        widget.minimized = false;
        saveLocalCache();
        if (!widget.contentLoaded) {
          loadContent();
        }
        $('.widget-content', widget.element).slideDown(opts.animationSpeed);
      };

      // Toggles whether the widget is in settings-display mode or not.
      widget.toggleSettings = function() {
        if (widget.settings.displayed) {
          // Widgets always exit settings into maximized state.
          widget.maximize();
          widget.hideSettings();
          invokeCallback(opts.widgetCallbacks.hideSettings, widget);
        }
        else {
          widget.minimize();
          widget.showSettings();
          invokeCallback(opts.widgetCallbacks.showSettings, widget);
        }
      };
      widget.showSettings = function() {
        if (widget.settings.element) {
          widget.settings.element.show();

          // Settings are loaded via AJAX.  Only execute the script if the settings have been loaded.
          if (widget.settings.ready) {
            getJavascript(widget.settings.script);
          }
        }
        else {
          // Settings have not been initialized.  Do so now.
          initSettings();
        }
        widget.settings.displayed = true;
      };
      widget.hideSettings = function() {
        if (widget.settings.element) {
          widget.settings.element.hide();
        }
        widget.settings.displayed = false;
      };
      widget.saveSettings = function() {
        // Build list of parameters to POST to server.
        var params = {};
        // serializeArray() returns an array of objects.  Process it.
        var fields = widget.settings.element.serializeArray();
        $.each(fields, function(i, field) {
            // Put the values into flat object properties that PHP will parse into an array server-side.
            // (Unfortunately jQuery doesn't do this)
            params['settings[' + field.name + ']'] = field.value;
        });

        // Things get messy here.
        // @todo Refactor to use currentState and targetedState properties to determine what needs
        // to be done to get to any desired state on any UI or AJAX event – since these don't always
        // match.
        // E.g.  When a user starts a new UI event before the Ajax event handler from a previous
        // UI event gets invoked.

        // Hide the settings first of all.
        widget.toggleSettings();
        // Save the real settings element so that we can restore the reference later.
        var settingsElement = widget.settings.element;
        // Empty the settings form.
        widget.settings.innerElement.empty();
        initThrobber();
        // So that showSettings() and hideSettings() can do SOMETHING, without showing the empty settings form.
        widget.settings.element = widget.throbber.hide();
        widget.settings.ready = false;

        // Save the settings to the server.
        $.extend(params, opts.ajaxCallbacks.widgetSettings.data, { id: widget.id });
        $.post(opts.ajaxCallbacks.widgetSettings.url, params, function(response, status) {
          // Merge the response into widget.settings.
          $.extend(widget.settings, response);
          // Restore the reference to the real settings element.
          widget.settings.element = settingsElement;
          // Make sure the settings form is empty and add the updated settings form.
          widget.settings.innerElement.empty().append(widget.settings.markup);
          widget.settings.ready = true;

          // Did the user already jump back into settings-display mode before we could finish reloading the settings form?
          if (widget.settings.displayed) {
            // Ooops!  We had better take care of hiding the throbber and showing the settings form then.
            widget.throbber.hide();
            widget.showSettings();
            invokeCallback(opts.widgetCallbacks.saveSettings, dashboard);
          }
        }, 'json');

        // Don't let form submittal bubble up.
        return false;
      };

      widget.enterFullscreen = function() {
        // Make sure the widget actually supports full screen mode.
        if (widget.fullscreenUrl) {
          CRM.loadPage(widget.fullscreenUrl);
        }
      };

      // Adds controls to a widget.  id is for internal use and image file name in images/dashboard/ (a .gif).
      widget.addControl = function(id, control) {
          var markup = '<a class="crm-i ' + control.icon + '" alt="' + control.description + '" title="' + control.description + '"></a>';
          control.element = $(markup).prependTo($('.widget-controls', widget.element)).click(control.callback);
      };

      // Fetch remote content.
      widget.reloadContent = function() {
        // If minimized, we'll reload later
        if (widget.minimized) {
          widget.contentLoaded = false;
          widget.lastLoaded = 0;
        } else {
          CRM.loadPage(widget.url, {target: widget.contentElement});
        }
      };

      // Removes the widget from the dashboard, and saves columns.
      widget.remove = function() {
        invokeCallback(opts.widgetCallbacks.remove, widget);
        widget.element.fadeOut(opts.animationSpeed, function() {
          $(this).remove();
          delete(dashboard.widgets[widget.id]);
          dashboard.saveColumns(false);
        });
        CRM.alert(
          ts('You can re-add it by clicking the "Configure Your Dashboard" button.'),
          ts('"%1" Removed', {1: widget.title}),
          'success'
        );
      };

      widget.cacheIsFresh = function() {
        return (((widget.cacheMinutes * 60000 + widget.lastLoaded) > $.now()) && widget.content);
      };

      /**
       * Public properties of widget.
       */

      // Default controls.  External script can add more with widget.addControls()
      widget.controls = {
        settings: {
          description: ts('Configure this dashlet'),
          callback: widget.toggleSettings,
          icon: 'fa-wrench'
        },
        minimize: {
          description: widget.minimized ? ts('Expand') : ts('Collapse'),
          callback: widget.toggleMinimize,
          icon: widget.minimized ? 'fa-caret-right' : 'fa-caret-down'
        },
        fullscreen: {
          description: ts('View fullscreen'),
          callback: widget.enterFullscreen,
          icon: 'fa-expand'
        },
        close: {
          description: ts('Remove from dashboard'),
          callback: widget.remove,
          icon: 'fa-times'
        }
      };
      widget.contentLoaded = false;

      init();
      return widget;

      /**
       * Private methods of widget.
       */

      function loadContent() {
        var loadFromCache = widget.cacheIsFresh();
        if (loadFromCache) {
          widget.contentElement.html(widget.content).trigger('crmLoad', widget);
        }
        widget.contentElement.off('crmLoad').on('crmLoad', function(event, data) {
          if ($(event.target).is(widget.contentElement)) {
            widget.content = data.content;
            // Cache for one day
            widget.lastLoaded = $.now();
            saveLocalCache();
            invokeCallback(opts.widgetCallbacks.get, widget);
          }
        });
        if (!loadFromCache) {
          widget.reloadContent();
        }
        widget.contentLoaded = true;
      }

      // Build widget & load content.
      function init() {
        // Delete controls that don't apply to this widget.
        if (!widget.settings) {
          delete widget.controls.settings;
          widget.settings = {};
        }
        if (!widget.fullscreenUrl) {
          delete widget.controls.fullscreen;
        }
        var cssClass = 'widget-' + widget.name.replace('/', '-');
        widget.element.attr('id', 'widget-' + widget.id).addClass(cssClass);
        // Build and add the widget's DOM element.
        $(widget.element).append(widgetHTML());
        // Save the content element so that external scripts can reload it easily.
        widget.contentElement = $('.widget-content', widget.element);
        $.each(widget.controls, widget.addControl);

        if (widget.minimized) {
          widget.contentElement.hide();
        } else {
          loadContent();
        }
      }

      // Builds inner HTML for widgets.
      function widgetHTML() {
        var html = '';
        html += '<div class="widget-wrapper">';
        html += '  <div class="widget-controls"><h3 class="widget-header">' + widget.title + '</h3></div>';
        html += '  <div class="widget-content"></div>';
        html += '</div>';
        return html;
      }

      // Initializes a widgets settings pane.
      function initSettings() {
        // Overwrite widget.settings (boolean).
        initThrobber();
        widget.settings = {
          element: widget.throbber.show(),
          ready: false
        };

        // Get the settings markup and script executables for this widget.
        var params = $.extend({}, opts.ajaxCallbacks.widgetSettings.data, { id: widget.id });
        $.getJSON(opts.ajaxCallbacks.widgetSettings.url, params, function(response, status) {
          $.extend(widget.settings, response);
          // Build and add the settings form to the DOM.  Bind the form's submit event handler/callback.
          widget.settings.element = $(widgetSettingsHTML()).appendTo($('.widget-wrapper', widget.element)).submit(widget.saveSettings);
          // Bind the cancel button's event handler too.
          widget.settings.cancelButton = $('.widget-settings-cancel', widget.settings.element).click(cancelEditSettings);
          // Build and add the inner form elements from the HTML markup provided in the AJAX data.
          widget.settings.innerElement = $('.widget-settings-inner', widget.settings.element).append(widget.settings.markup);
          widget.settings.ready = true;

          if (widget.settings.displayed) {
            // If the user hasn't clicked away from the settings pane, then display the form.
            widget.throbber.hide();
            widget.showSettings();
          }

          getJavascript(widget.settings.initScript);
        });
      }

      // Builds HTML for widget settings forms.
      function widgetSettingsHTML() {
        var html = '';
        html += '<form class="widget-settings">';
        html += '  <div class="widget-settings-inner"></div>';
        html += '  <div class="widget-settings-buttons">';
        html += '    <input id="' + widget.id + '-settings-save" class="widget-settings-save" value="Save" type="submit" />';
        html += '    <input id="' + widget.id + '-settings-cancel" class="widget-settings-cancel" value="Cancel" type="submit" />';
        html += '  </div>';
        html += '</form>';
        return html;
      }

      // Initializes a generic widget content throbber, for use by settings form and external scripts.
      function initThrobber() {
        if (!widget.throbber) {
          widget.throbber = $(opts.throbberMarkup).appendTo($('.widget-wrapper', widget.element));
        }
      }

      // Event handler/callback for cancel button clicks.
      // @todo test this gets caught by all browsers when the cancel button is 'clicked' via the keyboard.
      function cancelEditSettings() {
        widget.toggleSettings();
        return false;
      }

      // Helper function to execute external script on the server.
      // @todo It would be nice to provide some context to the script.  How?
      function getJavascript(url) {
        if (url) {
          $.getScript(url);
        }
      }
    }
  };

  // Public static properties of dashboard.  Default settings.
  $.fn.dashboard.defaults = {
    columns: 2,
    emptyPlaceholderInner: '',
    throbberMarkup: '',
    animationSpeed: 200,
    callbacks: {},
    widgetCallbacks: {}
  };

  // Default widget settings.
  $.fn.dashboard.widget = {
    defaults: {
      minimized: false,
      content: null,
      lastLoaded: 0,
      settings: false
      // id, url, fullscreenUrl, title, name, cacheMinutes
    }
  };
})(jQuery);
