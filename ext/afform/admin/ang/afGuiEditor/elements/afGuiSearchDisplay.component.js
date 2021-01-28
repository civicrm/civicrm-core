// https://civicrm.org/licensing
(function(angular, $, _) {
  "use strict";

  angular.module('afGuiEditor').component('afGuiSearchDisplay', {
    templateUrl: '~/afGuiEditor/elements/afGuiSearchDisplay.html',
    bindings: {
      node: '='
    },
    controller: function($scope, afGui) {
      var ts = $scope.ts = CRM.ts(),
        ctrl = this;

      this.$onInit = function() {
        ctrl.display = afGui.meta.searchDisplays[ctrl.node['search-name'] + '.' + ctrl.node['display-name']];
      };

    }
  });

})(angular, CRM.$, CRM._);
