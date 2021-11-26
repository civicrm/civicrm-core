(function(angular, $, _) {
  "use strict";

  // Specialized searchDisplay, only used by Admins
  angular.module('crmSearchAdmin').component('crmSearchAdminSearchListing', {
    bindings: {
      filters: '<',
      tabCount: '='
    },
    templateUrl: '~/crmSearchDisplayTable/crmSearchDisplayTable.html',
    controller: function($scope, $q, crmApi4, crmStatus, searchMeta, searchDisplayBaseTrait, searchDisplaySortableTrait) {
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        // Mix in traits to this controller
        ctrl = angular.extend(this, searchDisplayBaseTrait, searchDisplaySortableTrait),
        afformLoad;

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
            // These two need to be in the select clause so they are allowed as filters
            'created_id.display_name',
            'modified_id.display_name',
            'created_date',
            'modified_date',
            'has_base',
            'base_module:label',
            'local_modified_date',
            'DATE(created_date) AS date_created',
            'DATE(modified_date) AS date_modified',
            'GROUP_CONCAT(display.name ORDER BY display.id) AS display_name',
            'GROUP_CONCAT(display.label ORDER BY display.id) AS display_label',
            'GROUP_CONCAT(display.type:icon ORDER BY display.id) AS display_icon',
            'GROUP_CONCAT(display.acl_bypass ORDER BY display.id) AS display_acl_bypass',
            'tags', // Not a selectable field but this hacks around the requirement that filters be in the select clause
            'GROUP_CONCAT(DISTINCT entity_tag.tag_id) AS tag_id',
            'GROUP_CONCAT(DISTINCT group.title) AS groups'
          ],
          join: [
            ['SearchDisplay AS display', 'LEFT', ['id', '=', 'display.saved_search_id']],
            ['Group AS group', 'LEFT', ['id', '=', 'group.saved_search_id']],
            ['EntityTag AS entity_tag', 'LEFT', ['entity_tag.entity_table', '=', '"civicrm_saved_search"'], ['id', '=', 'entity_tag.entity_id']],
          ],
          where: [['api_entity', 'IS NOT NULL']],
          groupBy: ['id']
        }
      };

      this.$onInit = function() {
        buildDisplaySettings();
        this.initializeDisplay($scope, $());
        // Keep tab counts up-to-date - put rowCount in current tab if there are no other filters
        $scope.$watch('$ctrl.rowCount', function(val) {
          if (typeof val === 'number' && angular.equals(['has_base'], _.keys(ctrl.filters))) {
            ctrl.tabCount = val;
          }
        });
      };

      this.onPostRun.push(function(result) {
        _.each(result, function(row) {
          row.permissionToEdit = CRM.checkPerm('all CiviCRM permissions and ACLs') || !_.includes(row.data.display_acl_bypass, true);
          // Saves rendering cycles to not show an empty menu of search displays
          if (!row.data.display_name) {
            row.openDisplayMenu = false;
          }
        });
        updateAfformCounts();
      });

      this.encode = function(params) {
        return encodeURI(angular.toJson(params));
      };

      this.deleteOrRevert = function(row) {
        var search = row.data,
          revert = !!search['base_module:label'];
        function getMessage() {
          var title = revert ? ts('Revert this search to its packaged settings?') : ts('Permanently delete this saved search?'),
            msg = '<h4>' + _.escape(title) + '</h4>' +
            '<ul>';
          if (revert) {
            if (search.display_label && search.display_label.length === 1) {
              msg += '<li>' + _.escape(ts('Includes 1 display which will also be reverted.')) + '</li>';
            } else if (search.display_label && search.display_label.length > 1) {
              msg += '<li>' + _.escape(ts('Includes %1 displays which will also be reverted.', {1: search.display_label.length})) + '</li>';
            }
            _.each(search.groups, function(smartGroup) {
              msg += '<li>' + _.escape(ts('Smart group "%1" will be reset to the packaged search criteria.', {1: smartGroup})) + '</li>';
            });
            if (row.afform_count) {
              _.each(ctrl.afforms[search.name], function(afform) {
                msg += '<li><i class="crm-i fa-list-alt"></i> ' + _.escape(ts('Form "%1" will be affected because it contains an embedded display from this search.', {1: afform.title})) + '</li>';
              });
            }
          } else {
            if (search.display_label && search.display_label.length === 1) {
              msg += '<li>' + _.escape(ts('Includes 1 display which will also be deleted.')) + '</li>';
            } else if (search.display_label && search.display_label.length > 1) {
              msg += '<li>' + _.escape(ts('Includes %1 displays which will also be deleted.', {1: search.display_label.length})) + '</li>';
            }
            _.each(search.groups, function (smartGroup) {
              msg += '<li class="crm-error"><i class="crm-i fa-exclamation-triangle"></i> ' + _.escape(ts('Smart group "%1" will also be deleted.', {1: smartGroup})) + '</li>';
            });
            if (row.afform_count) {
              _.each(ctrl.afforms[search.name], function (afform) {
                msg += '<li class="crm-error"><i class="crm-i fa-exclamation-triangle"></i> ' + _.escape(ts('Form "%1" will also be deleted because it contains an embedded display from this search.', {1: afform.title})) + '</li>';
              });
            }
          }
          return msg + '</ul>';
        }

        var dialog = CRM.confirm({
          title: revert ? ts('Revert %1', {1: search.label}) : ts('Delete %1', {1: search.label}),
          message: getMessage(),
        }).on('crmConfirm:yes', function() {
          $scope.$apply(function() {
            return revert ? ctrl.revertSearch(search) : ctrl.deleteSearch(search);
          });
        }).block();

        ctrl.loadAfforms().then(function() {
          dialog.html(getMessage()).unblock();
        });
      };

      this.deleteSearch = function(search) {
        crmStatus({start: ts('Deleting...'), success: ts('Search Deleted')},
          crmApi4('SavedSearch', 'delete', {where: [['id', '=', search.id]]}).then(function() {
            ctrl.rowCount = null;
            ctrl.runSearch();
          })
        );
      };

      this.revertSearch = function(search) {
        crmStatus({start: ts('Reverting...'), success: ts('Search Reverted')},
          crmApi4('SavedSearch', 'revert', {
            where: [['id', '=', search.id]],
            chain: {
              revertDisplays: ['SearchDisplay', 'revert', {'where': [['saved_search_id', '=', '$id'], ['has_base', '=', true]]}],
              deleteDisplays: ['SearchDisplay', 'delete', {'where': [['saved_search_id', '=', '$id'], ['has_base', '=', false]]}]
            }
          }).then(function() {
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
            classes: ['table', 'table-striped'],
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
                label: ts('Tags'),
                path: '~/crmSearchAdmin/searchListing/tags.html'
              },
              {
                type: 'include',
                label: ts('Displays'),
                path: '~/crmSearchAdmin/searchListing/displays.html'
              }
            ]
          }
        };
        if (ctrl.afformEnabled) {
          ctrl.display.settings.columns.push({
            type: 'include',
            label: ts('Forms'),
            path: '~/crmSearchAdmin/searchListing/afforms.html'
          });
        }
        ctrl.display.settings.columns.push(
          searchMeta.fieldToColumn('GROUP_CONCAT(DISTINCT group.title) AS groups', {
            label: ts('Smart Group')
          })
        );
        if (ctrl.filters.has_base) {
          ctrl.display.settings.columns.push(
            searchMeta.fieldToColumn('base_module:label', {
              label: ts('Package'),
              title: '[base_module]',
              empty_value: ts('Missing'),
              cssRules: [
                ['font-italic', 'base_module:label', 'IS EMPTY']
              ]
            })
          );
          ctrl.display.settings.columns.push(
            // Using 'local_modified_date' as the column + an empty_value will only show the rewritten value
            // if the record has been modified from its packaged state.
            searchMeta.fieldToColumn('local_modified_date', {
              label: ts('Modified'),
              empty_value: ts('No'),
              title: ts('Whether and when a search was modified from its packaged settings'),
              rewrite: ts('%1 by %2', {1: '[date_modified]', 2: '[modified_id.display_name]'}),
              cssRules: [
                ['font-italic', 'local_modified_date', 'IS EMPTY']
              ]
            })
          );
        } else {
          ctrl.display.settings.columns.push(
            searchMeta.fieldToColumn('created_date', {
              label: ts('Created'),
              title: '[created_date]',
              rewrite: ts('%1 by %2', {1: '[date_created]', 2: '[created_id.display_name]'})
            })
          );
          ctrl.display.settings.columns.push(
            searchMeta.fieldToColumn('modified_date', {
              label: ts('Modified'),
              title: '[modified_date]',
              rewrite: ts('%1 by %2', {1: '[date_modified]', 2: '[modified_id.display_name]'})
            })
          );
        }
        ctrl.display.settings.columns.push({
          type: 'include',
          alignment: 'text-right',
          path: '~/crmSearchAdmin/searchListing/buttons.html'
        });
        ctrl.settings = ctrl.display.settings;
      }

      // @return {Promise}
      this.loadAfforms = function() {
        if (!ctrl.afformEnabled && !afformLoad) {
          var deferred = $q.defer();
          afformLoad = deferred.promise;
          deferred.resolve([]);
        }
        if (afformLoad) {
          return afformLoad;
        }
        ctrl.afforms = null;
        afformLoad = crmApi4('Afform', 'get', {
          select: ['search_displays', 'name', 'title', 'server_route'],
          where: [['type', '=', 'search'], ['search_displays', 'IS NOT EMPTY']]
        }).then(function(afforms) {
          ctrl.afforms = {};
          _.each(afforms, function(afform) {
            _.each(_.uniq(afform.search_displays), function(searchNameDisplayName) {
              var searchName = searchNameDisplayName.split('.')[0];
              ctrl.afforms[searchName] = ctrl.afforms[searchName] || [];
              ctrl.afforms[searchName].push({
                title: afform.title,
                name: afform.name,
                link: ctrl.afformAdminEnabled ? CRM.url('civicrm/admin/afform#/edit/' + afform.name) : '',
                // FIXME: This is the view url, currently not exposed to the UI, as BS3 doesn't support submenus.
                url: afform.server_route ? CRM.url(afform.server_route) : null
              });
            });
          });
          updateAfformCounts();
        });
        return afformLoad;
      };

      function updateAfformCounts() {
        _.each(ctrl.results, function(row) {
          row.afform_count = ctrl.afforms && ctrl.afforms[row.data.name] && ctrl.afforms[row.data.name].length || 0;
        });
      }

    }
  });

})(angular, CRM.$, CRM._);
