(function(angular, $, _) {

  // Controller for the "Preview Recipients" dialog
  // Note: Expects $scope.model to be an object with properties:
  //   - recipients: array of contacts
  angular.module('crmMailing').controller('PreviewRecipCtrl', function($scope) {
    $scope.ts = CRM.ts(null);
  });

})(angular, CRM.$, CRM._);
