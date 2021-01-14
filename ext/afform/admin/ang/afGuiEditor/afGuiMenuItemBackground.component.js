// https://civicrm.org/licensing
(function(angular, $, _) {
  "use strict";

  // Menu item to control the background property of a node
  angular.module('afGuiEditor').component('afGuiMenuItemBackground', {
    templateUrl: '~/afGuiEditor/afGuiMenuItemBackground.html',
    bindings: {
      node: '='
    },
    controller: function($scope, afAdmin) {
      var ts = $scope.ts = CRM.ts(),
        ctrl = this;

      $scope.getSetBackgroundColor = function(color) {
        if (!arguments.length) {
          return afAdmin.getStyles(ctrl.node)['background-color'] || '#ffffff';
        }
        afAdmin.setStyle(ctrl.node, 'background-color', color);
      };
    }
  });

})(angular, CRM.$, CRM._);
