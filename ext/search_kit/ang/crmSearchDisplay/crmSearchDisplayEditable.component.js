// https://civicrm.org/licensing
(function(angular, $, _) {
  "use strict";

  var optionsCache = {};

  angular.module('crmSearchDisplay').component('crmSearchDisplayEditable', {
    bindings: {
      row: '<',
      rowIndex: '<',
      searchDisplay: '<',
      colKey: '<',
    },
    templateUrl: '~/crmSearchDisplay/crmSearchDisplayEditable.html',
    controller: function($scope, $element, crmApi4, crmStatus) {
      const ctrl = this;
      let initialValue;
      let editableInfo;
      let valuePath;

      this.$onInit = function() {
        editableInfo = this.searchDisplay.results.editable[this.colKey];
        valuePath = this.colKey.split(':')[0];
        this.value = JSON.parse(JSON.stringify(this.row.data[valuePath]));
        initialValue = JSON.parse(JSON.stringify(this.row.data[valuePath]));

        this.field = {
          data_type: editableInfo.data_type,
          input_type: editableInfo.input_type,
          entity: editableInfo.entity,
          name: editableInfo.value_key,
          options: editableInfo.options,
          fk_entity: editableInfo.fk_entity,
          serialize: editableInfo.serialize,
          nullable: editableInfo.nullable && ctrl.row.data[editableInfo.id_path],
        };

        if (this.field.options === true) {
          loadOptions();
        }

        $(document).on('keydown.crmSearchDisplayEditable', (e) => {
          if (e.key === 'Escape') {
            $scope.$apply(() => ctrl.cancel());
          }
          else if (e.key === 'Enter') {
            $scope.$apply(() => ctrl.save());
          }
        });
      };

      this.$onDestroy = function() {
        $(document).off('.crmSearchDisplayEditable');
      };

      this.save = function() {
        const value = formatDataType(ctrl.value);
        if (value !== initialValue) {
          ctrl.row.data[valuePath] = value;
          ctrl.searchDisplay.saveEditing(ctrl.rowIndex, ctrl.colKey, value);
        }
        else {
          ctrl.searchDisplay.cancelEditing();
        }
      };

      this.cancel = function() {
        ctrl.searchDisplay.cancelEditing();
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

      // Used to dynamically load options for fields whose options are not static
      function loadOptions() {
        crmApi4(editableInfo.entity, 'getFields', {
          action: 'update',
          select: ['options'],
          values: ctrl.row.data,
          loadOptions: ['id', 'name', 'label', 'description', 'color', 'icon'],
          where: [['name', '=', ctrl.field.name]]
        }, 0).then(function(fieldInfo) {
          ctrl.field.options = fieldInfo.options;
        });
      }
    }
  });

})(angular, CRM.$, CRM._);
