(function(angular, CRM) {
  var crmApp = angular.module('crmApp', CRM.angular.modules);
  crmApp.config(['$routeProvider',
    function($routeProvider) {
      $routeProvider.otherwise({
        template: ts('Unknown path')
      });
    }
  ]);
  crmApp.factory('crmApi', function() {
    return function(entity, action, params, message) {
      // JSON serialization in CRM.api3 is not aware of Angular metadata like $$hash
      if (CRM._.isObject(entity)) {
        return CRM.api3(eval('('+angular.toJson(entity)+')'), message);
      } else {
        return CRM.api3(entity, action, eval('('+angular.toJson(params)+')'), message);
      }
    };
  });
  crmApp.factory('crmLegacy', function() {
    return CRM;
  });
  crmApp.factory('crmNavigator', ['$window', function($window) {
    return {
      redirect: function(path) {
        $window.location.href = path;
      }
    };
  }]);
})(angular, CRM);
