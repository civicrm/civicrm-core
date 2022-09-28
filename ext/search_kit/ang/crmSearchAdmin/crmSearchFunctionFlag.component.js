(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('crmSearchFunctionFlag', {
    bindings: {
      arg: '<',
      param: '<',
      writeExpr: '&'
    },
    templateUrl: '~/crmSearchAdmin/crmSearchFunctionFlag.html',
    controller: function($scope) {
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this;

      this.$onInit = function() {
        if (!ctrl.param || !ctrl.param.flag_before) {
          this.widget = null;
        } else if (_.keys(ctrl.param.flag_before).length === 2 && '' in ctrl.param.flag_before) {
          this.widget = 'checkbox';
        } else {
          this.widget = 'select';
        }
      };
    }
  });

})(angular, CRM.$, CRM._);
