// https://civicrm.org/licensing
(function(angular, $, _) {
  "use strict";

  // Menu item to control the border property of a node
  angular.module('afGuiEditor').component('afGuiMenuItemStyle', {
    templateUrl: '~/afGuiEditor/afGuiMenuItemStyle.html',
    bindings: {
      node: '='
    },
    controller: function($scope, afGui) {
      var ts = $scope.ts = CRM.ts('org.civicrm.afform_admin'),
        ctrl = this;

      // Todo: Make this an option group so other extensions can add to it
      this.styles = [
        {name: 'af-container-style-pane', label: ts('Panel Pane')}
      ];

      $scope.getSetStyle = function(style) {
        var options = _.map(ctrl.styles, 'name');
        if (arguments.length) {
          afGui.modifyClasses(ctrl.node, options, style);
        }
        return _.intersection(afGui.splitClass(ctrl.node['class']), options)[0] || '';
      };

    }
  });

})(angular, CRM.$, CRM._);
