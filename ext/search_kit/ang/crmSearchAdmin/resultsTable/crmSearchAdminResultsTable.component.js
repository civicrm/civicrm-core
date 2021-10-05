(function(angular, $, _) {
  "use strict";

  // Specialized searchDisplay, only used by Admins
  angular.module('crmSearchAdmin').component('crmSearchAdminResultsTable', {
    bindings: {
      search: '<'
    },
    require: {
      crmSearchAdmin: '^crmSearchAdmin'
    },
    templateUrl: '~/crmSearchAdmin/resultsTable/crmSearchAdminResultsTable.html',
    controller: function($scope, $element, searchMeta, searchDisplayBaseTrait, searchDisplayTasksTrait, searchDisplaySortableTrait) {
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        // Mix in traits to this controller
        ctrl = angular.extend(this, searchDisplayBaseTrait, searchDisplayTasksTrait, searchDisplaySortableTrait);

      // Output user-facing name/label fields as a link, if possible
      function getViewLink(fieldExpr, links) {
        var info = searchMeta.parseExpr(fieldExpr),
          firstField = _.findWhere(info.args, {type: 'field'}),
          entity = firstField && searchMeta.getEntity(firstField.field.entity);
        if (entity && firstField.field.fieldName === entity.label_field) {
          var joinEntity = searchMeta.getJoinEntity(info);
          return _.find(links, {join: joinEntity, action: 'view'});
        }
      }

      function buildSettings() {
        var links = ctrl.crmSearchAdmin.buildLinks();
        ctrl.apiEntity = ctrl.search.api_entity;
        ctrl.display = {
          type: 'table',
          settings: {
            limit: CRM.crmSearchAdmin.defaultPagerSize,
            pager: {show_count: true, expose_limit: true},
            actions: true,
            classes: ['table', 'table-striped'],
            button: ts('Search'),
            columns: _.transform(ctrl.search.api_params.select, function(columns, fieldExpr) {
              var column = {label: true, sortable: true},
                link = getViewLink(fieldExpr, links);
              if (link) {
                column.title = link.title;
                column.link = {
                  path: link.path,
                  target: '_blank'
                };
              }
              columns.push(searchMeta.fieldToColumn(fieldExpr, column));
            })
          }
        };
        if (links.length) {
          ctrl.display.settings.columns.push({
            text: '',
            icon: 'fa-bars',
            type: 'menu',
            size: 'btn-xs',
            style: 'secondary-outline',
            alignment: 'text-right',
            links: _.transform(links, function(links, link) {
              if (!link.isAggregate) {
                links.push({
                  path: link.path,
                  text: link.title,
                  icon: link.icon,
                  style: link.style,
                  target: link.action === 'view' ? '_blank' : 'crm-popup'
                });
              }
            })
          });
        }
        ctrl.debug = {
          apiParams: JSON.stringify(ctrl.search.api_params, null, 2)
        };
        ctrl.settings = ctrl.display.settings;
        setLabel();
      }

      function setLabel() {
        ctrl.display.label = ctrl.search.label || searchMeta.getEntity(ctrl.search.api_entity).title_plural;
      }

      this.$onInit = function() {
        buildSettings();
        this.initializeDisplay($scope, $element);
        $scope.$watch('$ctrl.search.api_entity', buildSettings);
        $scope.$watch('$ctrl.search.api_params', buildSettings, true);
        $scope.$watch('$ctrl.search.label', setLabel);
      };

      // Add callbacks for pre & post run
      this.onPreRun.push(function(apiParams) {
        apiParams.debug = true;
      });

      this.onPostRun.push(function(result) {
        ctrl.debug = _.extend(_.pick(ctrl.debug, 'apiParams'), result.debug);
      });

      $scope.sortableColumnOptions = {
        axis: 'x',
        handle: '.crm-draggable',
        update: function(e, ui) {
          // Don't allow items to be moved to position 0 if locked
          if (!ui.item.sortable.dropindex && ctrl.crmSearchAdmin.groupExists) {
            ui.item.sortable.cancel();
          }
          // Function selectors use `ng-repeat` with `track by $index` so must be refreshed when rearranging the array
          ctrl.crmSearchAdmin.hideFuncitons();
        }
      };

      $scope.fieldsForSelect = function() {
        return {results: ctrl.crmSearchAdmin.getAllFields(':label', ['Field', 'Custom', 'Extra', 'Pseudo'], function(key) {
            return _.contains(ctrl.search.api_params.select, key);
          })
        };
      };

      $scope.addColumn = function(col) {
        ctrl.crmSearchAdmin.addParam('select', col);
      };

      $scope.removeColumn = function(index) {
        ctrl.crmSearchAdmin.clearParam('select', index);
      };

    }
  });

})(angular, CRM.$, CRM._);
