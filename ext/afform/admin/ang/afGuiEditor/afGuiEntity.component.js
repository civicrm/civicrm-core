// https://civicrm.org/licensing
(function(angular, $, _) {
  "use strict";

  angular.module('afGuiEditor').component('afGuiEntity', {
    templateUrl: '~/afGuiEditor/afGuiEntity.html',
    bindings: {
      entity: '<'
    },
    require: {editor: '^^afGuiEditor'},
    controller: function ($scope, $timeout, afGui, formatForSelect2) {
      var ts = $scope.ts = CRM.ts('org.civicrm.afform_admin');
      var ctrl = this;
      $scope.controls = {};
      $scope.fieldList = [];
      $scope.blockList = [];
      $scope.blockTitles = [];
      $scope.elementList = [];
      $scope.elementTitles = [];

      this.getEntityType = function() {
        return ctrl.entity.type;
      };

      $scope.getMeta = function() {
        return afGui.meta.entities[ctrl.getEntityType()];
      };

      $scope.getField = afGui.getField;

      $scope.valuesFields = function() {
        var fields = _.transform($scope.getMeta().fields, function(fields, field) {
          fields.push({id: field.name, text: field.label, disabled: $scope.fieldInUse(field.name)});
        }, []);
        return {results: fields};
      };

      $scope.removeValue = function(entity, fieldName) {
        delete entity.data[fieldName];
      };

      this.buildPaletteLists = function() {
        var search = $scope.controls.fieldSearch ? $scope.controls.fieldSearch.toLowerCase() : null;
        buildFieldList(search);
        buildBlockList(search);
        buildElementList(search);
      };

      this.getOptionsTpl = function() {
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
        _.each(afGui.meta.entities, function(entity, entityName) {
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
          return _.transform(fields, function(fieldList, field) {
            if (!search || _.contains(field.name, search) || _.contains(field.label.toLowerCase(), search)) {
              fieldList.push(fieldDefaults(field));
            }
          }, []);
        }

        function fieldDefaults(field) {
          var tag = {
            "#tag": "af-field",
            name: field.name
          };
          return tag;
        }
      }

      function buildBlockList(search) {
        $scope.blockList.length = 0;
        $scope.blockTitles.length = 0;
        _.each(afGui.meta.blocks, function(block, directive) {
          if ((!search || _.contains(directive, search) || _.contains(block.name.toLowerCase(), search) || _.contains(block.title.toLowerCase(), search)) &&
            // A block of type "*" applies to everything. A block of type "Contact" also applies to "Individual", "Organization" & "Household".
            (block.entity_type === '*' || block.entity_type === ctrl.entity.type || (block.entity_type === 'Contact' && ['Individual', 'Household', 'Organization'].includes(ctrl.entity.type))) &&
            // Prevent recursion
            block.name !== ctrl.editor.getAfform().name
          ) {
            var item = {"#tag": block.join_entity ? "div" : directive};
            if (block.join_entity) {
              var joinEntity = afGui.getEntity(block.join_entity);
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
        _.each(afGui.meta.elements, function(element, name) {
          if (
            (!element.afform_type || _.contains(element.afform_type, 'form')) &&
            (!search || _.contains(name, search) || _.contains(element.title.toLowerCase(), search))) {
            var node = _.cloneDeep(element.element);
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
      $scope.buildPaletteLists = function() {
        $timeout(function() {
          $scope.$apply(function() {
            ctrl.buildPaletteLists();
          });
        });
      };

      // Checks if a field is on the form or set as a value
      $scope.fieldInUse = function(fieldName, joinEntity) {
        var data = ctrl.entity.data || {};
        if (!joinEntity) {
          return (fieldName in data) || check(ctrl.editor.layout['#children'], {'#tag': 'af-field', name: fieldName});
        }
        // Joins might support multiple instances per entity; first fetch them all
        let afJoinContainers = afGui.getFormElements(ctrl.editor.layout['#children'], {'af-join': joinEntity}, (item) => {
          return item['af-join'] || (item['af-fieldset'] && item['af-fieldset'] !== ctrl.entity.name);
        });
        // Check if ALL af-join containers are using the field
        let inUse = true;
        afJoinContainers.forEach(function(container) {
          if (inUse && !check(container['#children'], {'#tag': 'af-field', name: fieldName})) {
            inUse = false;
          }
        });
        return inUse;
      };

      // Checks if fields in a block are already in use on the form.
      // Note that if a block contains no fields it can be used repeatedly, so this will always return false for those.
      $scope.blockInUse = function(block) {
        if (block['af-join']) {
          return check(ctrl.editor.layout['#children'], (item) => item['af-join'] === block['af-join'] && !(item.data && item.data.location_type_id));
        }
        var fieldsInBlock = _.pluck(afGui.findRecursive(afGui.meta.blocks[block['#tag']].layout, {'#tag': 'af-field'}), 'name');
        return check(ctrl.editor.layout['#children'], function(item) {
          return item['#tag'] === 'af-field' && _.includes(fieldsInBlock, item.name);
        });
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
        _.each(group, function(item) {
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

        ctrl.behaviors = _.transform(CRM.afGuiEditor.behaviors[ctrl.getEntityType()], function(behaviors, behavior) {
          var item = _.cloneDeep(behavior);
          item.options = formatForSelect2(item.modes, 'name', 'label', ['description', 'icon']);
          behaviors.push(item);
        }, []);
      };
    }
  });

})(angular, CRM.$, CRM._);
