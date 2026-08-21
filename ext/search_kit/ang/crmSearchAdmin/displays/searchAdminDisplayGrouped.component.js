(function(angular) {
  "use strict";

  angular.module('crmSearchAdmin').component('searchAdminDisplayGrouped', {
    bindings: {
      display: '<',
      apiEntity: '<',
      apiParams: '<'
    },
    require: {
      parent: '^crmSearchAdminDisplay'
    },
    templateUrl: '~/crmSearchAdmin/displays/searchAdminDisplayGrouped.html',
    controller: function($scope, searchMeta, crmUiHelp) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit');
      const ctrl = this;
      $scope.hs = crmUiHelp({file: 'CRM/Search/Help/Display'});

      this.getColTypes = function() {
        return ctrl.parent.colTypes;
      };

      // Grouping is a client-side band over already-sorted rows, so the group_by
      // field must always be the primary sort key or the bands will be wrong.
      this.setGroupBy = function(field) {
        ctrl.display.settings.group_by = field;
        ctrl.display.settings.sort = (ctrl.display.settings.sort || []).filter(s => s[0] !== field);
        if (field) {
          ctrl.display.settings.sort.unshift([field, 'ASC']);
        }
      };

      this.$onInit = function () {
        if (!ctrl.display.settings) {
          ctrl.display.settings = {
            sort: ctrl.parent.getDefaultSort(),
          };
        }
        ctrl.parent.initColumns({});
      };

    }
  });

})(angular);
