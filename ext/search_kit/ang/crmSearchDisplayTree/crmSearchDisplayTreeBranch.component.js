(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchDisplayTree').component('crmSearchDisplayTreeBranch', {
    bindings: {
      items: '<',
      parentKey: '<',
    },
    require: {
      displayCtrl: '^crmSearchDisplayTree',
    },
    templateUrl: '~/crmSearchDisplayTree/crmSearchDisplayTreeBranch.html',
    controllerAs: '$branch',
    controller: function($scope, $element, crmApi4, crmStatus) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit');
      const ctrl = this;

      this.$onInit = function() {
        // Pass display ctrl back to scope for use by e.g. field.html
        $scope.$ctrl = this.displayCtrl;

        ctrl.draggableOptions = {
          handle: '.crm-draggable',
          connectWith: 'crm-search-display-tree [ui-sortable]',
          placeholder: 'crm-search-display-tree-placeholder',
          tolerance: 'pointer', // seems to work better for dragging large items into small gaps
          update: function(e, ui) {
            const movedItem = ui.item.sortable.model;
            let newPosition = ui.item.sortable.dropindex;
            let displacedItem;
            // Item moved within the same parent group
            if (ui.item.sortable.source[0] === ui.item.sortable.droptarget[0]) {
              // In this mode the list hasn't yet been updated, so the new index points to the displaced item
              displacedItem = ctrl.items[newPosition];
              updateDraggableWeights(movedItem, displacedItem);
            }
            // Item moved here from a different group
            else if (ui.sender) {
              // In this mode the list has already been updated so the new index points to the current item
              displacedItem = ctrl.items[newPosition + 1];
              updateDraggableWeights(movedItem, displacedItem);
            }
          }
        };
      };

      function updateDraggableWeights(movedItem, displacedItem) {
        const weightField = ctrl.displayCtrl.settings.draggable;
        const parentField = ctrl.displayCtrl.settings.parent_field;
        const apiParams = ctrl.displayCtrl.getApiParams('draggableWeight');
        apiParams.rowKey = movedItem.key;
        apiParams.values = {};
        if (displacedItem) {
          apiParams.values[weightField] = displacedItem.data[weightField];
        }
        // No displaced item - place at the end of the list (if the list has more than just the new item)
        else if (ctrl.items.length > 1) {
          apiParams.values[weightField] = ctrl.items[ctrl.items.length - 1].data[weightField] + 1;
        }
        // List was empty (only containst the new item)
        else {
          apiParams.values[weightField] = 1;
        }
        apiParams.values[parentField] = ctrl.parentKey;
        crmStatus({}, crmApi4('SearchDisplay', 'inlineEdit', apiParams))
          .then(function(newWeights) {
            const weightColumn = ctrl.displayCtrl.settings.columns.findIndex(col => col.key === weightField);
            ctrl.displayCtrl.results.forEach(function(row) {
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

    }
  });

})(angular, CRM.$, CRM._);
