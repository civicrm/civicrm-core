// https://civicrm.org/licensing
(function (angular, $, _) {
  "use strict";

  angular.module('afGuiEditor').component('afGuiEntity', {
    templateUrl: '~/afGuiEditor/afGuiEntity.html',
    bindings: {
      entity: '<'
    },
    require: {editor: '^^afGuiEditor'},
    controller: function ($scope, $timeout, afGui, formatForSelect2) {
      const ts = $scope.ts = CRM.ts('org.civicrm.afform_admin');
      const ctrl = this;
      $scope.controls = {};
      $scope.fieldList = [];
      $scope.blockList = [];
      $scope.blockTitles = [];
      $scope.elementList = [];
      $scope.elementTitles = [];

      this.getEntityType = () => {
        return ctrl.entity.type;
      };

      $scope.getMeta = () => {
        return afGui.meta.entities[ctrl.getEntityType()];
      };

      $scope.getField = afGui.getField;

      $scope.valuesFields = () => {
        const fields = Object.values($scope.getMeta().fields).map(field => ({
          id: field.name,
          text: field.label,
          disabled: $scope.fieldInUse(field.name)
        }));
        return {results: fields};
      };

      $scope.removeValue = (entity, fieldName) => {
        delete entity.data[fieldName];
      };

      this.buildPaletteLists = () => {
        const search = $scope.controls.fieldSearch ? $scope.controls.fieldSearch.toLowerCase() : null;
        buildFieldList(search);
        buildBlockList(search);
        buildElementList(search);
      };

      this.getOptionsTpl = () => {
        return $scope.getMeta().options_tpl || '~/afGuiEditor/entityConfig/EntityOptionsGeneric.html';
      };

      function buildFieldList(search) {
        $scope.fieldList.length = 0;
        $scope.fieldList.push({
          entityName: ctrl.entity.name,
          entityType: ctrl.getEntityType(),
          label: ts('%1 Fields', {1: $scope.getMeta().label}),
          fields: filterFields($scope.getMeta().fields)
        });
        // Add fields for af-join blocks
        Object.entries(afGui.meta.entities).forEach(([entityName, entity]) => {
          if (check(ctrl.editor.layout['#children'], {'af-join': entityName})) {
            $scope.fieldList.push({
              entityName: ctrl.entity.name + '-join-' + entityName,
              entityType: entityName,
              afJoin: entityName,
              label: ts('%1 Fields', {1: entity.label}),
              fields: filterFields(entity.fields)
            });
          }
        });

        function filterFields(fields) {
          return Object.values(fields).filter(field => {
            return !search || field.name.includes(search) || field.label.toLowerCase().includes(search);
          }).map(field => fieldDefaults(field));
        }

        function fieldDefaults(field) {
          const tag = {
            "#tag": "af-field",
            name: field.name
          };
          return tag;
        }
      }

      function buildBlockList(search) {
        $scope.blockList.length = 0;
        $scope.blockTitles.length = 0;
        Object.entries(afGui.meta.blocks).forEach(([directive, block]) => {
          if ((!search || directive.includes(search) || block.name.toLowerCase().includes(search) || block.title.toLowerCase().includes(search)) &&
            // A block of type "*" applies to everything. A block of type "Contact" also applies to "Individual", "Organization" & "Household".
            (block.entity_type === '*' || block.entity_type === ctrl.entity.type || (block.entity_type === 'Contact' && ['Individual', 'Household', 'Organization'].includes(ctrl.entity.type))) &&
            // Prevent recursion
            block.name !== ctrl.editor.getAfform().name
          ) {
            const item = {"#tag": block.join_entity ? "div" : directive};
            if (block.join_entity) {
              const joinEntity = afGui.getEntity(block.join_entity);
              // Skip adding block if entity does not exist
              if (!joinEntity) {
                return;
              }
              item['af-join'] = block.join_entity;
              item['#children'] = [{"#tag": directive}];
              if (joinEntity.repeat_max !== 1) {
                item['af-repeat'] = ts('Add');
                item['af-copy'] = ts('Copy');
                item.min = '1';
                if (typeof joinEntity.repeat_max === 'number') {
                  item.max = '' + joinEntity.repeat_max;
                }
              }
            }
            $scope.blockList.push(item);
            $scope.blockTitles.push(block.title);
          }
        });
      }

      function buildElementList(search) {
        $scope.elementList.length = 0;
        $scope.elementTitles.length = 0;
        Object.entries(afGui.meta.elements).forEach(([name, element]) => {
          if (
            (!element.afform_type || element.afform_type.includes('form')) &&
            (!search || name.includes(search) || element.title.toLowerCase().includes(search))) {
            const node = _.cloneDeep(element.element);
            if (name === 'fieldset') {
              if (!ctrl.editor.allowEntityConfig) {
                return;
              }
              node['af-fieldset'] = ctrl.entity.name;
            }
            $scope.elementList.push(node);
            $scope.elementTitles.push(name === 'fieldset' ? ts('Fieldset for %1', {1: ctrl.entity.label}) : element.title);
          }
        });
      }

      // This gets called from jquery-ui so we have to manually apply changes to scope
      $scope.buildPaletteLists = () => {
        $timeout(() => {
          $scope.$apply(() => {
            ctrl.buildPaletteLists();
          });
        });
      };

      // Checks if a field is on the form or set as a value
      $scope.fieldInUse = (fieldName, joinEntity) => {
        const data = ctrl.entity.data || {};
        if (!joinEntity) {
          return (fieldName in data) || check(ctrl.editor.layout['#children'], {'#tag': 'af-field', name: fieldName});
        }
        // Joins might support multiple instances per entity; first fetch them all
        const afJoinContainers = afGui.getFormElements(ctrl.editor.layout['#children'], {'af-join': joinEntity}, (item) => {
          return item['af-join'] || (item['af-fieldset'] && item['af-fieldset'] !== ctrl.entity.name);
        });
        // Check if ALL af-join containers are using the field
        let inUse = true;
        afJoinContainers.forEach((container) => {
          if (inUse && !check(container['#children'], {'#tag': 'af-field', name: fieldName})) {
            inUse = false;
          }
        });
        return inUse;
      };

      // Checks if fields in a block are already in use on the form.
      // Note that if a block contains no fields it can be used repeatedly, so this will always return false for those.
      $scope.blockInUse = (block) => {
        if (block['af-join']) {
          return check(ctrl.editor.layout['#children'], (item) => item['af-join'] === block['af-join'] && !(item.data && item.data.location_type_id));
        }
        const fieldsInBlock = afGui.findRecursive(afGui.meta.blocks[block['#tag']].layout, {'#tag': 'af-field'}).map(field => field.name);
        return check(ctrl.editor.layout['#children'], (item) => item['#tag'] === 'af-field' && fieldsInBlock.includes(item.name));
      };

      // Check for a matching item for this entity
      // Recursively checks the form layout, including block directives
      function check(group, criteria, found) {
        if (!found) {
          found = {};
        }
        if (_.find(group, criteria)) {
          found.match = true;
          return true;
        }
        group.forEach((item) => {
          if (found.match) {
            return false;
          }
          if (_.isPlainObject(item)) {
            // Recurse through everything but skip fieldsets for other entities
            if (!item['af-join'] && (!item['af-fieldset'] || (item['af-fieldset'] === ctrl.entity.name)) && item['#children']) {
              check(item['#children'], criteria, found);
            }
            // Recurse into block directives
            else if (item['#tag'] && item['#tag'] in afGui.meta.blocks) {
              check(afGui.meta.blocks[item['#tag']].layout, criteria, found);
            }
          }
        });
        return found.match;
      }

      this.addValue = function(fieldName) {
        if (fieldName) {
          if (!ctrl.entity.data) {
            ctrl.entity.data = {};
          }
          ctrl.entity.data[fieldName] = '';
        }
      };

      this.getFieldId = function(fieldName) {
        return _.kebabCase(ctrl.entity.name + '-' + fieldName);
      };

      this.$onInit = function() {
        // When a new block is saved, update the list
        this.meta = afGui.meta;
        $scope.$watchCollection('$ctrl.meta.blocks', function() {
          $scope.controls.fieldSearch = '';
          ctrl.buildPaletteLists();
        });

        const behaviorInfo = CRM.afGuiEditor.behaviors[ctrl.getEntityType()] || [];
        ctrl.behaviors = behaviorInfo.reduce((behaviors, behavior) => {
          const item = _.cloneDeep(behavior);
          item.options = formatForSelect2(item.modes, 'name', 'label', ['description', 'icon']);
          behaviors.push(item);
          return behaviors;
        }, []);
      };
    }
  });

})(angular, CRM.$, CRM._);
