(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchDisplayList').component('crmSearchDisplayList', {
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
    templateUrl: '~/crmSearchDisplayList/crmSearchDisplayList.html',
    controller: function($scope, $element, searchDisplayBaseTrait, searchDisplayTasksTrait) {
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        // Mix in required traits
        ctrl = angular.extend(this, _.cloneDeep(searchDisplayBaseTrait), _.cloneDeep(searchDisplayTasksTrait));

      this.$onInit = function() {
        this.initializeDisplay($scope, $element);
      };

    }
  });

})(angular, CRM.$, CRM._);
