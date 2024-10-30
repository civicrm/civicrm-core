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
    controller: function($scope, searchMeta, crmUiHelp) {
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this;
      $scope.hs = crmUiHelp({file: 'CRM/Search/Help/Display'});

      this.getColTypes = function() {
        return ctrl.parent.colTypes;
      };

      this.$onInit = function () {
        if (!ctrl.display.settings) {
          ctrl.display.settings = {
            colno: '3',
            limit: ctrl.parent.getDefaultLimit(),
            sort: ctrl.parent.getDefaultSort(),
            pager: {}
          };
        }
        ctrl.parent.initColumns({});
      };

    }
  });

})(angular, CRM.$, CRM._);
