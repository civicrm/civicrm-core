// https://civicrm.org/licensing
(function(angular, $, _) {
  "use strict";

  angular.module('afGuiEditor').component('afGuiButton', {
    templateUrl: '~/afGuiEditor/elements/afGuiButton.html',
    bindings: {
      node: '=',
      deleteThis: '&'
    },
    controller: function($scope, afAdmin) {
      var ts = $scope.ts = CRM.ts(),
        ctrl = this;

      // TODO: Add action selector to UI
      // $scope.actions = {
      //   "afform.submit()": ts('Submit Form')
      // };

      $scope.styles = _.transform(CRM.afGuiEditor.styles, function(styles, val, key) {
        styles['btn-' + key] = val;
      });

      // Getter/setter for ng-model
      $scope.getSetStyle = function(val) {
        if (arguments.length) {
          return afAdmin.modifyClasses(ctrl.node, _.keys($scope.styles), ['btn', val]);
        }
        return _.intersection(afAdmin.splitClass(ctrl.node['class']), _.keys($scope.styles))[0] || '';
      };

      $scope.pickIcon = function() {
        afAdmin.pickIcon().then(function(val) {
          ctrl.node['crm-icon'] = val;
        });
      };

    }
  });

})(angular, CRM.$, CRM._);
