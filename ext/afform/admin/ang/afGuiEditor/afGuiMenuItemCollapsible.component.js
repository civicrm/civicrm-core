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
      var ts = $scope.ts = CRM.ts('org.civicrm.afform_admin'),
        ctrl = this;

      this.isCollapsible = function() {
        return afGui.hasClass(ctrl.node, 'af-collapsible');
      };

      this.isCollapsed = function() {
        return afGui.hasClass(ctrl.node, 'af-collapsible af-collapsed');
      };

      this.toggleCollapsible = function() {
        // Node must have a title to be collapsible
        if (ctrl.isCollapsible() || !ctrl.node['af-title']) {
          afGui.modifyClasses(ctrl.node, 'af-collapsible af-collapsed');
        } else {
          afGui.modifyClasses(ctrl.node, null, 'af-collapsible');
        }
      };

      this.toggleCollapsed = function() {
        if (ctrl.isCollapsed()) {
          afGui.modifyClasses(ctrl.node, 'af-collapsed');
        } else {
          afGui.modifyClasses(ctrl.node, null, 'af-collapsed');
        }
      };

    }
  });

})(angular, CRM.$, CRM._);
