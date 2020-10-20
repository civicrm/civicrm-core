(function(angular, $, _) {
  "use strict";

  // Declare module
  angular.module('crmSearchPage', CRM.angRequires('crmSearchPage'))


    .config(function($routeProvider) {
      $routeProvider.when('/display/:savedSearchName/:displayName', {
        controller: 'crmSearchPageDisplay',
        templateUrl: '~/crmSearchPage/display.html',
        resolve: {
          // Load saved search display
          display: function($route, crmApi4) {
            var params = $route.current.params;
            return crmApi4('SearchDisplay', 'get', {
              where: [['name', '=', params.displayName], ['saved_search.name', '=', params.savedSearchName]],
              select: ['*', 'saved_search.api_entity', 'saved_search.api_params']
            }, 0);
          }
        }
      });
    })

    // Controller for displaying a search
    .controller('crmSearchPageDisplay', function($scope, $routeParams, $location, display) {
      this.display = display;
      $scope.$ctrl = this;
    });

})(angular, CRM.$, CRM._);
