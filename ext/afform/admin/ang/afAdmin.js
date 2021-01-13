(function(angular, $, _) {
  "use strict";
  angular.module('afAdmin', CRM.angRequires('afAdmin'))

    .config(function($routeProvider) {
      $routeProvider.when('/', {
        controller: 'afAdminList',
        reloadOnSearch: false,
        templateUrl: '~/afAdmin/afAdminList.html',
        resolve: {
          // Load data for lists
          afforms: function(crmApi4) {
            return crmApi4('Afform', 'get', {
              select: ['name', 'title', 'type', 'is_public', 'server_route', 'has_local', 'has_base'],
              orderBy: {title: 'ASC'}
            });
          }
        }
      });
      $routeProvider.when('/create/:type', {
        controller: 'afAdminGui',
        template: '<af-gui-editor type="$ctrl.type"></af-gui-editor>',
      });
      $routeProvider.when('/edit/:name', {
        controller: 'afAdminGui',
        template: '<af-gui-editor name="$ctrl.name"></af-gui-editor>',
      });
    });

})(angular, CRM.$, CRM._);
