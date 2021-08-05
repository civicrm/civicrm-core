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
    controller: function($scope, $element, crmApi4, searchDisplayBaseTrait, searchDisplayTasksTrait) {
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        // Mix in traits to this controller
        ctrl = angular.extend(this, searchDisplayBaseTrait, searchDisplayTasksTrait);

      this.$onInit = function() {
        this.initializeDisplay($scope, $element);
      };

      // Refresh page after inline-editing a row
      this.refresh = function(row) {
        var rowId = row.id;
        ctrl.getResults()
          .then(function() {
            // If edited row disappears (because edits cause it to not meet search criteria), deselect it
            var index = ctrl.selectedRows.indexOf(rowId);
            if (index > -1 && !_.findWhere(ctrl.results, {id: rowId})) {
              ctrl.selectedRows.splice(index, 1);
            }
          });
      };

      /**
       * Returns crm-i icon class for a sortable column
       * @param col
       * @returns {string}
       */
      $scope.getSort = function(col) {
        var dir = _.reduce(ctrl.sort, function(dir, item) {
          return item[0] === col.key ? item[1] : dir;
        }, null);
        if (dir) {
          return 'fa-sort-' + dir.toLowerCase();
        }
        return 'fa-sort disabled';
      };

      /**
       * Called when clicking on a column header
       * @param col
       * @param $event
       */
      $scope.setSort = function(col, $event) {
        if (col.type !== 'field') {
          return;
        }
        var dir = $scope.getSort(col) === 'fa-sort-asc' ? 'DESC' : 'ASC';
        if (!$event.shiftKey || !ctrl.sort) {
          ctrl.sort = [];
        }
        var index = _.findIndex(ctrl.sort, [col.key]);
        if (index > -1) {
          ctrl.sort[index][1] = dir;
        } else {
          ctrl.sort.push([col.key, dir]);
        }
        $scope.getResults();
      };

    }
  });

})(angular, CRM.$, CRM._);
