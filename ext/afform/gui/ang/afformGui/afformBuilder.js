(function(angular, $, _) {

  angular.module('afformGui').config(function($routeProvider) {
    $routeProvider.when('/build/:afformName?', {
      controller: 'afformBuilder',
      templateUrl: '~/afformGui/afformBuilder.html',
      resolve: {
        afform: function(crmApi4, $route) {
          var name = $route.current.params.afformName;
          if (name) {
            return crmApi4('Afform', 'get', {where: [['name', '=', name]]});
          }
        }
      }
    });
  });

  // The controller uses *injection*. This default injects a few things:
  //   $scope -- This is the set of variables shared between JS and HTML.
  //   crmApi, crmStatus, crmUiHelp -- These are services provided by civicrm-core.
  //   myContact -- The current contact, defined above in config().
  angular.module('afformGui').controller('afformBuilder', function($scope, $routeParams, crmApi4, crmStatus, crmUiHelp, afform) {
    // The ts() and hs() functions help load strings for this module.
    var ts = $scope.ts = CRM.ts('afformGui');
    var hs = $scope.hs = crmUiHelp({file: 'CRM/AfformGui/afformBuilder'});
    $scope.afform = afform;

  });

})(angular, CRM.$, CRM._);
