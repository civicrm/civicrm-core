(function(angular, $, _) {
  "use strict";

  // Trait shared by any search display controllers which use tasks
  angular.module('crmSearchDisplay').factory('searchDisplayTasksTrait', function(crmApi4) {
    var ts = CRM.ts('org.civicrm.search_kit');

    // Trait properties get mixed into display controller using angular.extend()
    return {

      // Use ajax to select all rows on every page
      selectAllPages: function() {
        var ctrl = this;
        ctrl.loadingAllRows = ctrl.allRowsSelected = true;
        var params = ctrl.getApiParams('id');
        crmApi4('SearchDisplay', 'run', params).then(function(ids) {
          ctrl.loadingAllRows = false;
          ctrl.selectedRows = _.uniq(_.toArray(ids));
        });
      },

      // Select all rows on the current page
      selectPage: function() {
        this.allRowsSelected = (this.rowCount <= this.results.length);
        this.selectedRows = _.uniq(_.pluck(this.results, 'key'));
      },

      // Clear selection
      selectNone: function() {
        this.allRowsSelected = false;
        this.selectedRows = [];
      },

      // Toggle the "select all" checkbox
      toggleAllRows: function() {
        // Deselect all
        if (this.selectedRows && this.selectedRows.length) {
          this.selectNone();
        }
        // Select all
        else if (this.page === 1 && this.rowCount === this.results.length) {
          this.selectPage();
        }
        // If more than one page of results, use ajax to fetch all ids
        else {
          this.selectAllPages();
        }
      },

      // Toggle row selection
      toggleRow: function(row, event) {
        this.selectedRows = this.selectedRows || [];
        var ctrl = this,
          index = ctrl.selectedRows.indexOf(row.key);

        // See if any boxes are checked above/below this one
        function checkRange(allRows, checkboxPosition, dir) {
          for (var row = checkboxPosition; row >= 0 && row <= allRows.length; row += dir) {
            if (ctrl.selectedRows.indexOf(allRows[row]) > -1) {
              return row;
            }
          }
        }

        // Check a bunch of boxes
        function selectRange(allRows, start, end) {
          for (var row = start; row <= end; ++row) {
            ctrl.selectedRows.push(allRows[row]);
          }
        }

        if (index < 0) {
          // Shift-click - select range between clicked checkbox and the nearest selected row
          if (event.shiftKey && ctrl.selectedRows.length) {
            var allRows = _.pluck(ctrl.results, 'key'),
              checkboxPosition = allRows.indexOf(row.key);

            var nearestBefore = checkRange(allRows, checkboxPosition, -1),
              nearestAfter = checkRange(allRows, checkboxPosition, 1);

            // Select range between clicked box and the previous/next checked box
            // In the ambiguous situation where there are checked boxes both above AND below the clicked box,
            // choose the direction of the box which was most recently clicked.
            if (nearestAfter !== undefined && (nearestBefore === undefined || nearestAfter === allRows.indexOf(_.last(ctrl.selectedRows)))) {
              selectRange(allRows, checkboxPosition + 1, nearestAfter - 1);
            } else if (nearestBefore !== undefined && (nearestAfter === undefined || nearestBefore === allRows.indexOf(_.last(ctrl.selectedRows)))) {
              selectRange(allRows, nearestBefore + 1, checkboxPosition -1);
            }
          }
          ctrl.selectedRows = _.uniq(ctrl.selectedRows.concat([row.key]));
          ctrl.allRowsSelected = (ctrl.rowCount === ctrl.selectedRows.length);
        } else {
          ctrl.allRowsSelected = false;
          ctrl.selectedRows.splice(index, 1);
        }
      },

      // @return bool
      isRowSelected: function(row) {
        return this.allRowsSelected || _.includes(this.selectedRows, row.key);
      },

      isPageSelected: function() {
        return (this.allRowsSelected && this.rowCount === this.results.length) ||
          (!this.allRowsSelected && this.selectedRows && this.selectedRows.length === this.results.length);
      },

      refreshAfterTask: function() {
        this.selectedRows = [];
        this.allRowsSelected = false;
        this.rowCount = undefined;
        this.runSearch();
      },

      // Add onChangeFilters callback (gets merged with others via angular.extend)
      onChangeFilters: [function() {
        // Reset selection when filters are changed
        this.selectedRows = [];
        this.allRowsSelected = false;
      }],

      // Add onPostRun callback (gets merged with others via angular.extend)
      onPostRun: [function(results, status, editedRow) {
        if (editedRow && status === 'success' && this.selectedRows) {
          // If edited row disappears (because edits cause it to not meet search criteria), deselect it
          var index = this.selectedRows.indexOf(editedRow.key);
          if (index > -1 && !_.findWhere(results, {key: editedRow.key})) {
            this.selectedRows.splice(index, 1);
          }
        }
      }]

    };
  });

})(angular, CRM.$, CRM._);
