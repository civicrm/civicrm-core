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
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this;

      this.$onInit = function() {
        if (!ctrl.param || !ctrl.param[ctrl.flag]) {
          this.widget = null;
        } else if (_.keys(ctrl.param[ctrl.flag]).length === 2 && '' in ctrl.param[ctrl.flag]) {
          this.widget = 'checkbox';
        } else {
          this.widget = 'select';
        }
      };
    }
  });

})(angular, CRM.$, CRM._);
