(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('crmSearchAdminSegment', {
    bindings: {
      segment: '<',
    },
    templateUrl: '~/crmSearchAdmin/searchSegment/crmSearchAdminSegment.html',
    controller: function ($scope, searchMeta, dialogService, crmApi4, crmStatus) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this;
      let originalEntityName;
      let originalField;

      this.entitySelect = searchMeta.getPrimaryAndSecondaryEntitySelect();

      ctrl.saving = false;

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
        if (ctrl.segment.id) {
          $('.ui-dialog:visible').block();
          crmApi4('SearchSegment', 'get', {
            where: [['id', '=', ctrl.segment.id]]
          }, 0).then(function(segment) {
            ctrl.segment = segment;
            originalEntityName = segment.entity_name;
            originalField = 'segment_' + segment.name;
            searchMeta.loadFieldOptions([segment.entity_name]);
            $('.ui-dialog:visible').unblock();
          });
        } else {
          ctrl.segment.items = [];
          ctrl.onChangeEntity();
        }
      };

      this.onChangeEntity = function() {
        ctrl.segment.items.length = 0;
        if (ctrl.segment.entity_name) {
          searchMeta.loadFieldOptions([ctrl.segment.entity_name]);
          ctrl.addItem(true);
        }
      };

      this.getOptionKey = function(expr) {
        return expr.split(':')[1] || 'id';
      };

      function getDefaultField() {
        const item = _.findLast(ctrl.segment.items, function(item) {
          return item.when && item.when[0] && item.when[0][0];
        });
        return item ? item.when[0][0] : searchMeta.getEntity(ctrl.segment.entity_name).fields[0].name;
      }

      this.addItem = function(addCondition) {
        const item = {label: ''};
        if (addCondition) {
          ctrl.addCondition(item);
        }
        ctrl.segment.items.push(item);
      };

      this.addCondition = function(item) {
        const defaultField = getDefaultField();
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

      // Select2-formatted fields that can be used in "when" clause, including :name suffix if applicable
      this.selectFields = function() {
        const fields = {results: []};
        _.each(searchMeta.getEntity(ctrl.segment.entity_name).fields, function(field) {
          const item = {
            id: field.name + (field.suffixes && _.includes(field.suffixes, 'name') ? ':name' : ''),
            text: field.label,
            description: field.description
          };
          fields.results.push(item);
        });
        return fields;
      };

      this.save = function() {
        crmStatus({}, crmApi4('SearchSegment', 'save', {
          records: [ctrl.segment],
          chain: {
            fields: [ctrl.segment.entity_name, 'getFields', {
              loadOptions: ['id', 'name', 'label', 'description', 'color', 'icon'],
              where: [['type', '=', 'Extra'], ['name', 'LIKE', 'segment_%']]
            }]
          }
        }, 0)).then(function(saved) {
          // If entity changed, remove field from original entity
          if (originalEntityName) {
            const originalEntity = searchMeta.getEntity(originalEntityName);
            if (originalEntity) {
              originalEntity.fields = originalEntity.fields.filter(field => field.name !== originalField);
            }
          }
          // Refresh all segment fields in this entity
          const entity = searchMeta.getEntity(ctrl.segment.entity_name);
          entity.fields = entity.fields.filter(field => !field.name.startsWith('segment_'));
          saved.fields.forEach(field => {
            field.fieldName = field.name;
            entity.fields.push(field);
          });
          entity.fields.sort((a, b) => a.label.localeCompare(b.label));
          dialogService.close('searchSegmentDialog');
        });
      };

    }
  });

})(angular, CRM.$, CRM._);
