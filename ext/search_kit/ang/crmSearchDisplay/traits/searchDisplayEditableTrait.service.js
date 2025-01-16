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

      startEditing: function(row, colIndex) {
        if (this.editing === false && row.columns[colIndex].edit) {
          this.editValues = {};
          this.editing = row.key;
          row.columns.forEach((col, index) => {
            col.editing = (index === colIndex || (this.settings.editableRow && this.settings.editableRow.full));
          });
        }
      },

      startCreating: function() {
        this.editing = -1;
        this.editValues = {};
      },

      cancelEditing: function(row) {
        this.editing = false;
        this.editValues = {};
        if (row && row.columns) {
          row.columns.forEach((col) => col.editing = false);
        }
      },

      saveEditing: function(row, colKey) {
        const ctrl = this;
        const apiParams = this.getApiParams(null);
        // Edit mode (with no row given it's create mode)
        if (row) {
          if (colKey) {
            const colIndex = this.settings.columns.findIndex(column => column.key === colKey);
            row.columns[colIndex].loading = true;
          } else {
            row.columns.forEach((col) => col.loading = true);
          }
          apiParams.rowKey = row.key;
        }
        apiParams.values = this.editValues;
        this.cancelEditing();

        crmStatus({}, crmApi4('SearchDisplay', 'inlineEdit', apiParams))
          .then(function(result) {
            // Create mode
            if (!row && result.length) {
              ctrl.results.push(result[0]);
              ctrl.rowCount++;
            }
            // Edit mode
            else if (row) {
              // If the api returned a refreshed row, replace the current row with it
              if (result.length) {
                // Preserve hierarchical info which isn't returned by the refresh
                result[0].data._descendents = row.data._descendents;
                result[0].data._depth = row.data._depth;
                // Note that extend() will preserve top-level items like 'collapsed' which aren't returned by the refresh
                angular.extend(row, result[0]);
              }
              // Or it's possible that the update caused this row to no longer match filters, in which case remove it
              else {
                const rowIndex = ctrl.results.findIndex(result => result.key === row.key);
                if (rowIndex >= 0) {
                  ctrl.results.splice(rowIndex, 1);
                }
                ctrl.rowCount--;
              }
            }
          });
      },

    };
  });

})(angular, CRM.$, CRM._);
