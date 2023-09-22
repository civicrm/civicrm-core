(function(angular, $, _) {

  angular.module('crmDashboard').component('crmInactiveDashlet', {
    bindings: {
      dashlet: '<',
      delete: '&'
    },
    templateUrl: '~/crmDashboard/InactiveDashlet.html',
    controller: function ($scope, $element) {
      var ts = $scope.ts = CRM.ts(),
        ctrl = this;
      ctrl.isAdmin = CRM.checkPerm('administer CiviCRM');

      this.$onInit = function() {
        ctrl.confirmParams = {
          type: 'delete',
          obj: ctrl.dashlet,
          width: 400,
          message: ts('Do you want to remove this dashlet as an "Available Dashlet", AND delete it from all user dashboards?')
        };
      };
    }
  });

})(angular, CRM.$, CRM._);
