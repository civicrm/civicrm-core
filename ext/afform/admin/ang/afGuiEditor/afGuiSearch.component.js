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
      const ts = $scope.ts = CRM.ts('org.civicrm.afform_admin');
      const ctrl = this;
      $scope.controls = {};
      $scope.fieldList = [];
      $scope.calcFieldList = [];
      $scope.calcFieldTitles = [];
      $scope.blockList = [];
      $scope.blockTitles = [];
      $scope.elementList = [];
      $scope.elementTitles = [];

      $scope.getField = afGui.getField;

      // Live results for the select2 of filter fields
      this.getFilterFields = function() {
        return afGui.getSearchDisplayFields(ctrl.display.settings, ctrl.fieldInUse);
      };

      this.buildPaletteLists = function() {
        const search = $scope.controls.fieldSearch ? $scope.controls.fieldSearch.toLowerCase() : null;
        buildCalcFieldList(search);
        buildFieldList(search);
        buildBlockList(search);
        buildElementList(search);
      };

      // Gets the name of the entity a field belongs to
      this.getFieldEntity = function(fieldName) {
        if (fieldName.indexOf('.') < 0) {
          return ctrl.display.settings['saved_search_id.api_entity'];
        }
        let alias = fieldName.split('.')[0],
          entity;
        _.each(ctrl.display.settings['saved_search_id.api_params'].join, function(join) {
          const joinInfo = join[0].split(' AS ');
          if (alias === joinInfo[1]) {
            entity = joinInfo[0];
            return false;
          }
        });
        return entity || ctrl.display.settings['saved_search_id.api_entity'];
      };

      function fieldDefaults(field, prefix) {
        const tag = {
          "#tag": "af-field",
          name: prefix + field.name
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

      function buildCalcFieldList(search) {
        $scope.calcFieldList.length = 0;
        $scope.calcFieldTitles.length = 0;
        _.each(_.cloneDeep(ctrl.display.settings.calc_fields), function(field) {
          if (!search || field.label.toLowerCase().includes(search)) {
            $scope.calcFieldList.push(fieldDefaults(field, ''));
            $scope.calcFieldTitles.push(field.label);
          }
        });
      }

      function buildBlockList(search) {
        $scope.blockList.length = 0;
        $scope.blockTitles.length = 0;
        _.each(afGui.meta.blocks, function(block, directive) {
          if (!search ||
            directive.includes(search) ||
            block.name.toLowerCase().includes(search) ||
            block.title.toLowerCase().includes(search)
          ) {
            const item = {"#tag": directive};
            $scope.blockList.push(item);
            $scope.blockTitles.push(block.title);
          }
        });
      }

      function buildFieldList(search) {
        $scope.fieldList.length = 0;
        const entities = afGui.getSearchDisplayEntities(ctrl.display.settings);
        entities.forEach((entity) => {
          $scope.fieldList.push({
            entityType: entity.name,
            label: ts('%1 Fields', {1: entity.label}),
            fields: filterFields(entity.fields, entity.prefix)
          });
        });

        function filterFields(fields, prefix) {
          return _.transform(fields, function(fieldList, field) {
            if (!search ||
              field.name.includes(search) ||
              field.label.toLowerCase().includes(search)
            ) {
              fieldList.push(fieldDefaults(field, prefix));
            }
          }, []);
        }
      }

      function buildElementList(search) {
        $scope.elementList.length = 0;
        $scope.elementTitles.length = 0;
        _.each(afGui.meta.elements, function(element, name) {
          if (
            (!element.afform_type || element.afform_type.includes('search')) &&
            (!search || name.includes(search) || element.title.toLowerCase().includes(search))
          ) {
            const node = _.cloneDeep(element.element);
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

      // Checks if a field is on the form or set as a filter
      this.fieldInUse = function(fieldName) {
        if (ctrl.filters.some(filter => filter.name === fieldName)) {
          return true;
        }
        return !!getElement(ctrl.display.fieldset['#children'], {'#tag': 'af-field', name: fieldName});
      };

      // Checks if fields in a block are already in use on the form.
      // Note that if a block contains no fields it can be used repeatedly, so this will always return false for those.
      $scope.blockInUse = function(block) {
        if (block['af-join']) {
          return !!getElement(ctrl.display.fieldset['#children'], {'af-join': block['af-join']});
        }
        const fieldsInBlock = _.pluck(afGui.findRecursive(afGui.meta.blocks[block['#tag']].layout, {'#tag': 'af-field'}), 'name');
        return !!getElement(ctrl.display.fieldset['#children'], function(item) {
          return item['#tag'] === 'af-field' && fieldsInBlock.includes(item.name);
        });
      };

      // Return an item matching criteria
      // Recursively checks the form layout, including block directives
      function getElement(group, criteria, found) {
        if (!found) {
          found = {};
        }
        const match = _.find(group, criteria);
        if (match) {
          found.match = match;
          return match;
        }
        _.each(group, function(item) {
          if (found.match) {
            return false;
          }
          if (_.isPlainObject(item)) {
            // Recurse through everything
            if (item['#children']) {
              getElement(item['#children'], criteria, found);
            }
            // Recurse into block directives
            else if (item['#tag'] && item['#tag'] in afGui.meta.blocks) {
              getElement(afGui.meta.blocks[item['#tag']].layout, criteria, found);
            }
          }
        });
        return found.match;
      }

      // Append a search filter
      this.addFilter = function(fieldName) {
        ctrl.filters.push({
          name: fieldName,
          value: fieldName,
          mode: 'routeParams'
        });
      };

      // Respond to changing a filter field name
      this.onChangeFilter = function(index) {
        const filter = ctrl.filters[index];
        // Clear filter
        if (!filter.name) {
          ctrl.filters.splice(index, 1);
        } else if (filter.mode === 'routeParams') {
          // Set default value for routeParams
          filter.value = filter.name;
        }
      };

      this.toggleStoreValues = function() {
        if (this.display.fieldset['store-values']) {
          delete this.display.fieldset['store-values'];
        } else {
          this.display.fieldset['store-values'] = '1';
        }
      };

      // Update crm-search-display element filters
      function writeFilters() {
        const filterString = afGui.stringifyDisplayFilters(ctrl.filters);
        if (filterString) {
          ctrl.display.element.filters = filterString;
        } else {
          delete ctrl.display.element.filters;
        }
      }

      this.$onInit = function() {
        this.meta = afGui.meta;
        this.filters = afGui.parseDisplayFilters(ctrl.display.element.filters);
        $scope.$watch('$ctrl.filters', writeFilters, true);
        // When a new block is saved, update the list
        $scope.$watchCollection('$ctrl.meta.blocks', function() {
          $scope.controls.fieldSearch = '';
          ctrl.buildPaletteLists();
        });
      };
    }
  });

})(angular, CRM.$, CRM._);
