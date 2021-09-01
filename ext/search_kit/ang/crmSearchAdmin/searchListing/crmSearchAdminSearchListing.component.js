(function(angular, $, _) {
  "use strict";

  // Specialized searchDisplay, only used by Admins
  angular.module('crmSearchAdmin').component('crmSearchAdminSearchListing', {
    templateUrl: '~/crmSearchAdmin/searchListing/crmSearchAdminSearchListing.html',
    controller: function($scope, crmApi4, crmStatus, searchMeta, searchDisplayBaseTrait, searchDisplaySortableTrait) {
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        // Mix in traits to this controller
        ctrl = angular.extend(this, searchDisplayBaseTrait, searchDisplaySortableTrait);

      this.searchDisplayPath = CRM.url('civicrm/search');
      this.afformPath = CRM.url('civicrm/admin/afform');
      this.afformEnabled = CRM.crmSearchAdmin.afformEnabled;
      this.afformAdminEnabled = CRM.crmSearchAdmin.afformAdminEnabled;

      this.apiEntity = 'SavedSearch';
      this.search = {
        api_entity: 'SavedSearch',
        api_params: {
          version: 4,
          select: [
            'id',
            'name',
            'label',
            'api_entity',
            'api_entity:label',
            'api_params',
            'created_date',
            'modified_date',
            'GROUP_CONCAT(display.name ORDER BY display.id) AS display_name',
            'GROUP_CONCAT(display.label ORDER BY display.id) AS display_label',
            'GROUP_CONCAT(display.type:icon ORDER BY display.id) AS display_icon',
            'GROUP_CONCAT(display.acl_bypass ORDER BY display.id) AS display_acl_bypass',
            'GROUP_CONCAT(DISTINCT group.title) AS groups'
          ],
          join: [['SearchDisplay AS display'], ['Group AS group']],
          where: [['api_entity', 'IS NOT NULL']],
          groupBy: ['id']
        }
      };

      this.$onInit = function() {
        buildDisplaySettings();
        this.initializeDisplay($scope, $());
      };

      this.onPostRun.push(function(result) {
        _.each(result, function(row) {
          row.permissionToEdit = CRM.checkPerm('all CiviCRM permissions and ACLs') || !_.includes(row.display_acl_bypass.raw, true);
          // Saves rendering cycles to not show an empty menu of search displays
          if (!row.display_name.raw) {
            row.openDisplayMenu = false;
          }
        });
        updateAfformCounts();
      });

      this.encode = function(params) {
        return encodeURI(angular.toJson(params));
      };

      this.deleteSearch = function(search) {
        crmStatus({start: ts('Deleting...'), success: ts('Search Deleted')},
          crmApi4('SavedSearch', 'delete', {where: [['id', '=', search.id.raw]]}).then(function() {
            ctrl.rowCount = null;
            ctrl.runSearch();
          })
        );
      };

      function buildDisplaySettings() {
        ctrl.display = {
          type: 'table',
          settings: {
            limit: CRM.crmSearchAdmin.defaultPagerSize,
            pager: {show_count: true, expose_limit: true},
            actions: false,
            sort: [['modified_date', 'DESC']],
            columns: [
              searchMeta.fieldToColumn('label', {
                label: true,
                title: ts('Edit Label'),
                editable: {entity: 'SavedSearch', id: 'id', name: 'label', value: 'label'}
              }),
              searchMeta.fieldToColumn('api_entity:label', {
                label: ts('For'),
              }),
              {
                type: 'include',
                label: ts('Displays'),
                path: '~/crmSearchAdmin/searchListing/displays.html'
              },
              searchMeta.fieldToColumn('GROUP_CONCAT(DISTINCT group.title) AS groups', {
                label: ts('Smart Group')
              }),
              searchMeta.fieldToColumn('created_date', {
                label: ts('Created'),
                dataType: 'Date',
                rewrite: ts('%1 by %2', {1: '[created_date]', 2: '[created_id.display_name]'})
              }),
              searchMeta.fieldToColumn('modified_date', {
                label: ts('Last Modified'),
                dataType: 'Date',
                rewrite: ts('%1 by %2', {1: '[modified_date]', 2: '[modified_id.display_name]'})
              }),
              {
                type: 'include',
                alignment: 'text-right',
                path: '~/crmSearchAdmin/searchListing/buttons.html'
              }
            ]
          }
        };
        if (ctrl.afformEnabled) {
          ctrl.display.settings.columns.splice(3, 0, {
            type: 'include',
            label: ts('Forms'),
            path: '~/crmSearchAdmin/searchListing/afforms.html'
          });
        }
        ctrl.settings = ctrl.display.settings;
      }

      this.loadAfforms = function() {
        if (ctrl.afforms || ctrl.afforms === null) {
          return;
        }
        ctrl.afforms = null;
        crmApi4('Afform', 'get', {
          select: ['layout', 'name', 'title', 'server_route'],
          where: [['type', '=', 'search']],
          layoutFormat: 'html'
        }).then(function(afforms) {
          ctrl.afforms = {};
          _.each(afforms, function(afform) {
            var searchName = afform.layout.match(/<crm-search-display-[^>]+search-name[ ]*=[ ]*['"]([^"']+)/);
            if (searchName) {
              ctrl.afforms[searchName[1]] = ctrl.afforms[searchName[1]] || [];
              ctrl.afforms[searchName[1]].push({
                title: afform.title,
                name: afform.name,
                // FIXME: This is the view url, currently not exposed to the UI, as BS3 doesn't support submenus.
                url: afform.server_route ? CRM.url(afform.server_route) : null
              });
            }
          });
          updateAfformCounts();
        });
      };

      function updateAfformCounts() {
        _.each(ctrl.results, function(row) {
          row.afform_count = ctrl.afforms && ctrl.afforms[row.name.raw] && ctrl.afforms[row.name.raw].length || 0;
        });
      }

    }
  });

})(angular, CRM.$, CRM._);
