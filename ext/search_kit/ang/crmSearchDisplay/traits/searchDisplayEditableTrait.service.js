(function(angular, $, _) {
  "use strict";

  // Trait shared by any search display controllers which allow sorting
  angular.module('crmSearchDisplay').factory('searchDisplayEditableTrait', function(crmApi4, crmStatus) {

    // Trait properties get mixed into display controller using angular.extend()
    return {

      editing: false,

      onPostRun: [function(apiResults) {
        this.editing = false;
      }],

      cancelEditing: function() {
        this.editing = false;
      },

      saveEditing: function(rowIndex, colKey, value) {
        const ctrl = this;
        this.editing = false;
        const apiParams = this.getApiParams(null);
        const rowKey = this.results[rowIndex].key;
        const colIndex = this.settings.columns.findIndex(column => column.key === colKey);
        this.results[rowIndex].columns[colIndex].loading = true;
        apiParams.rowKey = rowKey;
        apiParams.colKey = colKey;
        apiParams.value = value;

        crmStatus({}, crmApi4('SearchDisplay', 'inlineEdit', apiParams))
          .then(function(result) {
            // Re-find rowIndex in case rows have shifted (possible race conditions with other editable rows)
            const rowIndex = ctrl.results.findIndex(row => row.key === rowKey);
            // If the api returned a refreshed row, replace the current row with it
            if (result.length) {
              delete ctrl.results[rowIndex].loading;
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
            }
          });
      },

    };
  });

})(angular, CRM.$, CRM._);
