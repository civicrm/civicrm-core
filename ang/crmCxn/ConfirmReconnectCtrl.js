(function(angular, $, _) {
  angular.module('crmCxn').controller('CrmCxnConfirmReconnectCtrl', function($scope) {
    $scope.ts = CRM.ts(null);
  });
})(angular, CRM.$, CRM._);
