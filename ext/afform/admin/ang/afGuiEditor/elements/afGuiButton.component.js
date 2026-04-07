// https://civicrm.org/licensing
(function(angular, $, _) {
  "use strict";

  angular.module('afGuiEditor').component('afGuiButton', {
    templateUrl: '~/afGuiEditor/elements/afGuiButton.html',
    bindings: {
      node: '=',
      deleteThis: '&'
    },
    require: {
      editor: '^^afGuiEditor',
    },
    controller: function($scope, afGui) {
      const ts = $scope.ts = CRM.ts('org.civicrm.afform_admin'),
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
          return afGui.modifyClasses(ctrl.node, Object.keys($scope.styles), ['btn', val]);
        }
        return _.intersection(afGui.splitClass(ctrl.node['class']), Object.keys($scope.styles))[0] || '';
      };

      $scope.pickIcon = function() {
        afGui.pickIcon().then(function(val) {
          ctrl.node['crm-icon'] = val;
        });
      };

    }
  });

})(angular, CRM.$, CRM._);
