// https://civicrm.org/licensing
(function(angular, $, _) {
  "use strict";

  angular.module('afGuiEditor').component('afGuiSearch', {
    templateUrl: '~/afGuiEditor/afGuiSearch.html',
    bindings: {
      display: '<'
    },
    require: {editor: '^^afGuiEditor'},
    controller: function ($scope, $timeout, afGui) {
      var ts = $scope.ts = CRM.ts('org.civicrm.afform_admin');
      var ctrl = this;
      $scope.controls = {};
      $scope.fieldList = [];
      $scope.calcFieldList = [];
      $scope.blockList = [];
      $scope.blockTitles = [];
      $scope.elementList = [];
      $scope.elementTitles = [];

      $scope.getField = afGui.getField;

      this.buildPaletteLists = function() {
        var search = $scope.controls.fieldSearch ? $scope.controls.fieldSearch.toLowerCase() : null;
        buildCalcFieldList(search);
        buildFieldList(search);
        buildBlockList(search);
        buildElementList(search);
      };

      function buildCalcFieldList(search) {
        $scope.calcFieldList.length = 0;
        _.each(_.cloneDeep(ctrl.display.calc_fields), function(field) {
          if (!search || _.contains(field.defn.label.toLowerCase(), search)) {
            $scope.calcFieldList.push(field);
          }
        });
      }

      function buildBlockList(search) {
        $scope.blockList.length = 0;
        $scope.blockTitles.length = 0;
        _.each(afGui.meta.blocks, function(block, directive) {
          if (!search || _.contains(directive, search) || _.contains(block.name.toLowerCase(), search) || _.contains(block.title.toLowerCase(), search)) {
            var item = {"#tag": directive};
            $scope.blockList.push(item);
            $scope.blockTitles.push(block.title);
          }
        });
      }

      function buildFieldList(search) {
        $scope.fieldList.length = 0;
        var entity = afGui.getEntity(ctrl.display['saved_search_id.api_entity']),
          entityCount = {};
        entityCount[entity.entity] = 1;
        $scope.fieldList.push({
          entityType: entity.entity,
          label: ts('%1 Fields', {1: entity.label}),
          fields: filterFields(entity.fields)
        });

        _.each(ctrl.display['saved_search_id.api_params'].join, function(join) {
          var joinInfo = join[0].split(' AS '),
            entity = afGui.getEntity(joinInfo[0]),
            alias = joinInfo[1];
          entityCount[entity.entity] = (entityCount[entity.entity] || 0) + 1;
          $scope.fieldList.push({
            entityType: entity.entity,
            label: ts('%1 Fields', {1: entity.label + (entityCount[entity.entity] > 1 ? ' ' + entityCount[entity.entity] : '')}),
            fields: filterFields(entity.fields, alias)
          });
        });

        function filterFields(fields, prefix) {
          return _.transform(fields, function(fieldList, field) {
            if (!search || _.contains(field.name, search) || _.contains(field.label.toLowerCase(), search)) {
              fieldList.push(fieldDefaults(field, prefix));
            }
          }, []);
        }

        function fieldDefaults(field, prefix) {
          var tag = {
            "#tag": "af-field",
            name: (prefix ? prefix + '.' : '') + field.name
          };
          if (field.input_type === 'Select' || field.input_type === 'ChainSelect') {
            tag.defn = {input_attrs: {multiple: true}};
          } else if (field.input_type === 'Date') {
            tag.defn = {input_type: 'Select', search_range: true};
          } else if (field.options) {
            tag.defn = {input_type: 'Select', input_attrs: {multiple: true}};
          }
          return tag;
        }
      }

      function buildElementList(search) {
        $scope.elementList.length = 0;
        $scope.elementTitles.length = 0;
        _.each(afGui.meta.elements, function(element, name) {
          if (!search || _.contains(name, search) || _.contains(element.title.toLowerCase(), search)) {
            var node = _.cloneDeep(element.element);
            if (name === 'fieldset') {
              return;
            }
            $scope.elementList.push(node);
            $scope.elementTitles.push(element.title);
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
            // Recurse through everything
            if (item['#children']) {
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
      };
    }
  });

})(angular, CRM.$, CRM._);
