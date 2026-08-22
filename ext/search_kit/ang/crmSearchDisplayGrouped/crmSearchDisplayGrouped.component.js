(function(angular) {
  "use strict";

  angular.module('crmSearchDisplayGrouped').component('crmSearchDisplayGrouped', {
    bindings: {
      apiEntity: '@',
      search: '<',
      display: '<',
      apiParams: '<',
      settings: '<',
      filters: '<',
      totalCount: '=?'
    },
    require: {
      afFieldset: '?^^afFieldset'
    },
    templateUrl: '~/crmSearchDisplayGrouped/crmSearchDisplayGrouped.html',
    controller: function($scope, $element, searchDisplayBaseTrait, searchDisplayTasksTrait, searchDisplayEditableTrait) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit');
      // Mix in required traits
      const ctrl = angular.extend(this, angular.copy(searchDisplayBaseTrait), angular.copy(searchDisplayTasksTrait), angular.copy(searchDisplayEditableTrait));

      this.$onInit = function() {
        this.onPostRun.push(function(apiResults, status) {
          if (status === 'success' && ctrl.settings.group_by) {
            ctrl.groupRows(ctrl.results, ctrl.settings.group_by);
          }
        });

        this.initializeDisplay($scope, $element);
      };

    }
  });

})(angular);
