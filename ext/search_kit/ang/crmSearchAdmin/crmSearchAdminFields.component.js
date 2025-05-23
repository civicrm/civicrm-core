(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('crmSearchAdminFields', {
    bindings: {
    },
    require: {
      crmSearchAdmin: '^crmSearchAdmin'
    },
    templateUrl: '~/crmSearchAdmin/crmSearchAdminFields.html',
    controller: function ($scope, $element) {
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this;

      // savedSearch.api_params.select is an array of strings.
      // ui-sortable (and angularJs loops in general) don't work well with primitives
      // So this controller converts the strings into objects and maintains 2-way sync between
      // the two arrays.
      this.select = [];

      $scope.$watchCollection('$ctrl.crmSearchAdmin.savedSearch.api_params.select', function(flatSelect) {
        ctrl.select.length = flatSelect.length;
        flatSelect.forEach((key, index) => {
          // Same field - just update the label
          if (ctrl.select[index] && ctrl.select[index].key === key) {
            ctrl.select[index].label = ctrl.crmSearchAdmin.getFieldLabel(key);
          }
          // Replace field
          else {
            ctrl.select[index] = {
              key: key,
              label: ctrl.crmSearchAdmin.getFieldLabel(key),
              isPseudoField: ctrl.crmSearchAdmin.isPseudoField(key)
            };
          }
        });
      });

      $scope.$watch('$ctrl.select', function(selectObject, oldSelect) {
        if (oldSelect && oldSelect.length && selectObject) {
          ctrl.crmSearchAdmin.savedSearch.api_params.select.length = selectObject.length;
          selectObject.forEach((item, index) => {
            ctrl.crmSearchAdmin.savedSearch.api_params.select[index] = item.key;
          });
        }
      }, true);

      // Drag-n-drop settings for reordering search fields
      this.sortableOptions = {
        containment: '.crm-search-select-fields',
        axis: 'y',
        forcePlaceholderSize: true,
        update: function(e, ui) {
          // Don't allow items to be moved to position 0 if locked
          if (!ui.item.sortable.dropindex && ctrl.crmSearchAdmin.groupExists) {
            ui.item.sortable.cancel();
          }
        }
      };

    }
  });

})(angular, CRM.$, CRM._);
