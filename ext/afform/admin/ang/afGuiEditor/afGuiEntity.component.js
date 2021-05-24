// https://civicrm.org/licensing
(function(angular, $, _) {
  "use strict";

  angular.module('afGuiEditor').component('afGuiEntity', {
    templateUrl: '~/afGuiEditor/afGuiEntity.html',
    bindings: {
      entity: '<'
    },
    require: {editor: '^^afGuiEditor'},
    controller: function ($scope, $timeout, afGui) {
      var ts = $scope.ts = CRM.ts('org.civicrm.afform_admin');
      var ctrl = this;
      $scope.controls = {};
      $scope.fieldList = [];
      $scope.blockList = [];
      $scope.blockTitles = [];
      $scope.elementList = [];
      $scope.elementTitles = [];

      function getEntityType() {
        return (ctrl.entity.type === 'Contact' && ctrl.entity.data) ? ctrl.entity.data.contact_type || 'Contact' : ctrl.entity.type;
      }

      $scope.getMeta = function() {
        return afGui.meta.entities[getEntityType()];
      };

      $scope.getAdminTpl = function() {
        return $scope.getMeta().admin_tpl || '~/afGuiEditor/entityConfig/Generic.html';
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

      function buildFieldList(search) {
        $scope.fieldList.length = 0;
        $scope.fieldList.push({
          entityName: ctrl.entity.name,
          entityType: getEntityType(),
          label: ts('%1 Fields', {1: $scope.getMeta().label}),
          fields: filterFields($scope.getMeta().fields)
        });
        // Add fields for af-join blocks
        _.each(afGui.meta.entities, function(entity, entityName) {
          if (check(ctrl.editor.layout['#children'], {'af-join': entityName})) {
            $scope.fieldList.push({
              entityName: ctrl.entity.name + '-join-' + entityName,
              entityType: entityName,
              label: ts('%1 Fields', {1: entity.label}),
              fields: filterFields(entity.fields)
            });
          }
        });

        function filterFields(fields) {
          return _.transform(fields, function(fieldList, field) {
            if (!field.readonly &&
              (!search || _.contains(field.name, search) || _.contains(field.label.toLowerCase(), search))
            ) {
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
            (block.block === '*' || block.block === ctrl.entity.type || (ctrl.entity.type === 'Contact' && block.block === ctrl.entity.data.contact_type)) &&
            block.name !== ctrl.editor.getAfform().name
          ) {
            var item = {"#tag": block.join ? "div" : directive};
            if (block.join) {
              item['af-join'] = block.join;
              item['#children'] = [{"#tag": directive}];
            }
            if (block.repeat) {
              item['af-repeat'] = ts('Add');
              item.min = '1';
              if (typeof block.repeat === 'number') {
                item.max = '' + block.repeat;
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
          if (!search || _.contains(name, search) || _.contains(element.title.toLowerCase(), search)) {
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
      $scope.fieldInUse = function(fieldName) {
        var data = ctrl.entity.data || {};
        if (fieldName in data) {
          return true;
        }
        return check(ctrl.editor.layout['#children'], {'#tag': 'af-field', name: fieldName});
      };

      // Checks if fields in a block are already in use on the form.
      // Note that if a block contains no fields it can be used repeatedly, so this will always return false for those.
      $scope.blockInUse = function(block) {
        if (block['af-join']) {
          return check(ctrl.editor.layout['#children'], {'af-join': block['af-join']});
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
            if ((!item['af-fieldset'] || (item['af-fieldset'] === ctrl.entity.name)) && item['#children']) {
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

      this.$onInit = function() {
        // When a new block is saved, update the list
        this.meta = afGui.meta;
        $scope.$watchCollection('$ctrl.meta.blocks', function() {
          $scope.controls.fieldSearch = '';
          ctrl.buildPaletteLists();
        });

        $scope.$watch('controls.addValue', function(fieldName) {
          if (fieldName) {
            if (!ctrl.entity.data) {
              ctrl.entity.data = {};
            }
            ctrl.entity.data[fieldName] = '';
            $scope.controls.addValue = '';
          }
        });
      };
    }
  });

})(angular, CRM.$, CRM._);
