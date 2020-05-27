(function(angular, $, _) {

  // Controller for the "Preview Mailing Component" dialog
  // Note: Expects $scope.model to be an object with properties:
  //   - "name"
  //   - "subject"
  //   - "body_html"
  //   - "body_text"
  angular.module('crmMailing').controller('PreviewComponentDialogCtrl', function PreviewComponentDialogCtrl($scope) {
    $scope.ts = CRM.ts(null);
  });

})(angular, CRM.$, CRM._);
