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
          $scope.checkPerm = CRM.checkPerm;

          $el.addClass('afform-directive');

          // Afforms do not use routing, but some forms get input from search params
          var dialog = $el.closest('.ui-dialog-content');
          if (!dialog.length) {
            // Full-screen mode
            $scope.$watch(function() {return $location.search();}, function(params) {
              $scope.routeParams = params;
            });
          } else {
            // Use urlHash embedded in popup dialog
            $scope.routeParams = {};
            if (dialog.data('urlHash')) {
              var searchParams = new URLSearchParams(dialog.data('urlHash'));
              searchParams.forEach(function(value, key) {
                $scope.routeParams[key] = value;
              });
            }
          }

          $scope.$parent.afformTitle = meta.title;

          // Prepends a string to the afform title
          // Provides contextual titles to search Afforms in standalone mode
          $scope.addTitle = function(addition) {
            $scope.$parent.afformTitle = addition + ' ' + meta.title;
          };
        }
      };
      return d;
    };
  });
})(angular, CRM.$, CRM._);
