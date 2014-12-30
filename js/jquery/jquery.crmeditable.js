// https://civicrm.org/licensing
(function($) {
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
      ret.action = $row.data('action') || 'setvalue';

    if (!ret.entity || !ret.id) {
      return false;
    }
    $('.crm-editable, [data-field]', $row).each(function() {
      var fieldName = $(this).data('field') || this.className.match(/crmf-(\S*)/)[1];
      if (fieldName) {
        ret[fieldName] = $(this).text();
        if (this === el) {
          ret.field = fieldName;
        }
      }
    });
    return ret;
  };

  /**
   * @see http://wiki.civicrm.org/confluence/display/CRMDOC/Structure+convention+for+automagic+edit+in+place
   */
  $.fn.crmEditable = function(options) {
    var checkable = function() {
      $(this).change(function() {
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
        CRM.api3(info.entity, info.action, params, true)
          .fail(function(data) {
            editableSettings.error.call($el[0], info.entity, info.field, checked, data);
          })
          .done(function(data) {
            editableSettings.success.call($el[0], info.entity, info.field, checked, data);
          });
      });
    };

    var defaults = {
      form: {},
      callBack: function(data) {
        if (data.is_error) {
          editableSettings.error.call(this, data);
        } else {
          return editableSettings.success.call(this, data);
        }
      },
      error: function(entity, field, value, data) {
        $(this).crmError(data.error_message, ts('Error'));
        $(this).removeClass('crm-editable-saving');
      },
      success: function(entity, field, value, data, settings) {
        var $i = $(this);
        $i.removeClass('crm-editable-saving crm-error');
        value = value === '' ? settings.placeholder : value;
        $i.html(value);
      }
    };

    var editableSettings = $.extend({}, defaults, options);
    return this.each(function() {
      var $i,
        fieldName = "";

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
        placeholder: $i.data('placeholder') || '<span class="crm-editable-placeholder">' + ts('Click to edit') + '</span>',
        onblur: 'cancel',
        cancel: '<button type="cancel"><span class="ui-icon ui-icon-closethick"></span></button>',
        submit: '<button type="submit"><span class="ui-icon ui-icon-check"></span></button>',
        cssclass: 'crm-editable-form',
        data: function(value, settings) {
          return value.replace(/<(?:.|\n)*?>/gm, '');
        }
      };
      if ($i.data('type')) {
        settings.type = $i.data('type');
      }
      if ($i.data('options')) {
        settings.data = $i.data('options');
      }
      if (settings.type == 'textarea') {
        $i.addClass('crm-editable-textarea-enabled');
      }
      else {
        $i.addClass('crm-editable-enabled');
      }

      $i.editable(function(value, settings) {
        $i.addClass('crm-editable-saving');
        var
          info = $i.crmEditableEntity(),
          $el = $($i),
          params = {},
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
        CRM.api3(info.entity, action, params, true)
          .done(function(data) {
            if ($el.data('options')) {
              value = $el.data('options')[value];
            }
            $el.trigger('crmFormSuccess');
            editableSettings.success.call($el[0], info.entity, info.field, value, data, settings);
          })
          .fail(function(data) {
            editableSettings.error.call($el[0], info.entity, info.field, value, data);
          });
      }, settings);

      // CRM-15759 - Workaround broken textarea handling in jeditable 1.7.1
      $i.click(function() {
        $('textarea', this).off()
          .on('blur', function() {
            $i.find('button[type=cancel]').click();
          })
          .on('keydown', function (e) {
            if (e.ctrlKey && e.keyCode == 13) {
              // Ctrl-Enter pressed
              $i.find('button[type=submit]').click();
              e.preventDefault();
            }
          });
      });
    });
  };

})(jQuery);
