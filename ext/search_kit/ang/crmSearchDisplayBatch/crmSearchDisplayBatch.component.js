(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchDisplayBatch').component('crmSearchDisplayBatch', {
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
    templateUrl: '~/crmSearchDisplayBatch/crmSearchDisplayBatch.html',
    controller: function($scope, $element, $location, searchDisplayBaseTrait, searchDisplayTasksTrait, searchDisplayEditableTrait) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit');
      // Mix in required traits
      const ctrl = angular.extend(this, _.cloneDeep(searchDisplayBaseTrait), _.cloneDeep(searchDisplayTasksTrait), _.cloneDeep(searchDisplayEditableTrait));

      this.$onInit = function() {
        this.batchId = $location.search().batch;
        this.initializeDisplay($scope, $element);
      };

      // Override base method: add batchId
      const _getApiParams = this.getApiParams;
      this.getApiParams = function(mode) {
        const apiParams = _getApiParams.call(this, mode);
        apiParams.batchId = this.batchId;
        return apiParams;
      };

    }
  });

})(angular, CRM.$, CRM._);
