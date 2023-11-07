(function(angular, $, _) {

  // Controller for the "Recipients: Edit Options" dialog
  // Note: Expects $scope.model to be an object with properties:
  //   - "mailing" (APIv3 mailing object)
  //   - "fields" (list of fields)
  angular.module('crmMailing').controller('EditRecipOptionsDialogCtrl', function EditRecipOptionsDialogCtrl($scope, crmUiHelp) {
    $scope.ts = CRM.ts(null);
    $scope.hs = crmUiHelp({file: 'CRM/Mailing/MailingUI'});
  });

})(angular, CRM.$, CRM._);
