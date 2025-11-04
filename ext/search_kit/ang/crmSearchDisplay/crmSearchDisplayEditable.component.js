// https://civicrm.org/licensing
(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchDisplay').component('crmSearchDisplayEditable', {
    bindings: {
      row: '<?',
      display: '<',
      colKey: '<',
      colData: '<?',
      isFullRowMode: '<',
    },
    templateUrl: '~/crmSearchDisplay/crmSearchDisplayEditable.html',
    controller: function($scope, $element, crmApi4, crmStatus) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit');
      const ctrl = this;
      let initialValue;
      let editableInfo;
      let valuePath;

      this.$onInit = function() {
        editableInfo = this.display.results.editable[this.colKey];
        valuePath = this.colKey.split(':')[0];
        this.display.editValues = this.display.editValues || {};
        // Not applicable to create mode
        if (this.row) {
          initialValue = JSON.parse(JSON.stringify(this.row.data[valuePath]));
          this.display.editValues[this.colKey] = JSON.parse(JSON.stringify(this.row.data[valuePath]));
        }

        this.field = {
          data_type: editableInfo.data_type,
          input_type: editableInfo.input_type,
          entity: editableInfo.entity,
          name: editableInfo.value_key,
          options: editableInfo.options,
          fk_entity: editableInfo.fk_entity,
          serialize: editableInfo.serialize,
          nullable: editableInfo.nullable && ctrl.row && ctrl.row.data[editableInfo.id_path],
        };

        if (this.field.options === true) {
          loadOptions();
        }

        $(document).off('.crmSearchDisplayEditable');
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
        const value = ctrl.display.editValues[ctrl.colKey];
        if (value !== initialValue || ctrl.isFullRowMode) {
          ctrl.display.saveEditing(ctrl.row, ctrl.colKey);
        }
        else {
          ctrl.display.cancelEditing(ctrl.row);
        }
      };

      this.cancel = function() {
        ctrl.display.cancelEditing(ctrl.row);
      };

      // Used to dynamically load options for fields whose options are not static
      function loadOptions() {
        crmApi4(editableInfo.entity, 'getFields', {
          action: 'update',
          select: ['options'],
          values: ctrl.row && ctrl.row.data,
          loadOptions: ['id', 'name', 'label', 'description', 'color', 'icon'],
          where: [['name', '=', ctrl.field.name]]
        }, 0).then(function(fieldInfo) {
          ctrl.field.options = fieldInfo.options;
        });
      }
    }
  });

})(angular, CRM.$, CRM._);
