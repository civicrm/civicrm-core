(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchDisplayGrid').component('crmSearchDisplayGrid', {
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
    templateUrl: '~/crmSearchDisplayGrid/crmSearchDisplayGrid.html',
    controller: function($scope, $element, searchDisplayBaseTrait, searchDisplayTasksTrait, searchDisplayEditableTrait) {
      let ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        // Mix in required traits
        ctrl = angular.extend(this, _.cloneDeep(searchDisplayBaseTrait), _.cloneDeep(searchDisplayTasksTrait), _.cloneDeep(searchDisplayEditableTrait));

      this.$onInit = function() {
        this.initializeDisplay($scope, $element);
      };

    }
  });

})(angular, CRM.$, CRM._);
