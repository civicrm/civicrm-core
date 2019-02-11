(function(angular, $, _) {

  angular.module('afformGui').config(function($routeProvider) {
    $routeProvider.when('/list', {
      controller: 'afformList',
      templateUrl: '~/afformGui/afformList.html',
      resolve: {
        afforms: function(crmApi4) {
          return crmApi4('Afform', 'get');
        }
      }
    });
  });

  // The controller uses *injection*. This default injects a few things:
  //   $scope -- This is the set of variables shared between JS and HTML.
  //   crmApi, crmStatus, crmUiHelp -- These are services provided by civicrm-core.
  //   myContact -- The current contact, defined above in config().
  angular.module('afformGui').controller('afformList', function($scope, crmApi4, crmStatus, crmUiHelp, afforms) {
    // The ts() and hs() functions help load strings for this module.
    var ts = $scope.ts = CRM.ts('afformGui');
    var hs = $scope.hs = crmUiHelp({file: 'CRM/AfformGui/afformList'});
    $scope.afforms = afforms;

  });

})(angular, CRM.$, CRM._);
