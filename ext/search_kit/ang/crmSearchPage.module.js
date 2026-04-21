(function(angular, $, _) {
  "use strict";

  // Declare module
  angular.module('crmSearchPage', CRM.angRequires('crmSearchPage'))

    .config(function($routeProvider) {
      // Load & render a SearchDisplay
      $routeProvider.when('/display/:savedSearchName/:displayName?', {
        controller: 'crmSearchPageDisplay',
        templateUrl: '~/crmSearchPage/crmSearchPage.html',
      });
    })

    // Controller for displaying a search
    .controller('crmSearchPageDisplay', function($scope, $location, $route, $timeout, crmApi4) {
      const ctrl = $scope.$ctrl = this;
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit');

      const routeParams = $route.current.params;

      // The crmSearchDisplay component will take care of loading & running the search.
      this.searchName = routeParams.savedSearchName;
      this.displayName = routeParams.displayName;

      // Check access for edit link. Defer via $timeout because this is lower priority than the search itself.
      $timeout(() => {
        crmApi4('SavedSearch', 'checkAccess', {
          action: 'update',
          values: {name: this.searchName},
        }, 0).then((result) => {
          // Format edit link if user has access
          if (result?.access) {
            this.editLink = CRM.url('civicrm/admin/search#/edit/' + result.id);
          }
        });
      }, 500);

      $scope.$watch(() => $location.search(), (params) => this.filters = params);
    });

})(angular, CRM.$, CRM._);
