// https://civicrm.org/licensing
(function(angular, $, _) {
  "use strict";

  angular.module('afGuiEditor').component('afGuiSearchDisplay', {
    templateUrl: '~/afGuiEditor/elements/afGuiSearchDisplay.html',
    bindings: {
      node: '='
    },
    controller: function($scope, afGui) {
      var ts = $scope.ts = CRM.ts('org.civicrm.afform_admin'),
        ctrl = this;

      this.$onInit = function() {
        ctrl.display = afGui.getSearchDisplay(ctrl.node['search-name'], ctrl.node['display-name']);
        ctrl.editUrl = CRM.url('civicrm/admin/search#/edit/' + ctrl.display.saved_search_id);
      };

    }
  });

})(angular, CRM.$, CRM._);
