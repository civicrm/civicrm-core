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

      this.groups = [];

      this.$onInit = function() {
        this.onPostRun.push(function(apiResults, status) {
          if (status === 'success') {
            ctrl.groups = groupRows(apiResults.run);
          }
        });

        this.initializeDisplay($scope, $element);
      };

      // Bands the (already-sorted) flat result set into sections wherever the
      // group_by field's value changes between consecutive rows. This is a client-side
      // "banded report" grouping, not a real SQL GROUP BY - it depends entirely on the
      // display's sort setting already ordering rows by the group_by field first.
      function groupRows(results) {
        const groupField = ctrl.settings.group_by;
        if (!groupField) {
          return [{value: null, rows: results}];
        }
        const groups = [];
        let current = null;
        results.forEach(function(row) {
          const value = row.data[groupField];
          if (!current || current.value !== value) {
            current = {value: value, rows: []};
            groups.push(current);
          }
          current.rows.push(row);
        });
        return groups;
      }

    }
  });

})(angular);
