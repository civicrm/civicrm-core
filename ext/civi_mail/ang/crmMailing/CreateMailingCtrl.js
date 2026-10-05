(function(angular, $) {

  angular.module('crmMailing').controller('CreateMailingCtrl', function EditMailingCtrl($scope, selectedMail, $location) {
    $location.path("/mailing/" + selectedMail.id);
    $location.replace();
  });

})(angular, CRM.$);
