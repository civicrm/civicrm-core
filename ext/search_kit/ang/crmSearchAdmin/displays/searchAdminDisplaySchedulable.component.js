(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('searchAdminDisplaySchedulable', {
    bindings: {
      display: '<',
      apiEntity: '<',
      apiParams: '<'
    },
    require: {
      parent: '^crmSearchAdminDisplay'
    },
    templateUrl: '~/crmSearchAdmin/displays/searchAdminDisplaySchedulable.html',
    controller: function($scope) {
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this;

      this.$onInit = function () {
        if (!ctrl.display.settings) {
          ctrl.display.settings = {
            limit: 20,
            pager: true
          };
        }
        ctrl.parent.initColumns({key: true, label: true, dataType: true, type: 'field'});
      };

    }
  });

})(angular, CRM.$, CRM._);
