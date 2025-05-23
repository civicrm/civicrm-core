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
          const dialog = $el.closest('.ui-dialog-content');
          if (!dialog.length) {
            // Full-screen mode: watch search params in url
            $scope.$watch(function() {return $location.search();}, function(params) {
              $scope.routeParams = params;
            });
          } else {
            // Popup dialog mode: use urlHash (injected by civi.crmSnippet::refresh() function)
            $scope.routeParams = {};
            if (typeof dialog.data('urlHash') === 'string' && dialog.data('urlHash').includes('?')) {
              const searchParams = new URLSearchParams(dialog.data('urlHash').split('?')[1]);
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
