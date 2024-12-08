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
    controller: function($scope, $element, searchDisplayBaseTrait, searchDisplayTasksTrait, searchDisplaySortableTrait, searchDisplayEditableTrait, crmApi4) {
      let ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        // Mix in copies of traits to this controller
        ctrl = angular.extend(this, _.cloneDeep(searchDisplayBaseTrait), _.cloneDeep(searchDisplayTasksTrait), _.cloneDeep(searchDisplaySortableTrait), _.cloneDeep(searchDisplayEditableTrait));

      this.$onInit = function() {
        var tallyParams;

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
            containment: $element,
            direction: 'vertical',
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
              $scope.$apply(function() {
                var movedItem = ui.item.sortable.model,
                  oldPosition = ui.item.sortable.index,
                  newPosition = ctrl.results.indexOf(movedItem),
                  displacement = newPosition < oldPosition ? -1 : 1,
                  displacedItem = ctrl.results[newPosition - displacement],
                  weightColumn = ctrl.settings.draggable,
                  updateParams = {where: [['id', '=', movedItem.data.id]], values: {}};
                if (newPosition > -1 && oldPosition !== newPosition) {
                  updateParams.values[weightColumn] = displacedItem.data[weightColumn];
                  ctrl.runSearch({updateWeight: [ctrl.apiEntity, 'update', updateParams]}, {}, movedItem);
                }
              });
            }
          };
        }
      };

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

    }
  });

})(angular, CRM.$, CRM._);
