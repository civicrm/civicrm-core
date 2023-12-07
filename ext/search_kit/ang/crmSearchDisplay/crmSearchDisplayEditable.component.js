// https://civicrm.org/licensing
(function(angular, $, _) {
  "use strict";

  var optionsCache = {};

  angular.module('crmSearchDisplay').component('crmSearchDisplayEditable', {
    bindings: {
      row: '<',
      col: '<',
      cancel: '&'
    },
    templateUrl: '~/crmSearchDisplay/crmSearchDisplayEditable.html',
    controller: function($scope, $element, crmApi4, crmStatus) {
      var ctrl = this,
        initialValue,
        col;

      this.$onInit = function() {
        col = this.col;
        this.value = _.cloneDeep(this.row.data[col.edit.value_path]);
        initialValue = _.cloneDeep(this.row.data[col.edit.value_path]);

        this.field = {
          data_type: col.edit.data_type,
          input_type: col.edit.input_type,
          entity: col.edit.entity,
          name: col.edit.value_key,
          options: col.edit.options,
          fk_entity: col.edit.fk_entity,
          serialize: col.edit.serialize,
          nullable: col.edit.nullable
        };

        $(document).on('keydown.crmSearchDisplayEditable', (e) => {
          if (e.key === 'Escape') {
            $scope.$apply(() => ctrl.cancel());
          }
          else if (e.key === 'Enter') {
            $scope.$apply(() => ctrl.save());
          }
        });

        if (this.field.options === true) {
          loadOptions();
        }
      };

      this.$onDestroy = function() {
        $(document).off('.crmSearchDisplayEditable');
      };

      this.save = function() {
        const value = formatDataType(ctrl.value);
        if (value !== initialValue) {
          col.edit.record[col.edit.value_key] = value;
          crmStatus({}, crmApi4(col.edit.entity, col.edit.action, {values: col.edit.record}));
          ctrl.row.data[col.edit.value_path] = value;
          col.val = formatDisplayValue(value);
        }
        ctrl.cancel();
      };

      function formatDataType(val) {
        if (_.isArray(val)) {
          const formatted = angular.copy(val);
          formatted.forEach((v, i) => formatted[i] = formatDataType(v));
          return formatted;
        }
        if (ctrl.field.data_type === 'Integer') {
          return +val;
        }
        return val;
      }

      function formatDisplayValue(val) {
        let displayValue = angular.copy(val);
        if (_.isArray(displayValue)) {
          displayValue.forEach((v, i) => displayValue[i] = formatDisplayValue(v));
          return displayValue;
        }
        if (ctrl.field.options) {
          ctrl.field.options.forEach((option) => {
            if (('' + option.id) === ('' + val)) {
              displayValue = option.label;
            }
          });
        } else if (ctrl.field.data_type === 'Boolean' && val === true) {
          displayValue = ts('Yes');
        } else if (ctrl.field.data_type === 'Boolean' && val === false) {
          displayValue = ts('No');
        } else if (ctrl.field.data_type === 'Date' || ctrl.field.data_type === 'Timestamp') {
          displayValue = CRM.utils.formatDate(val, null, ctrl.field.data_type === 'Timestamp');
        } else if (ctrl.field.data_type === 'Money') {
          displayValue = CRM.formatMoney(displayValue, false, col.edit.currency_format);
        }
        return displayValue;
      }

      function loadOptions() {
        var cacheKey = col.edit.entity + ' ' + ctrl.field.name;
        if (optionsCache[cacheKey]) {
          ctrl.field.options = optionsCache[cacheKey];
          return;
        }
        crmApi4(col.edit.entity, 'getFields', {
          action: 'update',
          select: ['options'],
          loadOptions: ['id', 'name', 'label', 'description', 'color', 'icon'],
          where: [['name', '=', ctrl.field.name]]
        }, 0).then(function(field) {
          ctrl.field.options = optionsCache[cacheKey] = field.options;
        });
      }
    }
  });

})(angular, CRM.$, CRM._);
