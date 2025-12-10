// https://civicrm.org/licensing
(function(angular, $, _) {
  "use strict";

  // Menu item to control the border property of a node
  angular.module('afGuiEditor').component('afGuiMenuItemCollapsible', {
    templateUrl: '~/afGuiEditor/afGuiMenuItemCollapsible.html',
    bindings: {
      node: '='
    },
    controller: function($scope, afGui) {
      const ts = $scope.ts = CRM.ts('org.civicrm.afform_admin'),
        ctrl = this;

      this.isCollapsed = function() {
        return !('open' in ctrl.node);
      };

      this.toggleCollapsed = function() {
        if (ctrl.isCollapsed()) {
          ctrl.node.open = '';
        } else {
          delete ctrl.node.open;
        }
      };

    }
  });

})(angular, CRM.$, CRM._);
