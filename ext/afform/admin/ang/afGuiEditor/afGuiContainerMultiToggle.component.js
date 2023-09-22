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
      var ts = $scope.ts = CRM.ts('org.civicrm.afform_admin'),
        ctrl = this;
      this.menuItems = [];
      this.uniqueFields = {};

      this.$onInit = function() {
        this.menuItems.push({
          key: 'repeat',
          label: ts('Multiple')
        });
        _.each(afGui.getEntity(this.entity).unique_fields, function(fieldName) {
          var field = ctrl.uniqueFields[fieldName] = afGui.getField(ctrl.entity, fieldName);
          ctrl.menuItems.push({});
          if (field.options) {
            _.each(field.options, function(option) {
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
          var field = ctrl.uniqueFields[item.field];
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
        var field = ctrl.uniqueFields[item.field];
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
        if (_.isEmpty(ctrl.container.node.data)) {
          delete ctrl.container.node.data;
        }
      };

      this.getButtonText = function() {
        if (ctrl.isMulti()) {
          return ts('Multiple');
        }
        var output = ts('Single');
        _.each(ctrl.container.node.data, function(val, fieldName) {
          if (val && (fieldName in ctrl.uniqueFields)) {
            var field = ctrl.uniqueFields[fieldName];
            if (field.options) {
              output = _.result(_.findWhere(field.options, {id: val}), 'label');
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
