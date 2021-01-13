// https://civicrm.org/licensing
(function(angular, $, _) {
  "use strict";

  angular.module('afGuiEditor').component('afGuiEditor', {
    templateUrl: '~/afGuiEditor/afGuiEditor.html',
    bindings: {
      type: '<',
      name: '<'
    },
    controllerAs: 'editor',
    controller: function($scope, crmApi4, afGui, $parse, $timeout, $location) {
      var ts = $scope.ts = CRM.ts('afform');
      $scope.afform = null;
      $scope.saving = false;
      $scope.selectedEntityName = null;
      this.meta = afGui.meta;
      var editor = this;
      var newForm = {
        title: '',
        permission: 'access CiviCRM',
        type: 'form',
        layout: [{
          '#tag': 'af-form',
          ctrl: 'afform',
          '#children': []
        }]
      };

      this.$onInit = function() {
        // Fetch the current form plus all blocks
        afGui.initialize(editor.name)
          .then(initializeForm);
      };

      // Initialize the current form
      function initializeForm(afforms) {
        $scope.afform = _.findWhere(afforms, {name: editor.name});
        if (!$scope.afform) {
          $scope.afform = _.cloneDeep(newForm);
          if (editor.name) {
            alert('Error: unknown form "' + editor.name + '"');
          }
        }
        $scope.canvasTab = 'layout';
        $scope.layoutHtml = '';
        editor.layout = afGui.findRecursive($scope.afform.layout, {'#tag': 'af-form'})[0];
        $scope.entities = afGui.findRecursive(editor.layout['#children'], {'#tag': 'af-entity'}, 'name');

        if (!editor.name) {
          editor.addEntity('Individual');
          editor.layout['#children'].push(afGui.meta.elements.submit.element);
        }

        // Set changesSaved to true on initial load, false thereafter whenever changes are made to the model
        $scope.changesSaved = !editor.name ? false : 1;
        $scope.$watch('afform', function () {
          $scope.changesSaved = $scope.changesSaved === 1;
        }, true);
      }

      $scope.updateLayoutHtml = function() {
        $scope.layoutHtml = '...Loading...';
        crmApi4('Afform', 'convert', {layout: [editor.layout], from: 'deep', to: 'html', formatWhitespace: true})
          .then(function(r){
            $scope.layoutHtml = r[0].layout || '(Error)';
          })
          .catch(function(r){
            $scope.layoutHtml = '(Error)';
          });
      };

      this.addEntity = function(type) {
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
          label: meta.label + ' ' + num
        });
        // Add this af-entity tag after the last existing one
        var pos = 1 + _.findLastIndex(editor.layout['#children'], {'#tag': 'af-entity'});
        editor.layout['#children'].splice(pos, 0, $scope.entities[type + num]);
        // Create a new af-fieldset container for the entity
        var fieldset = _.cloneDeep(afGui.meta.elements.fieldset.element);
        fieldset['af-fieldset'] = type + num;
        fieldset['#children'][0]['#children'][0]['#text'] = meta.label + ' ' + num;
        // Add default contact name block
        if (meta.entity === 'Contact') {
          fieldset['#children'].push({'#tag': 'afblock-name-' + type.toLowerCase()});
        }
        // Attempt to place the new af-fieldset after the last one on the form
        pos = 1 + _.findLastIndex(editor.layout['#children'], 'af-fieldset');
        if (pos) {
          editor.layout['#children'].splice(pos, 0, fieldset);
        } else {
          editor.layout['#children'].push(fieldset);
        }
        return type + num;
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

      $scope.addEntity = function(entityType) {
        var entityName = editor.addEntity(entityType);
        editor.selectEntity(entityName);
      };

      $scope.save = function() {
        $scope.saving = $scope.changesSaved = true;
        crmApi4('Afform', 'save', {formatWhitespace: true, records: [JSON.parse(angular.toJson($scope.afform))]})
          .then(function (data) {
            $scope.saving = false;
            $scope.afform.name = data[0].name;
            $location.url('/edit/' + data[0].name);
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
