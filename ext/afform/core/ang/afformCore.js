(function(angular, $, _) {
  // Declare a list of dependencies.
  angular.module('afformCore', [
    'crmUi', 'crmUtil', 'ngRoute', 'api4'
  ]);

  // Use `afformCoreDirective(string name)` to generate an AngularJS directive.
  angular.module('afformCore').service('afformCoreDirective', function($routeParams, crmApi4, crmStatus, crmUiAlert){
    return function(camelName, meta, d){
      d.restrict = 'AE';
      d.scope = {};
      d.scope.options = '=' + camelName;
      d.link = function($scope, $el, $attr) {
        $scope.ts = CRM.ts(camelName);
        $scope.routeParams = $routeParams;
        $scope.meta = meta;
        $scope.crmApi4 = crmApi4;
        $scope.crmStatus = crmStatus;
        $scope.crmUiAlert = crmUiAlert;
      };
      return d;
    };
  });
})(angular, CRM.$, CRM._);
