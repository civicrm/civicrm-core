(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchDisplayGrid').component('crmSearchDisplayGrid', {
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
    templateUrl: '~/crmSearchDisplayGrid/crmSearchDisplayGrid.html',
    controller: function($scope, $element, searchDisplayBaseTrait) {
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        // Mix in properties of searchDisplayBaseTrait
        ctrl = angular.extend(this, searchDisplayBaseTrait);

      this.$onInit = function() {
        this.initializeDisplay($scope, $element);
      };

    }
  });

})(angular, CRM.$, CRM._);
