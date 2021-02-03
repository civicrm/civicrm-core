// https://civicrm.org/licensing
(function(angular, $, _) {
  "use strict";

  angular.module('afGuiEditor').component('afGuiEditor', {
    templateUrl: '~/afGuiEditor/afGuiEditor.html',
    bindings: {
      data: '<',
      entity: '<',
      mode: '@'
    },
    controllerAs: 'editor',
    controller: function($scope, crmApi4, afGui, $parse, $timeout, $location) {
      var ts = $scope.ts = CRM.ts('afform');
      $scope.afform = null;
      $scope.saving = false;
      $scope.selectedEntityName = null;
      this.meta = afGui.meta;
      var editor = this;

      this.$onInit = function() {
        // Load the current form plus blocks & fields
        afGui.resetMeta();
        afGui.addMeta(this.data);
        initializeForm();
      };

      // Initialize the current form
      function initializeForm() {
        $scope.afform = editor.data.definition;
        if (!$scope.afform) {
          alert('Error: unknown form');
        }
        if (editor.mode === 'clone') {
          delete $scope.afform.name;
          delete $scope.afform.server_route;
          $scope.afform.is_dashlet = false;
          $scope.afform.title += ' ' + ts('(copy)');
        }
        $scope.canvasTab = 'layout';
        $scope.layoutHtml = '';
        editor.layout = {'#children': []};
        $scope.entities = {};

        if ($scope.afform.type === 'form') {
          editor.allowEntityConfig = true;
          editor.layout['#children'] = afGui.findRecursive($scope.afform.layout, {'#tag': 'af-form'})[0]['#children'];
          $scope.entities = afGui.findRecursive(editor.layout['#children'], {'#tag': 'af-entity'}, 'name');

          if (editor.mode === 'create') {
            editor.addEntity(editor.entity);
            editor.layout['#children'].push(afGui.meta.elements.submit.element);
          }
        }

        if ($scope.afform.type === 'block') {
          editor.layout['#children'] = $scope.afform.layout;
          editor.blockEntity = $scope.afform.join || $scope.afform.block;
          $scope.entities[editor.blockEntity] = {
            type: editor.blockEntity,
            name: editor.blockEntity,
            label: afGui.getEntity(editor.blockEntity).label
          };
        }

        if ($scope.afform.type === 'search') {
          editor.layout['#children'] = afGui.findRecursive($scope.afform.layout, {'af-fieldset': ''})[0]['#children'];

        }

        // Set changesSaved to true on initial load, false thereafter whenever changes are made to the model
        $scope.changesSaved = editor.mode === 'edit' ? 1 : false;
        $scope.$watch('afform', function () {
          $scope.changesSaved = $scope.changesSaved === 1;
        }, true);
      }

      $scope.updateLayoutHtml = function() {
        $scope.layoutHtml = '...Loading...';
        crmApi4('Afform', 'convert', {layout: $scope.afform.layout, from: 'deep', to: 'html', formatWhitespace: true})
          .then(function(r){
            $scope.layoutHtml = r[0].layout || '(Error)';
          })
          .catch(function(r){
            $scope.layoutHtml = '(Error)';
          });
      };

      this.addEntity = function(type, selectTab) {
        var meta = afGui.meta.entities[type],
          num = 1;
        // Give this new entity a unique name
        while (!!$scope.entities[type + num]) {
          num++;
        }
        $scope.entities[type + num] = _.assign($parse(meta.defaults)($scope), {
          '#tag': 'af-entity',
          type: meta.entity,
          name: type + num,
          label: meta.label + ' ' + num,
          loading: true,
        });

        function addToCanvas() {
          // Add this af-entity tag after the last existing one
          var pos = 1 + _.findLastIndex(editor.layout['#children'], {'#tag': 'af-entity'});
          editor.layout['#children'].splice(pos, 0, $scope.entities[type + num]);
          // Create a new af-fieldset container for the entity
          var fieldset = _.cloneDeep(afGui.meta.elements.fieldset.element);
          fieldset['af-fieldset'] = type + num;
          fieldset['#children'][0]['#children'][0]['#text'] = meta.label + ' ' + num;
          // Add boilerplate contents
          _.each(meta.boilerplate, function (tag) {
            fieldset['#children'].push(tag);
          });
          // Attempt to place the new af-fieldset after the last one on the form
          pos = 1 + _.findLastIndex(editor.layout['#children'], 'af-fieldset');
          if (pos) {
            editor.layout['#children'].splice(pos, 0, fieldset);
          } else {
            editor.layout['#children'].push(fieldset);
          }
          delete $scope.entities[type + num].loading;
          if (selectTab) {
            editor.selectEntity(type + num);
          }
        }

        if (meta.fields) {
          addToCanvas();
        } else {
          crmApi4('Afform', 'loadAdminData', {
            definition: {type: 'form'},
            entity: type
          }, 0).then(function(data) {
            afGui.addMeta(data);
            addToCanvas();
          });
        }
      };

      this.removeEntity = function(entityName) {
        delete $scope.entities[entityName];
        afGui.removeRecursive(editor.layout['#children'], {'#tag': 'af-entity', name: entityName});
        afGui.removeRecursive(editor.layout['#children'], {'af-fieldset': entityName});
        this.selectEntity(null);
      };

      this.selectEntity = function(entityName) {
        $scope.selectedEntityName = entityName;
      };

      this.getEntity = function(entityName) {
        return $scope.entities[entityName];
      };

      this.getSelectedEntityName = function() {
        return $scope.selectedEntityName;
      };

      this.getAfform = function() {
        return $scope.afform;
      };

      // Validates that a drag-n-drop action is allowed
      this.onDrop = function(event, ui) {
        var sort = ui.item.sortable;
        // Check if this is a callback for an item dropped into a different container
        // @see https://github.com/angular-ui/ui-sortable notes on canceling
        if (!sort.received && sort.source[0] !== sort.droptarget[0]) {
          var $source = $(sort.source[0]),
            $target = $(sort.droptarget[0]),
            $item = $(ui.item[0]);
          // Fields cannot be dropped outside their own entity
          if ($item.is('[af-gui-field]') || $item.has('[af-gui-field]').length) {
            if ($source.closest('[data-entity]').attr('data-entity') !== $target.closest('[data-entity]').attr('data-entity')) {
              return sort.cancel();
            }
          }
          // Entity-fieldsets cannot be dropped into other entity-fieldsets
          if ((sort.model['af-fieldset'] || $item.has('.af-gui-fieldset').length) && $target.closest('.af-gui-fieldset').length) {
            return sort.cancel();
          }
        }
      };

      $scope.save = function() {
        var afform = JSON.parse(angular.toJson($scope.afform));
        // This might be set to undefined by validation
        afform.server_route = afform.server_route || '';
        $scope.saving = $scope.changesSaved = true;
        crmApi4('Afform', 'save', {formatWhitespace: true, records: [afform]})
          .then(function (data) {
            $scope.saving = false;
            $scope.afform.name = data[0].name;
            if (editor.mode !== 'edit') {
              $location.url('/edit/' + data[0].name);
            }
          });
      };

      $scope.$watch('afform.title', function(newTitle, oldTitle) {
        if (typeof oldTitle === 'string') {
          _.each($scope.entities, function(entity) {
            if (entity.data && entity.data.source === oldTitle) {
              entity.data.source = newTitle;
            }
          });
        }
      });
    }
  });

})(angular, CRM.$, CRM._);
