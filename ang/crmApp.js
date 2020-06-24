(function(angular, CRM) {
  // crmApp is the default application which aggregates all known modules.
  // crmApp should not provide any significant services, and no other
  // modules should depend on it.
  var crmApp = angular.module('crmApp', CRM.angular.modules);
  crmApp.config(['$routeProvider',
    function($routeProvider) {

      if (CRM.crmApp.defaultRoute) {
        $routeProvider.when('/', {
          template: '<div></div>',
          controller: function($location) {
            $location.path(CRM.crmApp.defaultRoute);
          }
        });
      }

      $routeProvider.otherwise({
        template: ts('Unknown path')
      });
    }
  ]);
})(angular, CRM);
