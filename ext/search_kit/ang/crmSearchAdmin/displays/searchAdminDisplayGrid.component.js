(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('searchAdminDisplayGrid', {
    bindings: {
      display: '<',
      apiEntity: '<',
      apiParams: '<'
    },
    require: {
      parent: '^crmSearchAdminDisplay'
    },
    templateUrl: '~/crmSearchAdmin/displays/searchAdminDisplayGrid.html',
    controller: function($scope, searchMeta) {
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this;

      this.$onInit = function () {
        if (!ctrl.display.settings) {
          ctrl.display.settings = {
            colno: '3',
            limit: CRM.crmSearchAdmin.defaultPagerSize,
            sort: [],
            pager: {}
          };
          if (searchMeta.getEntity(ctrl.apiEntity).order_by) {
            ctrl.display.settings.sort.push([searchMeta.getEntity(ctrl.apiEntity).order_by, 'ASC']);
          }
        }
        ctrl.parent.initColumns({});
      };

    }
  });

})(angular, CRM.$, CRM._);
