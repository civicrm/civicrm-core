(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('searchAdminDisplayTree', {
    bindings: {
      display: '<',
      apiEntity: '<',
      apiParams: '<'
    },
    require: {
      parent: '^crmSearchAdminDisplay'
    },
    templateUrl: '~/crmSearchAdmin/displays/searchAdminDisplayTree.html',
    controller: function($scope, searchMeta, crmUiHelp) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit');
      const ctrl = this;
      $scope.hs = crmUiHelp({file: 'CRM/Search/Help/Display'});

      this.getColTypes = function() {
        return ctrl.parent.colTypes;
      };

      this.$onInit = function () {
        // Tree can be draggable if the main entity is a SortableEntity.
        ctrl.sortableEntity = _.includes(this.parent.getMainEntity().type, 'SortableEntity');
        ctrl.hierarchicalEntity = _.includes(this.parent.getMainEntity().type, 'HierarchicalEntity');

        if (!ctrl.display.settings) {
          ctrl.display.settings = {
            parent_field: this.parent.getMainEntity().parent_field,
            collapsible: 'closed',
            sort: ctrl.parent.getDefaultSort(),
          };
          if (ctrl.sortableEntity) {
            ctrl.parent.toggleDraggable();
          }
        }
        ctrl.parent.initColumns({break: false});
      };

    }
  });

})(angular, CRM.$, CRM._);
