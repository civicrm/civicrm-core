(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchDisplayTable').component('crmSearchDisplayToggleNested', {
    bindings: {
      row: '<',
      results: '<',
      rowIndex: '<',
      settings: '<',
    },
    templateUrl: '~/crmSearchDisplayTable/crmSearchDisplayToggleNested.html',
    controller: function($scope, $element) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this;

      this.$onInit = function() {

      };

      this.toggleNested = function() {
        ctrl.row.showNested = !ctrl.row.showNested;
        if (ctrl.row.showNested) {
          ctrl.results.splice(ctrl.rowIndex + 1, 0, {
            isNested: true,
            columns: [],
            key: null,
            cssClass: 'crm-search-display-table-nested-row',
            nestedFilters: getNestedFilters(),
          });
        }
        else {
          ctrl.results.splice(ctrl.rowIndex + 1, 1);
        }
      };

      function getNestedFilters() {
        const data = _.cloneDeep(ctrl.row.data);
        return ctrl.settings.nested.filters.reduce((filters, filter) => {
          filters[filter.field] = data[filter.data];
          return filters;
        }, {});
      }

    }
  });

})(angular, CRM.$, CRM._);
