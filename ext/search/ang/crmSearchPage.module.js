(function(angular, $, _) {
  "use strict";

  // Declare module
  angular.module('crmSearchPage', CRM.angRequires('crmSearchPage'))

    .config(function($routeProvider) {
      // Load & render a SearchDisplay
      $routeProvider.when('/display/:savedSearchName/:displayName', {
        controller: 'crmSearchPageDisplay',
        // Dynamic template generates the directive for each display type
        template: function() {
          var html =
            '<h1 crm-page-title>{{:: $ctrl.display.label }}</h1>\n' +
            '<div ng-switch="$ctrl.display.type" id="bootstrap-theme">\n';
          _.each(CRM.crmSearchPage.displayTypes, function(type) {
            html +=
            '  <div ng-switch-when="' + type.name + '">\n' +
            '    <crm-search-display-' + type.name + ' api-entity="$ctrl.apiEntity" api-params="$ctrl.apiParams" settings="$ctrl.display.settings"></crm-search-display-' + type.name + '>\n' +
            '  </div>\n';
          });
          html += '</div>';
          return html;
        },
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
      this.apiEntity = display['saved_search.api_entity'];
      this.apiParams = display['saved_search.api_params'];
      $scope.$ctrl = this;
    });

})(angular, CRM.$, CRM._);
