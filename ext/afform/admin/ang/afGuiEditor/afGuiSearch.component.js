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
      $scope.calcFieldTitles = [];
      $scope.blockList = [];
      $scope.blockTitles = [];
      $scope.elementList = [];
      $scope.elementTitles = [];

      $scope.getField = afGui.getField;

      // Live results for the select2 of filter fields
      this.getFilterFields = function() {
        var fieldGroups = [],
          entities = getEntities();
        if (ctrl.display.settings.calc_fields && ctrl.display.settings.calc_fields.length) {
          fieldGroups.push({
            text: ts('Calculated Fields'),
            children: _.transform(ctrl.display.settings.calc_fields, function(fields, el) {
              fields.push({id: el.name, text: el.label, disabled: ctrl.fieldInUse(el.name)});
            }, [])
          });
        }
        _.each(entities, function(entity) {
          fieldGroups.push({
            text: entity.label,
            children: _.transform(entity.fields, function(fields, field) {
              fields.push({id: entity.prefix + field.name, text: entity.label + ' ' + field.label, disabled: ctrl.fieldInUse(entity.prefix + field.name)});
            }, [])
          });
        });
        return {results: fieldGroups};
      };

      this.buildPaletteLists = function() {
        var search = $scope.controls.fieldSearch ? $scope.controls.fieldSearch.toLowerCase() : null;
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
        var alias = fieldName.split('.')[0],
          entity;
        _.each(ctrl.display.settings['saved_search_id.api_params'].join, function(join) {
          var joinInfo = join[0].split(' AS ');
          if (alias === joinInfo[1]) {
            entity = joinInfo[0];
            return false;
          }
        });
        return entity || ctrl.display.settings['saved_search_id.api_entity'];
      };

      function fieldDefaults(field, prefix) {
        var tag = {
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
          if (!search || _.contains(field.label.toLowerCase(), search)) {
            $scope.calcFieldList.push(fieldDefaults(field, ''));
            $scope.calcFieldTitles.push(field.label);
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

      // Fetch all entities used in search (main entity + joins)
      function getEntities() {
        var
          mainEntity = afGui.getEntity(ctrl.display.settings['saved_search_id.api_entity']),
          entityCount = {},
          entities = [{
            name: mainEntity.entity,
            prefix: '',
            label: mainEntity.label,
            fields: mainEntity.fields
          }];

        // Increment count of entityName and return a suffix string if > 1
        function countEntity(entityName) {
          entityCount[entityName] = (entityCount[entityName] || 0) + 1;
          return entityCount[entityName] > 1 ? ' ' + entityCount[entityName] : '';
        }
        countEntity(mainEntity.entity);

        _.each(ctrl.display.settings['saved_search_id.api_params'].join, function(join) {
          const joinInfo = join[0].split(' AS ');
          const entity = afGui.getEntity(joinInfo[0]);
          const bridgeEntity = afGui.getEntity(join[2]);
          const defaultLabel = entity.label + countEntity(entity.entity);
          const formValues = ctrl.display.settings['saved_search_id.form_values'] || {};
          entities.push({
            name: entity.entity,
            prefix: joinInfo[1] + '.',
            label: (formValues && formValues.join && formValues.join[joinInfo[1]]) || defaultLabel,
            fields: entity.fields,
          });
          if (bridgeEntity) {
            entities.push({
              name: bridgeEntity.entity,
              prefix: joinInfo[1] + '.',
              label: bridgeEntity.label + countEntity(bridgeEntity.entity),
              fields: _.omit(bridgeEntity.fields, _.keys(entity.fields)),
            });
          }
        });

        return entities;
      }

      function buildFieldList(search) {
        $scope.fieldList.length = 0;
        var entities = getEntities();
        _.each(entities, function(entity) {
          $scope.fieldList.push({
            entityType: entity.name,
            label: ts('%1 Fields', {1: entity.label}),
            fields: filterFields(entity.fields, entity.prefix)
          });
        });

        function filterFields(fields, prefix) {
          return _.transform(fields, function(fieldList, field) {
            if (!search || _.contains(field.name, search) || _.contains(field.label.toLowerCase(), search)) {
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
            (!element.afform_type || _.contains(element.afform_type, 'search')) &&
            (!search || _.contains(name, search) || _.contains(element.title.toLowerCase(), search))
          ) {
            var node = _.cloneDeep(element.element);
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
        if (_.findIndex(ctrl.filters, {name: fieldName}) >= 0) {
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
        var fieldsInBlock = _.pluck(afGui.findRecursive(afGui.meta.blocks[block['#tag']].layout, {'#tag': 'af-field'}), 'name');
        return !!getElement(ctrl.display.fieldset['#children'], function(item) {
          return item['#tag'] === 'af-field' && _.includes(fieldsInBlock, item.name);
        });
      };

      // Return an item matching criteria
      // Recursively checks the form layout, including block directives
      function getElement(group, criteria, found) {
        if (!found) {
          found = {};
        }
        var match = _.find(group, criteria);
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
        var filter = ctrl.filters[index];
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
