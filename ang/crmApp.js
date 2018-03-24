(function(angular, CRM) {
  // crmApp is the default application which aggregates all known modules.
  // crmApp should not provide any significant services, and no other
  // modules should depend on it.
  var crmApp = angular.module('crmApp', CRM.angular.modules);
  crmApp.config(['$routeProvider',
    function($routeProvider) {

      var route;
      if (CRM.crmApp && CRM.crmApp.activeRoute) {
        route = CRM.crmApp.activeRoute;
      }
      else if (CRM.crmApp && CRM.crmApp.defaultRoute) {
        route = CRM.crmApp.defaultRoute;
      }

      if (route) {
        $routeProvider.when('/', {
          template: '<div></div>',
          controller: function($location) {
            $location.path(route);
          }
        });
      }

      $routeProvider.otherwise({
        template: ts('Unknown path')
      });
    }
  ]);
})(angular, CRM);
