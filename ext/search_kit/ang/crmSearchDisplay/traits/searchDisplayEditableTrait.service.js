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
            ctrl.refreshAfterEditing(result, row ? row.key : null);
          });
      },

      refreshAfterEditing: function(result, rowKey) {
        // Create mode
        if (!rowKey && result.length) {
          this.results.push(result[0]);
          this.rowCount++;
          return;
        }
        const rowIndex = this.results.findIndex(result => result.key === rowKey);
        // If the api returned a refreshed row, replace the current row with it
        if (result.length && rowIndex >= 0) {
          const row = this.results[rowIndex];
          // Preserve hierarchical info like _descendents and _depth which isn't returned by the refresh
          _.defaults(result[0].data, row.data);
          // Note that extend() will preserve top-level items like 'collapsed' while replacing columns and data
          angular.extend(row, result[0]);
        }
        // Or it's possible that the update caused this row to no longer match filters, in which case do a full refresh
        else {
          this.rowCount = null;
          this.getResultsPronto();
          // Trigger all other displays in the same form to update.
          // This display won't update twice because of the debounce in getResultsPronto()
          this.$element.trigger('crmPopupFormSuccess');
        }
      }

    };
  });

})(angular, CRM.$, CRM._);
