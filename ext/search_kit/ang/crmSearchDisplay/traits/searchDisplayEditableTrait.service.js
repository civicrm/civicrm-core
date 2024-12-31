(function(angular, $, _) {
  "use strict";

  // Trait shared by any search display controllers which allow sorting
  angular.module('crmSearchDisplay').factory('searchDisplayEditableTrait', function(crmApi4, crmStatus) {

    // Trait properties get mixed into display controller using angular.extend()
    return {

      editing: false,

      onPostRun: [function(apiResults) {
        this.cancelEditing();
      }],

      startCreating: function() {
        this.editing = [-1];
        this.editValues = {};
      },

      cancelEditing: function() {
        this.editing = false;
        this.editValues = {};
      },

      saveEditing: function(rowIndex, colKey) {
        const ctrl = this;
        const apiParams = this.getApiParams(null);
        let rowKey = null;
        // Edit mode
        if (rowIndex >= 0) {
          rowKey = this.results[rowIndex].key;
          if (colKey) {
            const colIndex = this.settings.columns.findIndex(column => column.key === colKey);
            this.results[rowIndex].columns[colIndex].loading = true;
          } else {
            this.results[rowIndex].columns.forEach((col) => col.loading = true);
          }
          apiParams.rowKey = rowKey;
        }
        apiParams.values = this.editValues;
        this.cancelEditing();

        crmStatus({}, crmApi4('SearchDisplay', 'inlineEdit', apiParams))
          .then(function(result) {
            // Create mode
            if (rowIndex < 0 && result.length) {
              ctrl.results.push(result[0]);
              ctrl.rowCount++;
            }
            // Edit mode
            else if (rowIndex >= 0) {
              // Re-find rowIndex in case rows have shifted (possible race conditions with other editable rows)
              const rowIndex = ctrl.results.findIndex(row => row.key === rowKey);
              // If the api returned a refreshed row, replace the current row with it
              if (result.length) {
                angular.extend(ctrl.results[rowIndex], result[0]);
              }
              // Or it's possible that the update caused this row to no longer match filters, in which case remove it
              else {
                ctrl.results.splice(rowIndex, 1);
                // Shift value of 'editing' if needed
                if (ctrl.editing && ctrl.editing[0] > rowIndex) {
                  ctrl.editing[0]--;
                } else if (ctrl.editing && ctrl.editing[0] == rowIndex) {
                  ctrl.cancelEditing();
                }
                ctrl.rowCount--;
              }
            }
          });
      },

    };
  });

})(angular, CRM.$, CRM._);
