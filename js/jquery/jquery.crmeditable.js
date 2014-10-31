/**
 * Copyright (C) 2012 Xavier Dutoit
 * Licensed to CiviCRM under the Academic Free License version 3.0.
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/Structure+convention+for+automagic+edit+in+place
 */
(function($) {
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

  $.fn.crmEditable = function(options) {
    var checkable = function() {
      $(this).change(function() {
        var info = $(this).crmEditableEntity();
        if (!info.field) {
          return false;
        }
        var checked = $(this).is(':checked');
        var params = {
          sequential: 1,
          id: info.id,
          field: info.field,
          value: checked ? 1 : 0
        };
        CRM.api(info.entity, info.action, params, {
          context: this,
          error: function(data) {
            editableSettings.error.call(this, info.entity, info.field, checked, data);
          },
          success: function(data) {
            editableSettings.success.call(this, info.entity, info.field, checked, data);
          }
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
        CRM.status(ts('Saved'));
        $i.removeClass('crm-editable-saving crm-error');
        value = value === '' ? settings.placeholder : value;
        $i.html(value);
      }
    };

    var editableSettings = $.extend({}, defaults, options);
    return this.each(function() {
      var $i = $(this);
      var fieldName = "";

      if (this.nodeName == "INPUT" && this.type == "checkbox") {
        checkable.call(this, this);
        return;
      }

      var settings = {
        tooltip: 'Click to edit...',
        placeholder: '<span class="crm-editable-placeholder">Click to edit</span>',
        data: function(value, settings) {
          return value.replace(/<(?:.|\n)*?>/gm, '');
        }
      };
      if ($i.data('placeholder')) {
        settings.placeholder = $i.data('placeholder');
      } else {
        settings.placeholder = '<span class="crm-editable-placeholder">Click to edit</span>';
      }
      if ($i.data('tooltip')) {
        settings.placeholder = $i.data('tooltip')
      } else {
        settings.tooltip = 'Click to edit...';
      }
      if ($i.data('type')) {
        settings.type = $i.data('type');
        settings.onblur = 'submit';
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
        CRM.api(info.entity, action, params, {
          context: this,
          error: function(data) {
            editableSettings.error.call(this, info.entity, info.field, value, data);
          },
          success: function(data) {
            if ($i.data('options')) {
              value = $i.data('options')[value];
            }
            $i.trigger('crmFormSuccess');
            editableSettings.success.call(this, info.entity, info.field, value, data, settings);
          }
        });
      }, settings);
    });
  };

})(jQuery);
