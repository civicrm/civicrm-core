(function(angular, $, _) {
  "use strict";

  // Declare module
  angular.module('crmSearchPage', CRM.angRequires('crmSearchPage'))

    .config(function($routeProvider) {
      // Load & render a SearchDisplay
      $routeProvider.when('/display/:savedSearchName/:displayName?', {
        controller: 'crmSearchPageDisplay',
        // Dynamic template generates the directive for each display type
        template: '<h1 crm-page-title>{{:: $ctrl.display.label }}</h1>\n' +
          '<form ng-include="\'~/crmSearchPage/displayType/\' + $ctrl.display.type + \'.html\'" id="bootstrap-theme"></form>',
        resolve: {
          // Load saved search display
          info: function($route, crmApi4) {
            var params = $route.current.params;
            var apiCalls = {
              search: ['SavedSearch', 'get', {
                select: ['name', 'api_entity'],
                where: [['name', '=', params.savedSearchName]]
              }, 0]
            };
            if (params.displayName) {
              apiCalls.display = ['SearchDisplay', 'get', {
                where: [['name', '=', params.displayName], ['saved_search_id.name', '=', params.savedSearchName]],
              }, 0];
            } else {
              apiCalls.display = ['SearchDisplay', 'getDefault', {
                savedSearch: params.savedSearchName,
              }, 0];
            }
            return crmApi4(apiCalls);
          }
        }
      });
    })

    // Controller for displaying a search
    .controller('crmSearchPageDisplay', function($scope, $location, info) {
      var ctrl = $scope.$ctrl = this;
      this.display = info.display;
      this.searchName = info.search.name;
      this.apiEntity = info.search.api_entity;

      $scope.$watch(function() {return $location.search();}, function(params) {
        ctrl.filters = params;
      });
    });

})(angular, CRM.$, CRM._);
