(function(angular, $, _) {

  // Controller for the "Preview Mailing" dialog
  // Note: Expects $scope.model to be an object with properties:
  //   - "subject"
  //   - "body_html"
  //   - "body_text"
  angular.module('crmMailing').controller('PreviewMailingDialogCtrl', function PreviewMailingDialogCtrl($scope) {
    $scope.ts = CRM.ts('civi_mail');
  });

})(angular, CRM.$, CRM._);
