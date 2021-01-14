// https://civicrm.org/licensing
(function(angular, $, _) {
  "use strict";

  // Menu item to control the border property of a node
  angular.module('afGuiEditor').component('afGuiMenuItemBorder', {
    templateUrl: '~/afGuiEditor/afGuiMenuItemBorder.html',
    bindings: {
      node: '='
    },
    controller: function($scope, afAdmin) {
      var ts = $scope.ts = CRM.ts(),
        ctrl = this;

      $scope.getSetBorderWidth = function(width) {
        return getSetBorderProp(ctrl.node, 0, arguments.length ? width : null);
      };

      $scope.getSetBorderStyle = function(style) {
        return getSetBorderProp(ctrl.node, 1, arguments.length ? style : null);
      };

      $scope.getSetBorderColor = function(color) {
        return getSetBorderProp(ctrl.node, 2, arguments.length ? color : null);
      };

      function getSetBorderProp(node, idx, val) {
        var border = getBorder(node) || ['1px', '', '#000000'];
        if (val === null) {
          return border[idx];
        }
        border[idx] = val;
        afAdmin.setStyle(node, 'border', val ? border.join(' ') : null);
      }

      function getBorder(node) {
        var border = _.map((afAdmin.getStyles(node).border || '').split(' '), _.trim);
        return border.length > 2 ? border : null;
      }
    }
  });

})(angular, CRM.$, CRM._);
