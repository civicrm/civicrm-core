// https://civicrm.org/licensing
(function(angular, $, _) {
  "use strict";

  // Menu item to control the border property of a node
  angular.module('afGuiEditor').component('afGuiContainerMultiToggle', {
    templateUrl: '~/afGuiEditor/afGuiContainerMultiToggle.html',
    bindings: {
      entity: '<'
    },
    require: {
      container: '^^afGuiContainer'
    },
    controller: function($scope, afGui) {
      const ts = $scope.ts = CRM.ts('org.civicrm.afform_admin'),
        ctrl = this;
      this.menuItems = [];
      this.uniqueFields = {};

      this.$onInit = function() {
        this.menuItems.push({
          key: 'repeat',
          label: ts('Multiple')
        });
        Object.values(afGui.getEntity(this.entity).unique_fields ?? {}).forEach((fieldName) => {
          const field = ctrl.uniqueFields[fieldName] = afGui.getField(ctrl.entity, fieldName);
          ctrl.menuItems.push({});
          if (field.options) {
            field.options.forEach((option) => {
              ctrl.menuItems.push({
                field: fieldName,
                key: option.id,
                label: option.label
              });
            });
          } else {
            ctrl.menuItems.push({
              field: fieldName,
              key: true,
              label: field.label
            });
          }
        });
      };

      this.isMulti = function() {
        return 'af-repeat' in ctrl.container.node;
      };

      this.isSelected = function(item) {
        if (!item.field && item.key === 'repeat') {
          return ctrl.isMulti();
        }
        if (ctrl.container.node.data) {
          const field = ctrl.uniqueFields[item.field];
          if (field.options) {
            return ctrl.container.node.data[item.field] === item.key;
          }
          return ctrl.container.node.data[item.field];
        }
        return false;
      };

      this.selectOption = function(item) {
        if (!item.field && item.key === 'repeat') {
          return ctrl.container.toggleRepeat();
        }
        if (ctrl.isMulti()) {
          ctrl.container.toggleRepeat();
        }
        const field = ctrl.uniqueFields[item.field];
        ctrl.container.node.data = ctrl.container.node.data || {};
        if (field.options) {
          if (ctrl.container.node.data[item.field] === item.key) {
            delete ctrl.container.node.data[item.field];
          } else {
            ctrl.container.node.data = {};
            ctrl.container.node.data[item.field] = item.key;
            ctrl.container.removeField(item.field);
          }
        } else if (ctrl.container.node.data[item.field]) {
          delete ctrl.container.node.data[item.field];
        } else {
          ctrl.container.node.data = {};
          ctrl.container.node.data[item.field] = true;
          ctrl.container.removeField(item.field);
        }
        if (Object.keys(ctrl.container.node.data).length === 0) {
          delete ctrl.container.node.data;
        }
      };

      this.getButtonText = () => {
        if (ctrl.isMulti()) {
          return ts('Multiple');
        }
        let output = ts('Single');
        Object.entries(ctrl.container.node.data || {}).forEach(([fieldName, val]) => {
          if (val && (fieldName in ctrl.uniqueFields)) {
            const field = ctrl.uniqueFields[fieldName];
            if (field.options) {
              const foundOption = field.options.find((option) => option.id === val);
              output = foundOption ? foundOption.label : output;
            } else {
              output = field.label;
            }
            return false;
          }
        });
        return output;
      };

    }
  });

})(angular, CRM.$, CRM._);
