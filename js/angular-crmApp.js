(function(angular, CRM) {
  var crmApp = angular.module('crmApp', CRM.angular.modules);
  crmApp.config(['$routeProvider',
    function($routeProvider) {
      $routeProvider.otherwise({
        template: ts('Unknown path')
      });
    }
  ]);
  crmApp.factory('crmApi', function($q) {
    return function(entity, action, params, message) {
      // JSON serialization in CRM.api3 is not aware of Angular metadata like $$hash, so use angular.toJson()
      var deferred = $q.defer();
      var p;
      if (_.isObject(entity)) {
        p = CRM.api3(eval('('+angular.toJson(entity)+')'), message);
      } else {
        p = CRM.api3(entity, action, eval('('+angular.toJson(params)+')'), message);
      }
      // CRM.api3 returns a promise, but the promise doesn't really represent errors as errors, so we
      // convert them
      p.then(
        function(result) {
          if (result.is_error) {
            deferred.reject(result);
          } else {
            deferred.resolve(result);
          }
        },
        function(error) {
          deferred.reject(error);
        }
      );
      return deferred.promise;
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
