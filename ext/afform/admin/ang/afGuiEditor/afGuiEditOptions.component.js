// https://civicrm.org/licensing
(function(angular, $, _) {
  "use strict";

  angular.module('afGuiEditor').component('afGuiEditOptions', {
    templateUrl: '~/afGuiEditor/afGuiEditOptions.html',
    require: {field: '^^afGuiField'},
    controller: function($scope) {
      var ts = $scope.ts = CRM.ts('org.civicrm.afform_admin'),
        ctrl = this;

      this.$onInit = function() {
        $scope.options = JSON.parse(angular.toJson(ctrl.field.getOptions()));
        var optionKeys = _.map($scope.options, 'id');
        $scope.deletedOptions = _.filter(JSON.parse(angular.toJson(ctrl.field.getDefn().options || [])), function (item) {
          return !_.contains(optionKeys, item.id);
        });
        $scope.originalLabels = _.transform(ctrl.field.getDefn().options || [], function (originalLabels, item) {
          originalLabels[item.id] = item.label;
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
