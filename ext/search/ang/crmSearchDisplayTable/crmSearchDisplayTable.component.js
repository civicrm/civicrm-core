(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchDisplayTable').component('crmSearchDisplayTable', {
    bindings: {
      apiEntity: '<',
      apiParams: '<',
      settings: '<',
      filters: '<'
    },
    templateUrl: '~/crmSearchDisplayTable/crmSearchDisplayTable.html',
    controller: function($scope, crmApi4, searchDisplayUtils) {
      var ts = $scope.ts = CRM.ts(),
        ctrl = this;

      this.page = 1;
      this.selectedRows = [];
      this.allRowsSelected = false;

      this.$onInit = function() {
        this.apiParams = _.cloneDeep(this.apiParams);
        this.apiParams.limit = parseInt(this.settings.limit || 0, 10);
        this.columns = searchDisplayUtils.prepareColumns(this.settings.columns, this.apiParams);
      };

      this.getResults = function() {
        var params = searchDisplayUtils.prepareParams(ctrl.apiParams, ctrl.filters, ctrl.settings.pager ? ctrl.page : null);

        crmApi4(ctrl.apiEntity, 'get', params).then(function(results) {
          ctrl.results = results;
          ctrl.rowCount = results.count;
        });
      };

      $scope.$watch('$ctrl.filters', ctrl.getResults, true);

      /**
       * Returns crm-i icon class for a sortable column
       * @param col
       * @returns {string}
       */
      $scope.getOrderBy = function(col) {
        var dir = ctrl.apiParams.orderBy && ctrl.apiParams.orderBy[col.key];
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
      $scope.setOrderBy = function(col, $event) {
        var dir = $scope.getOrderBy(col) === 'fa-sort-asc' ? 'DESC' : 'ASC';
        if (!$event.shiftKey || !ctrl.apiParams.orderBy) {
          ctrl.apiParams.orderBy = {};
        }
        ctrl.apiParams.orderBy[col.key] = dir;
        ctrl.getResults();
      };

      $scope.formatResult = function(row, col) {
        var value = row[col.key];
        return searchDisplayUtils.formatSearchValue(row, col, value);
      };

      $scope.selectAllRows = function() {
        // Deselect all
        if (ctrl.allRowsSelected) {
          ctrl.allRowsSelected = false;
          ctrl.selectedRows.length = 0;
          return;
        }
        // Select all
        ctrl.allRowsSelected = true;
        if (ctrl.page === 1 && ctrl.results[1].length < ctrl.apiParams.limit) {
          ctrl.selectedRows = _.pluck(ctrl.results[1], 'id');
          return;
        }
        // If more than one page of results, use ajax to fetch all ids
        $scope.loadingAllRows = true;
        var params = _.cloneDeep(ctrl.apiParams);
        params.select = ['id'];
        crmApi4(ctrl.apiEntity, 'get', params, ['id']).then(function(ids) {
          $scope.loadingAllRows = false;
          ctrl.selectedRows = _.toArray(ids);
        });
      };

      $scope.selectRow = function(row) {
        var index = ctrl.selectedRows.indexOf(row.id);
        if (index < 0) {
          ctrl.selectedRows.push(row.id);
          ctrl.allRowsSelected = (ctrl.rowCount === ctrl.selectedRows.length);
        } else {
          ctrl.allRowsSelected = false;
          ctrl.selectedRows.splice(index, 1);
        }
      };

      $scope.isRowSelected = function(row) {
        return ctrl.allRowsSelected || _.includes(ctrl.selectedRows, row.id);
      };

    }
  });

})(angular, CRM.$, CRM._);
