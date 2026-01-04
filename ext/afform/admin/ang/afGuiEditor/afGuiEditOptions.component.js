// https://civicrm.org/licensing
(function(angular, $, _) {
  "use strict";

  angular.module('afGuiEditor').component('afGuiEditOptions', {
    templateUrl: '~/afGuiEditor/afGuiEditOptions.html',
    require: {field: '^^afGuiField'},
    controller: function($scope) {
      const ts = $scope.ts = CRM.ts('org.civicrm.afform_admin');
      const ctrl = this;

      this.$onInit = function() {
        // Currently defined field options (either customized or original)
        $scope.options = JSON.parse(angular.toJson(ctrl.field.getOptions()));
        const optionKeys = $scope.options.map(option => option.id);
        // Original options
        const originalOptions = JSON.parse(angular.toJson(ctrl.field.getOriginalOptions()));
        // Original options that are not in the current set (if customized)
        $scope.deletedOptions = originalOptions.filter(item => !optionKeys.includes(item.id));
        // Deleted options have no label so fetch original
        $scope.originalLabels = originalOptions.reduce((originalLabels, item) => {
          originalLabels[item.id] = item.label;
          return originalLabels;
        }, {});
      };

      $scope.deleteOption = function(option, $index) {
        $scope.options.splice($index, 1);
        $scope.deletedOptions.push(option);
      };

      $scope.restoreOption = function(option, $index) {
        $scope.deletedOptions.splice($index, 1);
        $scope.options.push(option);
      };

      $scope.save = function() {
        ctrl.field.getSet('options', JSON.parse(angular.toJson($scope.options)));
        $scope.close();
      };

      $scope.close = function() {
        ctrl.field.setEditingOptions(false);
        $('#afGuiEditor').removeClass('af-gui-editing-content');
      };
    }
  });

})(angular, CRM.$, CRM._);
