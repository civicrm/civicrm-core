(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('searchAdminDisplayTable', {
    bindings: {
      display: '<',
      apiEntity: '<',
      apiParams: '<'
    },
    require: {
      parent: '^crmSearchAdminDisplay'
    },
    templateUrl: '~/crmSearchAdmin/displays/searchAdminDisplayTable.html',
    controller: function($scope, searchMeta) {
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this;

      this.tableClasses = [
        {name: 'table', label: ts('Row Borders')},
        {name: 'table-bordered', label: ts('Column Borders')},
        {name: 'table-striped', label: ts('Even/Odd Stripes')}
      ];

      // Check if array contains item
      this.includes = _.includes;

      // Add or remove an item from an array
      this.toggle = function(collection, item) {
        if (_.includes(collection, item)) {
          _.pull(collection, item);
        } else {
          collection.push(item);
        }
      };

      this.toggleDraggable = function() {
        if (ctrl.display.settings.draggable) {
          delete ctrl.display.settings.draggable;
        } else {
          ctrl.display.settings.sort = [];
          ctrl.display.settings.draggable = searchMeta.getEntity(ctrl.apiEntity).order_by;
        }
      };

      this.$onInit = function () {
        if (!ctrl.display.settings) {
          ctrl.display.settings = _.extend({}, _.cloneDeep(CRM.crmSearchAdmin.defaultDisplay.settings), {columns: null});
          if (searchMeta.getEntity(ctrl.apiEntity).order_by) {
            ctrl.display.settings.sort.push([searchMeta.getEntity(ctrl.apiEntity).order_by, 'ASC']);
          }
        }
        // Displays created prior to 5.43 may not have this property
        ctrl.display.settings.classes = ctrl.display.settings.classes || [];
        // Table can be draggable if the main entity is a SortableEntity.
        ctrl.canBeDraggable = _.includes(searchMeta.getEntity(ctrl.apiEntity).type, 'SortableEntity');
        ctrl.parent.initColumns({label: true, sortable: true});
      };

    }
  });

})(angular, CRM.$, CRM._);
