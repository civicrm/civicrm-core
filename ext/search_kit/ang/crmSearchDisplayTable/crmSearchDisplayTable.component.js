(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchDisplayTable').component('crmSearchDisplayTable', {
    bindings: {
      apiEntity: '@',
      search: '<',
      display: '<',
      settings: '<',
      filters: '<'
    },
    require: {
      afFieldset: '?^^afFieldset'
    },
    templateUrl: '~/crmSearchDisplayTable/crmSearchDisplayTable.html',
    controller: function($scope, $element, searchDisplayBaseTrait, searchDisplayTasksTrait, searchDisplaySortableTrait) {
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        // Mix in traits to this controller
        ctrl = angular.extend(this, searchDisplayBaseTrait, searchDisplayTasksTrait, searchDisplaySortableTrait);

      this.$onInit = function() {
        this.initializeDisplay($scope, $element);
      };

      // Refresh page after inline-editing a row
      this.refresh = function(row) {
        var rowId = row.id;
        ctrl.runSearch()
          .then(function() {
            // If edited row disappears (because edits cause it to not meet search criteria), deselect it
            var index = ctrl.selectedRows.indexOf(rowId);
            if (index > -1 && !_.findWhere(ctrl.results, {id: rowId})) {
              ctrl.selectedRows.splice(index, 1);
            }
          });
      };

    }
  });

})(angular, CRM.$, CRM._);
