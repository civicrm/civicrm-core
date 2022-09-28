(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchDisplayTable').component('crmSearchDisplayTable', {
    bindings: {
      apiEntity: '@',
      search: '<',
      display: '<',
      settings: '<',
      filters: '<',
      totalCount: '<'
    },
    require: {
      afFieldset: '?^^afFieldset'
    },
    templateUrl: '~/crmSearchDisplayTable/crmSearchDisplayTable.html',
    controller: function($scope, $element, searchDisplayBaseTrait, searchDisplayTasksTrait, searchDisplaySortableTrait, crmApi4, crmStatus) {
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        // Mix in traits to this controller
        ctrl = angular.extend(this, searchDisplayBaseTrait, searchDisplayTasksTrait, searchDisplaySortableTrait);

      this.$onInit = function() {
        var tallyParams;

        if (ctrl.settings.tally) {
          ctrl.onPreRun.push(function (apiParams) {
            ctrl.tally = null;
            tallyParams = _.cloneDeep(apiParams);
          });

          ctrl.onPostRun.push(function (results, status) {
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
            containment: 'table',
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
                  ctrl.runSearch([[ctrl.apiEntity, 'update', updateParams]], {}, movedItem);
                }
              });
            }
          };
        }
      };

    }
  });

})(angular, CRM.$, CRM._);
