// https://civicrm.org/licensing
(function($, _) {
  "use strict";
  /* jshint validthis: true */

  // TODO: We'll need a way to clear this cache if options are edited.
  // Maybe it should be stored in the CRM object so other parts of the app can use it.
  // Note that if we do move it, we should also change the format of option lists to our standard sequential arrays
  var optionsCache = {};

  /**
   * Helper fn to retrieve semantic data from markup
   */
  $.fn.crmEditableEntity = function() {
    var
      el = this[0],
      ret = {},
      $row = this.first().closest('.crm-entity');
      ret.entity = $row.data('entity') || $row[0].id.split('-')[0];
      ret.id = $row.data('id') || $row[0].id.split('-')[1];
      ret.action = $row.data('action') || 'create';

    if (!ret.entity || !ret.id) {
      return false;
    }
    $('.crm-editable, [data-field]', $row).each(function() {
      var fieldName = $(this).data('field') || this.className.match(/crmf-(\S*)/)[1];
      if (fieldName) {
        ret[fieldName] = $(this).text();
        if (this === el) {
          ret.field = fieldName;
          ret.params = $(this).data('params');
        }
      }
    });
    return ret;
  };

  /**
   * @see https://docs.civicrm.org/dev/en/latest/framework/ui/#in-place-field-editing
   */
  $.fn.crmEditable = function(options) {
    function checkable() {
      $(this).off('.crmEditable').on('change.crmEditable', function() {
        var $el = $(this),
          info = $el.crmEditableEntity();
        if (!info.field) {
          return false;
        }
        var params = {
          sequential: 1,
          id: info.id,
          field: info.field,
          value: $el.is(':checked') ? 1 : 0
        };
        CRM.api3(info.entity, info.action, params, true);
      });
    }

    return this.each(function() {
      var $i,
        fieldName = "",
        defaults = {
          error: function(entity, field, value, data) {
            restoreContainer();
            $(this).html(originalValue || settings.placeholder).click();
            var msg = $.isPlainObject(data) && data.error_message;
            errorMsg = $(':input', this).first().crmError(msg || ts('Sorry an error occurred and your information was not saved'), ts('Error'));
          },
          success: function(entity, field, value, data, settings) {
            restoreContainer();
            if ($i.data('refresh')) {
              CRM.refreshParent($i);
            } else {
              value = value === '' ? settings.placeholder : _.escape(value);
              $i.html(value);
            }
          }
        },
        originalValue = '',
        errorMsg,
        editableSettings = $.extend({}, defaults, options);

      if ($(this).hasClass('crm-editable-enabled')) {
        return;
      }

      if (this.nodeName == "INPUT" && this.type == "checkbox") {
        checkable.call(this, this);
        return;
      }

      // Table cell needs something inside it to look right
      if ($(this).is('td')) {
        $(this)
          .removeClass('crm-editable')
          .wrapInner('<div class="crm-editable" />');
        $i = $('div.crm-editable', this)
          .data($(this).data());
        var field = this.className.match(/crmf-(\S*)/);
        if (field) {
          $i.data('field', field[1]);
        }
      }
      else {
        $i = $(this);
      }

      var settings = {
        tooltip: $i.data('tooltip') || ts('Click to edit'),
        placeholder: $i.data('placeholder') || '<i class="crm-i fa-pencil crm-editable-placeholder" aria-hidden="true"></i>',
        onblur: 'cancel',
        cancel: '<button type="cancel"><i class="crm-i fa-times" aria-hidden="true"></i></button>',
        submit: '<button type="submit"><i class="crm-i fa-check" aria-hidden="true"></i></button>',
        cssclass: 'crm-editable-form',
        data: getData,
        onreset: restoreContainer
      };
      if ($i.data('type')) {
        settings.type = $i.data('type');
        if (settings.type == 'boolean') {
          settings.type = 'select';
          $i.data('options', {'0': ts('No'), '1': ts('Yes')});
        }
      }
      if (settings.type == 'textarea') {
        $i.addClass('crm-editable-textarea-enabled');
      }
      $i.addClass('crm-editable-enabled');

      function callback(value, settings) {
        $i.addClass('crm-editable-saving');
        var
          info = $i.crmEditableEntity(),
          $el = $($i),
          params = info.params || {},
          action = $i.data('action') || info.action;
        if (!info.field) {
          return false;
        }
        if (info.id && info.id !== 'new') {
          params.id = info.id;
        }
        if (action === 'setvalue') {
          params.field = info.field;
          params.value = value;
        }
        else {
          params[info.field] = value;
        }
        CRM.api3(info.entity, action, params, {error: null})
          .done(function(data) {
            if (data.is_error) {
              return editableSettings.error.call($el[0], info.entity, info.field, value, data);
            }
            if ($el.data('options')) {
              value = $el.data('options')[value] || '';
            }
            else if ($el.data('optionsHashKey')) {
              var options = optionsCache[$el.data('optionsHashKey')];
              value = options && options[value] ? options[value] : '';
            }
            $el.trigger('crmFormSuccess', [value]);
            editableSettings.success.call($el[0], info.entity, info.field, value, data, settings);
          })
          .fail(function(data) {
            editableSettings.error.call($el[0], info.entity, info.field, value, data);
          });
      }

      CRM.loadScript(CRM.config.packagesBase + 'jquery/plugins/jquery.jeditable.min.js').done(function() {
        $i.editable(callback, settings);
      });

      // CRM-15759 - Workaround broken textarea handling in jeditable 1.7.1
      $i.click(function() {
        $('textarea', this).off()
          // Fix cancel-on-blur
          .on('blur', function(e) {
            if (!e.relatedTarget || !$(e.relatedTarget).is('.crm-editable-form button')) {
              $i.find('button[type=cancel]').click();
            }
          })
          // Add support for ctrl-enter shortcut key
          .on('keydown', function (e) {
            if (e.ctrlKey && e.keyCode == 13) {
              $i.find('button[type=submit]').click();
              e.preventDefault();
            }
          });
      });

      function getData(value, settings) {
        // Add css class to wrapper
        // FIXME: This should be a response to an event instead of coupled with this function but jeditable 1.7.1 doesn't trigger any events :(
        $i.addClass('crm-editable-editing');

        originalValue = value;

        if ($i.data('type') == 'select' || $i.data('type') == 'boolean') {
          if ($i.data('options')) {
            return formatOptions($i.data('options'));
          }
          var result,
            info = $i.crmEditableEntity(),
            // Strip extra id from multivalued custom fields
            custom = info.field.match(/(custom_\d+)_\d+/),
            field = custom ? custom[1] : info.field,
            hash = info.entity + '.' + field,
            params = {
              field: field,
              context: 'create'
            };
          $i.data('optionsHashKey', hash);
          if (!optionsCache[hash]) {
            $.ajax({
              url: CRM.url('civicrm/ajax/rest'),
              data: {entity: info.entity, action: 'getoptions', json: JSON.stringify(params)},
              async: false, // jeditable lacks support for async options lookup
              success: function(data) {optionsCache[hash] = data.values;}
            });
          }
          return formatOptions(optionsCache[hash]);
        }
        // Unwrap contents then replace html special characters with plain text
        return _.unescape(value.replace(/<(?:.|\n)*?>/gm, ''));
      }

      function formatOptions(options) {
        if (typeof $i.data('emptyOption') === 'string') {
          // Using 'null' because '' is broken in jeditable 1.7.1
          return $.extend({'null': $i.data('emptyOption')}, options);
        }
        return options;
      }

      function restoreContainer() {
        if (errorMsg && errorMsg.close) errorMsg.close();
        $i.removeClass('crm-editable-saving crm-editable-editing');
      }

    });
  };

})(jQuery, CRM._);
