(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('crmSearchFunctionFlag', {
    bindings: {
      arg: '<',
      param: '<',
      flag: '@',
      writeExpr: '&'
    },
    templateUrl: '~/crmSearchAdmin/crmSearchFunctionFlag.html',
    controller: function($scope) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this;

      this.getWidget = function() {
        if (!ctrl.param || !ctrl.param[ctrl.flag]) {
          return null;
        } else if (_.keys(ctrl.param[ctrl.flag]).length === 2 && '' in ctrl.param[ctrl.flag]) {
          return 'checkbox';
        } else {
          return 'select';
        }
      };
    }
  });

})(angular, CRM.$, CRM._);
