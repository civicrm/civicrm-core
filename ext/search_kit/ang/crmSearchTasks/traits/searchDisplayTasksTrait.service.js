(function(angular, $, _) {
  "use strict";

  // Trait shared by any search display controllers which use tasks
  angular.module('crmSearchDisplay').factory('searchDisplayTasksTrait', function(crmApi4) {
    var ts = CRM.ts('org.civicrm.search_kit');

    // Trait properties get mixed into display controller using angular.extend()
    return {

      selectedRows: [],
      allRowsSelected: false,

      // Toggle the "select all" checkbox
      selectAllRows: function() {
        var ctrl = this;
        // Deselect all
        if (ctrl.allRowsSelected) {
          ctrl.allRowsSelected = false;
          ctrl.selectedRows.length = 0;
          return;
        }
        // Select all
        ctrl.allRowsSelected = true;
        if (ctrl.page === 1 && ctrl.results.length < ctrl.settings.limit) {
          ctrl.selectedRows = _.pluck(ctrl.results, 'id');
          return;
        }
        // If more than one page of results, use ajax to fetch all ids
        ctrl.loadingAllRows = true;
        var params = ctrl.getApiParams('id');
        crmApi4('SearchDisplay', 'run', params, ['id']).then(function(ids) {
          ctrl.loadingAllRows = false;
          ctrl.selectedRows = _.toArray(ids);
        });
      },

      // Toggle row selection
      selectRow: function(row) {
        var index = this.selectedRows.indexOf(row.id);
        if (index < 0) {
          this.selectedRows.push(row.id);
          this.allRowsSelected = (this.rowCount === this.selectedRows.length);
        } else {
          this.allRowsSelected = false;
          this.selectedRows.splice(index, 1);
        }
      },

      // @return bool
      isRowSelected: function(row) {
        return this.allRowsSelected || _.includes(this.selectedRows, row.id);
      },

      // Reset selection when filters are changed
      onChangeFilters: function() {
        this.selectedRows.length = 0;
        this.allRowsSelected = false;
      }

    };
  });

})(angular, CRM.$, CRM._);
