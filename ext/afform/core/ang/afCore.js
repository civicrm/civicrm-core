(function(angular, $, _) {
  // Declare a list of dependencies.
  angular.module('afCore', CRM.angRequires('afCore'));

  // Use `afCoreDirective(string name)` to generate an AngularJS directive.
  angular.module('afCore').service('afCoreDirective', function($routeParams, crmApi4, crmStatus, crmUiAlert) {
    return function(camelName, meta, d) {
      d.restrict = 'AE';
      d.scope = {};
      d.scope.options = '=' + camelName;
      d.link = {
        pre: function($scope, $el, $attr) {
          $scope.ts = CRM.ts(camelName);
          $scope.routeParams = $routeParams;
          $scope.meta = meta;
          $scope.crmApi4 = crmApi4;
          $scope.crmStatus = crmStatus;
          $scope.crmUiAlert = crmUiAlert;
          $scope.crmUrl = CRM.url;
        }
      };
      return d;
    };
  });
})(angular, CRM.$, CRM._);
