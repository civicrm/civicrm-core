(function(angular, $, _) {
  "use strict";

  // Specialized searchDisplay, only used by Admins
  angular.module('crmSearchAdmin').component('crmSearchAdminSegmentListing', {
    bindings: {
      filters: '<',
      totalCount: '='
    },
    templateUrl: '~/crmSearchDisplayTable/crmSearchDisplayTable.html',
    controller: function($scope, $element, crmApi4, searchMeta, searchDisplayBaseTrait, searchDisplaySortableTrait) {
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        // Mix in traits to this controller
        ctrl = angular.extend(this, searchDisplayBaseTrait, searchDisplaySortableTrait);

      this.apiEntity = 'SearchSegment';
      this.search = {
        api_entity: 'SearchSegment',
        api_params: {
          version: 4,
          select: [
            'label',
            'description',
            'entity_name:label',
            'items'
          ],
          join: [],
          where: [],
          groupBy: []
        }
      };

      this.$onInit = function() {
        buildDisplaySettings();
        this.initializeDisplay($scope, $element);
      };

      this.deleteSegment = function(row) {
        ctrl.runSearch(
          [['SearchSegment', 'delete', {where: [['id', '=', row.key]]}]],
          {start: ts('Deleting...'), success: ts('Segment Deleted')},
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
            sort: [['label', 'ASC']],
            columns: [
              {
                key: 'label',
                label: ts('Label'),
                title: ts('Edit Label'),
                type: 'field',
                editable: true
              },
              {
                key: 'description',
                label: ts('Description'),
                type: 'field',
                editable: true
              },
              {
                key: 'entity_name:label',
                label: ts('For'),
                type: 'field',
                empty_value: ts('Missing'),
                cssRules: [
                  ['font-italic', 'entity_name:label', 'IS EMPTY']
                ]
              },
              {
                type: 'include',
                label: ts('Items'),
                path: '~/crmSearchAdmin/searchSegmentListing/segments.html'
              },
              {
                type: 'include',
                label: '',
                path: '~/crmSearchAdmin/searchSegmentListing/buttons.html'
              }
            ]
          }
        };
        ctrl.settings = ctrl.display.settings;
      }

    }
  });

})(angular, CRM.$, CRM._);
