(function(angular, $, _) {
  "use strict";

  // Specialized searchDisplay, only used by Admins
  angular.module('crmSearchAdmin').component('crmSearchAdminSearchListing', {
    bindings: {
      filters: '<',
      tabCount: '='
    },
    templateUrl: '~/crmSearchDisplayTable/crmSearchDisplayTable.html',
    controller: function($scope, $element, $q, crmApi4, crmStatus, searchMeta, searchDisplayBaseTrait, searchDisplaySortableTrait, searchDisplayEditableTrait) {
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        // Mix in traits to this controller
        ctrl = angular.extend(this, _.cloneDeep(searchDisplayBaseTrait), _.cloneDeep(searchDisplaySortableTrait), _.cloneDeep(searchDisplayEditableTrait)),
        afformLoad;

      $scope.crmUrl = CRM.url;
      this.searchDisplayPath = CRM.url('civicrm/search');
      this.afformPath = CRM.url('civicrm/admin/afform');
      this.afformEnabled = 'org.civicrm.afform' in CRM.crmSearchAdmin.modules;
      this.afformAdminEnabled = CRM.checkPerm('administer afform') &&
        'org.civicrm.afform_admin' in CRM.crmSearchAdmin.modules;
      const scheduledCommunicationsEnabled = 'scheduled_communications' in CRM.crmSearchAdmin.modules;
      const scheduledCommunicationsAllowed = scheduledCommunicationsEnabled && CRM.checkPerm('schedule communications');

      this.apiEntity = 'SavedSearch';
      this.search = {
        api_entity: 'SavedSearch',
        api_params: {
          version: 4,
          select: [
            'id',
            'name',
            'label',
            'description',
            'api_entity',
            'api_entity:label',
            'api_params',
            'is_template',
            // These two need to be in the select clause so they are allowed as filters
            'created_id.display_name',
            'modified_id.display_name',
            'created_date',
            'modified_date',
            'expires_date',
            'has_base',
            'base_module:label',
            'local_modified_date',
            'DATE(created_date) AS date_created',
            'DATE(modified_date) AS date_modified',
            'DATE(expires_date) AS expires',
            // Get all search displays
            'GROUP_CONCAT(UNIQUE display.name ORDER BY display.label) AS display_name',
            'GROUP_CONCAT(UNIQUE display.label ORDER BY display.label) AS display_label',
            'GROUP_CONCAT(UNIQUE display.type:icon ORDER BY display.label) AS display_icon',
            'GROUP_CONCAT(UNIQUE display.acl_bypass ORDER BY display.label) AS display_acl_bypass',
            'tags', // Not a selectable field but this hacks around the requirement that filters be in the select clause
            'GROUP_CONCAT(UNIQUE entity_tag.tag_id) AS tag_id',
            // Really there can only be 1 smart group per saved-search; aggregation is just for the sake of the query
            'GROUP_CONCAT(UNIQUE group.id) AS group_id',
            'GROUP_CONCAT(UNIQUE group.title) AS groups'
          ],
          join: [
            ['SearchDisplay AS display', 'LEFT', ['id', '=', 'display.saved_search_id']],
            ['Group AS group', 'LEFT', ['id', '=', 'group.saved_search_id']],
            ['EntityTag AS entity_tag', 'LEFT', ['entity_tag.entity_table', '=', '"civicrm_saved_search"'], ['id', '=', 'entity_tag.entity_id']],
          ],
          where: [['api_entity', 'IS NOT NULL'], ['is_current', '=', true]],
          groupBy: ['id']
        }
      };

      // Add scheduled communication to query if extension is enabled
      if (scheduledCommunicationsEnabled) {
        this.search.api_params.select.push('GROUP_CONCAT(UNIQUE schedule.id ORDER BY schedule.title) AS schedule_id');
        this.search.api_params.select.push('GROUP_CONCAT(UNIQUE schedule.title ORDER BY schedule.title) AS schedule_title');
        this.search.api_params.join.push(['ActionSchedule AS schedule', 'LEFT', ['schedule.mapping_id', '=', '"saved_search"'], ['id', '=', 'schedule.entity_value']]);
      }

      this.$onInit = function() {
        buildDisplaySettings();
        this.initializeDisplay($scope, $element);
        // Keep tab counts up-to-date - put rowCount in current tab if there are no other filters
        $scope.$watch('$ctrl.rowCount', function(val) {
          let activeFilters = getActiveFilters().filter(item => item !== 'has_base' && item !== 'is_template');
          if (typeof val === 'number' && !activeFilters.length) {
            ctrl.tabCount = val;
          }
        });
        // Customize the noResultsText
        $scope.$watch('$ctrl.filters', function() {
          ctrl.settings.noResultsText = (angular.equals(['has_base'], getActiveFilters())) ?
            ts('Welcome to SearchKit. Click the New Search button above to start composing your first search.') :
            ts('No Saved Searches match filter criteria.');
        }, true);
      };

      // Get the names of in-use filters
      function getActiveFilters() {
        return _.keys(_.pick(ctrl.filters, function(val) {
          return val !== null && (_.includes(['boolean', 'number'], typeof val) || val.length);
        }));
      }

      this.onPostRun.push(function(apiResults) {
        _.each(apiResults.run, function(row) {
          row.permissionToEdit = CRM.checkPerm('all CiviCRM permissions and ACLs') || !_.includes(row.data.display_acl_bypass, true);
          // If main entity doesn't exist, no can edit
          if (!row.data['api_entity:label']) {
            row.permissionToEdit = false;
          }
          // Users without 'schedule communications' permission do not have edit access
          if (scheduledCommunicationsEnabled && !scheduledCommunicationsAllowed && row.data.schedule_id) {
            row.permissionToEdit = false;
          }
          // Saves rendering cycles to not show an empty menu of search displays
          if (!row.data.display_name) {
            row.openDisplayMenu = false;
          }
        });
        updateAfformCounts();
      });

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
            _.each(search.schedule_title, (communication) => {
              msg += '<li class="crm-error"><i class="crm-i fa-exclamation-triangle"></i> ' + _.escape(ts('Communication "%1" will also be deleted.', {1: communication})) + '</li>';
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
            return revert ? ctrl.revertSearch(row) : ctrl.deleteSearch(row);
          });
        }).block();

        ctrl.loadAfforms().then(function() {
          dialog.html(getMessage()).unblock();
        });
      };

      this.deleteSearch = function(row) {
        ctrl.runSearch(
          {deleteSearch: ['SavedSearch', 'delete', {where: [['id', '=', row.key]]}]},
          {start: ts('Deleting...'), success: ts('Search Deleted')},
          row
        );
      };

      this.revertSearch = function(row) {
        ctrl.runSearch(
          {revertSearch: ['SavedSearch', 'revert', {
            where: [['id', '=', row.key]],
            chain: {
              revertDisplays: ['SearchDisplay', 'revert', {'where': [['saved_search_id', '=', '$id'], ['has_base', '=', true]]}],
              deleteDisplays: ['SearchDisplay', 'delete', {'where': [['saved_search_id', '=', '$id'], ['has_base', '=', false]]}]
            }
          }]},
          {start: ts('Reverting...'), success: ts('Search Reverted')},
          row
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
                editable: true
              }),
              searchMeta.fieldToColumn('description', {
                label: true,
                title: ts('Edit Description'),
                editable: true
              }),
              searchMeta.fieldToColumn('api_entity:label', {
                label: true,
                empty_value: ts('Missing'),
                cssRules: [
                  ['font-italic', 'api_entity:label', 'IS EMPTY']
                ]
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
        if (ctrl.afformEnabled && !ctrl.filters.is_template) {
          ctrl.display.settings.columns.push({
            type: 'include',
            label: ts('Forms'),
            path: '~/crmSearchAdmin/searchListing/afforms.html'
          });
        }
        // Add scheduled communication column if user is allowed to use them
        if (scheduledCommunicationsAllowed && !ctrl.filters.is_template) {
          ctrl.display.settings.columns.push({
            type: 'include',
            label: ts('Communications'),
            path: '~/crmSearchAdmin/searchListing/communications.html'
          });
        }
        if (!ctrl.filters.is_template) {
          ctrl.display.settings.columns.push(
            searchMeta.fieldToColumn('GROUP_CONCAT(UNIQUE group.title) AS groups', {
              label: ts('Smart Group')
            })
          );
        }
        if (ctrl.filters.has_base || ctrl.filters.is_template) {
          ctrl.display.settings.columns.push(
            searchMeta.fieldToColumn('base_module:label', {
              label: ts('Package'),
              title: '[base_module]',
              empty_value: ctrl.filters.has_base ? ts('Missing') : null,
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
        if (!ctrl.filters.is_template) {
          ctrl.display.settings.columns.push(
            searchMeta.fieldToColumn('expires_date', {
              label: ts('Expires'),
              title: '[expires_date]',
              rewrite: '[expires]'
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
