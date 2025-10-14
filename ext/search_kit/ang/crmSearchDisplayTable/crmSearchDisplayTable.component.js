(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchDisplayTable').component('crmSearchDisplayTable', {
    bindings: {
      apiEntity: '@',
      search: '<',
      display: '<',
      settings: '<',
      filters: '<',
      totalCount: '=?'
    },
    require: {
      afFieldset: '?^^afFieldset'
    },
    templateUrl: '~/crmSearchDisplayTable/crmSearchDisplayTable.html',
    controller: function($scope, $element, searchDisplayBaseTrait, searchDisplayTasksTrait, searchDisplaySortableTrait, searchDisplayEditableTrait, crmApi4, crmStatus) {
      let ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        // Mix in copies of traits to this controller
        ctrl = angular.extend(this, _.cloneDeep(searchDisplayBaseTrait), _.cloneDeep(searchDisplayTasksTrait), _.cloneDeep(searchDisplaySortableTrait), _.cloneDeep(searchDisplayEditableTrait));

      this._allColumns = null;

      this.$onInit = function() {
        let tallyParams;

        this._allColumns = ctrl.settings.columns;
        this.resetColumnToggles();

        // Copy API params from the run and adapt them in a secondary `tally` call for the "Totals" row
        if (ctrl.settings.tally) {
          ctrl.onPreRun.push(function (apiCalls) {
            ctrl.tally = null;
            tallyParams = _.cloneDeep(apiCalls.run[2]);
          });

          ctrl.onPostRun.push(function (apiResults, status) {
            ctrl.tally = null;
            if (status === 'success' && tallyParams) {
              tallyParams.return = 'tally';
              crmApi4('SearchDisplay', 'run', tallyParams).then(function (result) {
                ctrl.tally = result[0];
              });
            }
          });
        }

        this.initializeDisplay($scope, $element);

        if (ctrl.settings.draggable) {
          ctrl.draggableOptions = {
            containment: $element.children('div').first(),
            handle: '.crm-draggable',
            forcePlaceholderSize: true,
            helper: function(e, ui) {
              // Prevent table row width from changing during drag
              ui.children().each(function() {
                $(this).width($(this).width());
              });
              return ui;
            },
            stop: function(e, ui) {
              const movedItem = ui.item.sortable.model,
                oldPosition = ui.item.sortable.index,
                newPosition = ctrl.results.indexOf(movedItem),
                displacement = newPosition < oldPosition ? -1 : 1,
                displacedItem = ctrl.results[newPosition - displacement];
              if (newPosition > -1 && oldPosition !== newPosition) {
                updateDraggableWeights(movedItem.key, displacedItem.data);
              }
            }
          };
        }
      };

      function updateDraggableWeights(key, data) {
        const weightField = ctrl.settings.draggable;
        const newWeight = data[weightField];
        const apiParams = ctrl.getApiParams('draggableWeight');
        apiParams.rowKey = key;
        apiParams.values = {};
        apiParams.values[weightField] = newWeight;
        crmStatus({}, crmApi4('SearchDisplay', 'inlineEdit', apiParams))
          .then(function(newWeights) {
            const weightColumn = ctrl.settings.columns.findIndex(col => col.key === weightField);
            ctrl.results.forEach(function(row) {
              if (row.key in newWeights) {
                row.data[weightField] = newWeights[row.key];
                // If there is a column containing 'weight' as a value, update it and
                // hope it doesn't use rewrite or any advanced formatting; 'cause this is but a simple refresh function
                if (weightColumn >= 0) {
                  row.columns[weightColumn].val = newWeights[row.key];
                  // Break reference to trigger an Angular view refresh
                  row.columns[weightColumn] = JSON.parse(angular.toJson(row.columns[weightColumn]));
                }
              }
            });
          });
      }

      // Get header classes for each column
      this.getHeaderClass = function (column) {
        let headerClasses = [];
        if (ctrl.isSortable(column)) {
          headerClasses.push('crm-sortable-col');
        }
        if (column.alignment) {
          headerClasses.push(column.alignment);
        }
        // Include unconditional css rules
        if (column.cssRules) {
          column.cssRules.forEach(function (cssRule) {
            if (cssRule.length === 1) {
              headerClasses.push(cssRule[0]);
            }
          });
        }
        return headerClasses.join(' ');
      };

      this.getRowClass = function (row) {
        let cssClass = row.cssClass || '';
        if (ctrl.settings.hierarchical) {
          cssClass += ' crm-hierarchical-row crm-hierarchical-depth-' + row.data._depth;
          if (row.data._depth) {
            cssClass += ' crm-hierarchical-child';
          }
          if (row.data._descendents) {
            cssClass += ' crm-hierarchical-parent';
          }
        }
        return cssClass;
      };

      /**
       * Filter row columns to the toggled ones (on the clientside)
       *
       * TODO: sometimes we may want to pass column selections to the server
       * to lighten the query (and for actions like spreadsheet export)
       *
       * We *dont* want to keep refetching from the server  when toggling
       * columns on/off on the same page - so this approach works well for now
       */
      this.getRowColumns = (row) => row.columns.filter((col, index) => this._allColumns[index].toggled);

      this.toggleColumns = () => {
        this.settings.columns = this._allColumns.filter((col) => col.toggled);
      };

      this.getColumnToggleLabel = (col) => {
        if (col.label) {
          return col.label;
        }
        if (col.key) {
          return `[${col.key}]`;
        }
        if (col.type === 'menu') {
          return ts('Menu');
        }
        if (col.type === 'buttons') {
          return ts('Buttons');
        }
        if (col.type === 'links') {
          return ts('Links');
        }
        return ts('Column %1', {1: col.index});
      }
      this.resetColumnToggles = () => {
        this._allColumns.forEach((col, index) => {
          this._allColumns[index].toggled = true;
          this._allColumns[index].index = index;
        });
        this.toggleColumns();
      };

    }
  });

})(angular, CRM.$, CRM._);
