(function(angular, $, _) {

  // Controller for the "Open Link" dialog
  // Scope members:
  //   - [input] "model": Object
  //     - "url": string
  angular.module('crmCxn').controller('CrmCxnLinkDialogCtrl', function CrmCxnLinkDialogCtrl($scope, dialogService) {
    var ts = $scope.ts = CRM.ts(null);
  });

})(angular, CRM.$, CRM._);
