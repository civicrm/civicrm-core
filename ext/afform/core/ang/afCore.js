(function(angular, $, _) {
  // Declare a list of dependencies.
  angular.module('afCore', CRM.angRequires('afCore'));

  // Use `afCoreDirective(string name)` to generate an AngularJS directive.
  angular.module('afCore').service('afCoreDirective', function($location, crmApi4, crmStatus, crmUiAlert) {
    return function(camelName, meta, d) {
      d.restrict = 'E';
      d.scope = {};
      d.scope.options = '=';
      d.link = {
        pre: function($scope, $el, $attr) {
          $scope.ts = CRM.ts(camelName);
          $scope.meta = meta;
          $scope.crmApi4 = crmApi4;
          $scope.crmStatus = crmStatus;
          $scope.crmUiAlert = crmUiAlert;
          $scope.crmUrl = CRM.url;

          // Afforms do not use routing, but some forms get input from search params
          $scope.$watch(function() {return $location.search();}, function(params) {
            $scope.routeParams = params;
          });
        }
      };
      return d;
    };
  });
})(angular, CRM.$, CRM._);
