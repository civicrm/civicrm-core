(function(angular, $, _) {

  angular.module('crmMailingAB').controller('CrmMailingABNewCtrl', function($scope, abtest, $location) {
    // Transition URL "/abtest/new/foo" => "/abtest/123/foo"
    var parts = $location.path().split('/'); // e.g. "/mailing/new" or "/mailing/123/wizard"
    parts[2] = abtest.id;
    $location.path(parts.join('/'));
    $location.replace();
  });

})(angular, CRM.$, CRM._);
