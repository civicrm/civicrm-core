(function(angular, $, _) {

  angular.module('crmMailing').controller('CreateMailingCtrl', function EditMailingCtrl($scope, selectedMail, $location) {
    // Transition URL "/mailing/new/foo" => "/mailing/123/foo"
    var parts = $location.path().split('/'); // e.g. "/mailing/new" or "/mailing/123/wizard"
    parts[2] = selectedMail.id;
    $location.path(parts.join('/'));
    $location.replace();
  });

})(angular, CRM.$, CRM._);
