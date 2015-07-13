(function(angular, $, _) {
  angular.module('crmCxn').controller('CrmCxnConfirmConnectCtrl', function($scope) {
    $scope.ts = CRM.ts(null);
  });
})(angular, CRM.$, CRM._);
