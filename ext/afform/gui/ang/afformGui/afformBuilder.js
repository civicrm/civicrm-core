(function(angular, $, _) {

  angular.module('afformGui').config(function($routeProvider) {
    $routeProvider.when('/build', {
      controller: 'afformBuilder',
      templateUrl: '~/afformGui/afformBuilder.html'
    });
  });

  // The controller uses *injection*. This default injects a few things:
  //   $scope -- This is the set of variables shared between JS and HTML.
  //   crmApi, crmStatus, crmUiHelp -- These are services provided by civicrm-core.
  //   myContact -- The current contact, defined above in config().
  angular.module('afformGui').controller('afformBuilder', function($scope, crmApi4, crmStatus, crmUiHelp) {
    // The ts() and hs() functions help load strings for this module.
    var ts = $scope.ts = CRM.ts('afformGui');
    var hs = $scope.hs = crmUiHelp({file: 'CRM/AfformGui/afformBuilder'});


  });

})(angular, CRM.$, CRM._);
