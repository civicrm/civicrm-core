(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchDisplayList').component('crmSearchDisplayList', {
    bindings: {
      apiEntity: '@',
      search: '<',
      display: '<',
      apiParams: '<',
      settings: '<',
      filters: '<'
    },
    require: {
      afFieldset: '?^^afFieldset'
    },
    templateUrl: '~/crmSearchDisplayList/crmSearchDisplayList.html',
    controller: function($scope, $element, searchDisplayBaseTrait) {
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        // Mix in properties of searchDisplayBaseTrait
        ctrl = angular.extend(this, searchDisplayBaseTrait);

      this.$onInit = function() {
        this.initializeDisplay($scope, $element);
      };

      // Refresh current page
      this.refresh = function(row) {
        ctrl.getResults();
      };

    }
  });

})(angular, CRM.$, CRM._);
