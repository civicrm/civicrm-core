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
      var ts = $scope.ts = CRM.ts();
      var ctrl = this;
      $scope.controls = {};
      $scope.fieldList = [];
      $scope.elementList = [];
      $scope.elementTitles = [];

      $scope.getField = afGui.getField;

      function buildPaletteLists() {
        var search = $scope.controls.fieldSearch ? $scope.controls.fieldSearch.toLowerCase() : null;
        buildFieldList(search);
        buildElementList(search);
      }

      function buildFieldList(search) {
        $scope.fieldList.length = 0;
        var entity = afGui.getEntity(ctrl.display['saved_search.api_entity']),
          entityCount = {};
        entityCount[entity.entity] = 1;
        $scope.fieldList.push({
          entityType: entity.entity,
          label: ts('%1 Fields', {1: entity.label}),
          fields: filterFields(entity.fields)
        });

        _.each(ctrl.display['saved_search.api_params'].join, function(join) {
          var joinInfo = join[0].split(' AS '),
            entity = afGui.getEntity(joinInfo[0]),
            alias = joinInfo[1];
          entityCount[entity.entity] = entityCount[entity.entity] ? entityCount[entity.entity] + 1 : 1;
          $scope.fieldList.push({
            entityType: entity.entity,
            label: ts('%1 Fields', {1: entity.label + (entityCount[entity.entity] > 1 ? ' ' + entityCount[entity.entity] : '')}),
            fields: filterFields(entity.fields, alias)
          });
        });

        function filterFields(fields, prefix) {
          return _.transform(fields, function(fieldList, field) {
            if (!search || _.contains(field.name, search) || _.contains(field.label.toLowerCase(), search)) {
              fieldList.push({
                "#tag": "af-field",
                name: (prefix ? prefix + '.' : '') + field.name
              });
            }
          }, []);
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

      $scope.clearSearch = function() {
        $scope.controls.fieldSearch = '';
      };

      // This gets called from jquery-ui so we have to manually apply changes to scope
      $scope.buildPaletteLists = function() {
        $timeout(function() {
          $scope.$apply(function() {
            buildPaletteLists();
          });
        });
      };

      // Checks if a field is on the form or set as a value
      $scope.fieldInUse = function(fieldName) {
        return check(ctrl.editor.layout['#children'], {'#tag': 'af-field', name: fieldName});
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

      $scope.$watch('controls.fieldSearch', buildPaletteLists);
    }
  });

})(angular, CRM.$, CRM._);
