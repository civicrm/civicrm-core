(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('crmSearchAdminSegment', {
    bindings: {
      segmentId: '<',
    },
    templateUrl: '~/crmSearchAdmin/searchSegment/crmSearchAdminSegment.html',
    controller: function ($scope, searchMeta, dialogService, crmApi4, crmStatus, formatForSelect2) {
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this;

      this.entitySelect = searchMeta.getPrimaryAndSecondaryEntitySelect();

      ctrl.saving = false;
      ctrl.segment = {items: []};

      // Drag-n-drop settings for reordering items
      this.sortableOptions = {
        containment: 'fieldset',
        axis: 'y',
        handle: '.crm-draggable',
        forcePlaceholderSize: true,
        helper: function(e, ui) {
          // Prevent table row width from changing during drag
          ui.children().each(function() {
            $(this).width($(this).width());
          });
          return ui;
        }
      };

      this.$onInit = function() {
        if (ctrl.segmentId) {
          $('.ui-dialog:visible').block();
          crmApi4('SearchSegment', 'get', {
            where: [['id', '=', ctrl.segmentId]]
          }, 0).then(function(segment) {
            ctrl.segment = segment;
            searchMeta.loadFieldOptions([segment.entity_name]);
            $('.ui-dialog:visible').unblock();
          });
        }
      };

      this.onChangeEntity = function() {
        ctrl.segment.items.length = 0;
        if (ctrl.segment.entity_name) {
          searchMeta.loadFieldOptions([ctrl.segment.entity_name]);
          ctrl.addItem(true);
        }
      };

      function getDefaultField() {
        var item = _.findLast(ctrl.segment.items, function(item) {
          return item.when && item.when[0] && item.when[0][0];
        });
        return item ? item.when[0][0] : searchMeta.getEntity(ctrl.segment.entity_name).fields[0].name;
      }

      this.addItem = function(addCondition) {
        var item = {label: ''};
        if (addCondition) {
          ctrl.addCondition(item);
        }
        ctrl.segment.items.push(item);
      };

      this.addCondition = function(item) {
        var defaultField = getDefaultField();
        item.when = item.when || [];
        item.when.push([defaultField, '=']);
      };

      this.hasDefault = function() {
        return !!_.findLast(ctrl.segment.items, function(item) {
          return !item.when || !item.when[0].length;
        });
      };

      this.getField = function(fieldName) {
        return searchMeta.getField(fieldName, ctrl.segment.entity_name);
      };

      this.selectFields = function() {
        return {results: formatForSelect2(searchMeta.getEntity(ctrl.segment.entity_name).fields, 'name', 'label', ['description'])};
      };

      this.save = function() {
        crmStatus({}, crmApi4('SearchSegment', 'save', {
          records: [ctrl.segment]
        })).then(function() {
          dialogService.close('searchSegmentDialog');
        });
      };

    }
  });

})(angular, CRM.$, CRM._);
