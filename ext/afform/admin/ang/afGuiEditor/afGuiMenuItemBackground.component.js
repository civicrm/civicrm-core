// https://civicrm.org/licensing
(function(angular, $, _) {
  "use strict";

  // Menu item to control the background property of a node
  angular.module('afGuiEditor').component('afGuiMenuItemBackground', {
    templateUrl: '~/afGuiEditor/afGuiMenuItemBackground.html',
    bindings: {
      node: '='
    },
    controller: function($scope, afGui) {
      const ts = $scope.ts = CRM.ts('org.civicrm.afform_admin'),
        ctrl = this;

      $scope.getSetBackgroundColor = function(color) {
        if (!arguments.length) {
          return afGui.getStyles(ctrl.node)['background-color'] || '#ffffff';
        }
        afGui.setStyle(ctrl.node, 'background-color', color);
      };
    }
  });

})(angular, CRM.$, CRM._);
