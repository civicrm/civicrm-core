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
      const ts = $scope.ts = CRM.ts('org.civicrm.afform_admin'),
        ctrl = this;

      this.styles = _.cloneDeep(afGui.meta.afform_container_style);

      $scope.getSetStyle = function(style) {
        const options = ctrl.styles.map(item => item.value);
        if (arguments.length) {
          afGui.modifyClasses(ctrl.node, options, style);
        }
        return _.intersection(afGui.splitClass(ctrl.node['class']), options)[0] || '';
      };

    }
  });

})(angular, CRM.$, CRM._);
