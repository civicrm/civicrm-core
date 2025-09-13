(function(angular, $, _) {
  "use strict";

  // Declare module
  angular.module('crmSearchPage', CRM.angRequires('crmSearchPage'))

    .config(function($routeProvider) {
      // Load & render a SearchDisplay
      $routeProvider.when('/display/:savedSearchName/:displayName?', {
        controller: 'crmSearchPageDisplay',
        template: '<h1 crm-page-title>{{:: $ctrl.display.label }}</h1>\n' +
          '<form id="bootstrap-theme">' +
          // Edit link for authorized users
          '  <div class="pull-right btn-group" ng-if="$ctrl.editLink">' +
          '    <a class="btn btn-sm" ng-href="{{:: $ctrl.editLink }}"><i class="crm-i fa-pencil" role="img" aria-hidden="true"></i> {{:: ts("Edit Search") }}</a>' +
          '  </div>' +
          // Dynamic template generates the directive for each display type
          // @see \Civi\Search\Display::getPartials()
          '  <div ng-include="\'~/crmSearchPage/displayType/\' + $ctrl.display.type + \'.html\'"></div>' +
          '</form>',
        resolve: {
          // Load saved search display
          info: function($route, crmApi4) {
            var params = $route.current.params;
            var apiCalls = {
              search: ['SavedSearch', 'get', {
                select: ['id', 'name', 'api_entity'],
                where: [['name', '=', params.savedSearchName]],
                chain: {
                  checkAccess: ['SavedSearch', 'checkAccess', {action: 'update', values: {id: '$id'}}, 0],
                },
              }, 0],
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
      const ctrl = $scope.$ctrl = this;
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit');
      this.display = info.display;
      this.searchName = info.search.name;
      this.apiEntity = info.search.api_entity;
      // Format edit link if user has access
      this.editLink = info.search.checkAccess.access ? CRM.url('civicrm/admin/search#/edit/' + info.search.id) : false;

      $scope.$watch(function() {return $location.search();}, function(params) {
        ctrl.filters = params;
      });
    });

})(angular, CRM.$, CRM._);
