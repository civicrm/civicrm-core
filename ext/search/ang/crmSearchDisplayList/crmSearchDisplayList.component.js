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
    controller: function($scope, crmApi4, searchDisplayUtils) {
      var ts = $scope.ts = CRM.ts('org.civicrm.search'),
        ctrl = this;

      this.page = 1;
      this.rowCount = null;

      this.$onInit = function() {
        this.sort = this.settings.sort ? _.cloneDeep(this.settings.sort) : [];
        $scope.displayUtils = searchDisplayUtils;

        if (this.afFieldset) {
          $scope.$watch(this.afFieldset.getFieldData, onChangeFilters, true);
        }
        $scope.$watch('$ctrl.filters', onChangeFilters, true);
      };

      this.getResults = _.debounce(function() {
        searchDisplayUtils.getResults(ctrl);
      }, 100);

      // Refresh current page
      this.refresh = function(row) {
        searchDisplayUtils.getResults(ctrl);
      };

      function onChangeFilters() {
        ctrl.page = 1;
        ctrl.rowCount = null;
        ctrl.getResults();
      }

      this.formatFieldValue = function(rowData, col) {
        return searchDisplayUtils.formatDisplayValue(rowData, col.key, ctrl.settings.columns);
      };

    }
  });

})(angular, CRM.$, CRM._);
