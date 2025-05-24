(function(angular, $, _) {
  "use strict";

  // Specialized searchDisplay, only used by Admins
  angular.module('crmSearchAdmin').component('crmSearchAdminSegmentListing', {
    bindings: {
      filters: '<',
      totalCount: '=?'
    },
    templateUrl: '~/crmSearchDisplayTable/crmSearchDisplayTable.html',
    controller: function($scope, $element, crmApi4, searchMeta, searchDisplayBaseTrait, searchDisplaySortableTrait) {
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        // Mix in traits to this controller
        ctrl = angular.extend(this, _.cloneDeep(searchDisplayBaseTrait), _.cloneDeep(searchDisplaySortableTrait));

      this.apiEntity = 'SearchSegment';
      this.search = {
        api_entity: 'SearchSegment',
        api_params: {
          version: 4,
          select: [
            'id',
            'label',
            'description',
            'entity_name',
            'entity_name:label',
            'CONCAT("segment_", name) AS field_name',
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
          {deleteSegment: ['SearchSegment', 'delete', {where: [['id', '=', row.key]]}]},
          {start: ts('Deleting...'), success: ts('Segment Deleted')},
          row
        );
        // Delete field from metadata
        var entity = searchMeta.getEntity(row.data.entity_name);
        _.remove(entity.fields, {name: row.data.field_name});
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
            // Do not make columns editable because it would require a metadata refresh
            columns: [
              {
                key: 'label',
                label: ts('Label'),
                type: 'field',
                label_hidden: false
              },
              {
                key: 'description',
                label: ts('Description'),
                type: 'field',
                label_hidden: false
              },
              {
                key: 'entity_name:label',
                label: ts('For'),
                type: 'field',
                empty_value: ts('Missing'),
                cssRules: [
                  ['font-italic', 'entity_name:label', 'IS EMPTY']
                ],
                label_hidden: false
              },
              {
                type: 'include',
                label: ts('Items'),
                path: '~/crmSearchAdmin/searchSegmentListing/segments.html',
                label_hidden: false
              },
              {
                type: 'include',
                label: ts('Row Actions'),
                path: '~/crmSearchAdmin/searchSegmentListing/buttons.html',
                label_hidden: true
              }
            ]
          }
        };
        ctrl.settings = ctrl.display.settings;
      }

    }
  });

})(angular, CRM.$, CRM._);
