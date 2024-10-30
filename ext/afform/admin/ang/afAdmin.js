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
              select: ['name', 'title', 'type', 'server_route', 'is_public', 'submission_count', 'submission_date', 'submit_limit', 'submit_enabled', 'submit_currently_open', 'has_local', 'has_base', 'base_module', 'base_module:label', 'placement:label']
            });
          }
        }
      });
      $routeProvider.when('/create/:type/:entity', {
        controller: 'afAdminGui',
        template: '<af-gui-editor mode="create" data="$ctrl.data" entity="$ctrl.entity"></af-gui-editor>',
        resolve: {
          // Load data for gui editor
          data: function($route, crmApi4) {
            return crmApi4('Afform', 'loadAdminData', {
              definition: {type: $route.current.params.type},
              entity: $route.current.params.entity
            }, 0);
          }
        }
      });
      $routeProvider.when('/edit/:name', {
        controller: 'afAdminGui',
        template: '<af-gui-editor mode="edit" data="$ctrl.data"></af-gui-editor>',
        resolve: {
          // Load data for gui editor
          data: function($route, crmApi4) {
            return crmApi4('Afform', 'loadAdminData', {
              definition: {name: $route.current.params.name}
            }, 0);
          }
        }
      });
      $routeProvider.when('/clone/:name', {
        controller: 'afAdminGui',
        template: '<af-gui-editor mode="clone" data="$ctrl.data"></af-gui-editor>',
        resolve: {
          // Load data for gui editor
          data: function($route, crmApi4) {
            return crmApi4('Afform', 'loadAdminData', {
              definition: {name: $route.current.params.name}
            }, 0);
          }
        }
      });
    });

})(angular, CRM.$, CRM._);
