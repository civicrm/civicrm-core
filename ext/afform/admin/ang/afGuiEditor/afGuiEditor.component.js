// https://civicrm.org/licensing
(function(angular, $, _) {
  "use strict";

  function backfillEntityDefaults(entity) {
    // These fields did not exist in prior versions. In absence of explicit, these are the values inferred by runtime/server-side.
    // We cannot currently backfill schema in the upgrade, so this is the next best opportunity.
    if (entity.actions === undefined) entity.actions = {create: true, update: true};
    if (entity.security === undefined) entity.security = 'RBAC';
    return entity;
  }

  angular.module('afGuiEditor').component('afGuiEditor', {
    templateUrl: '~/afGuiEditor/afGuiEditor.html',
    bindings: {
      data: '<',
      entity: '<',
      mode: '@'
    },
    controllerAs: 'editor',
    controller: function($scope, crmApi4, afGui, $parse, $timeout, $location) {
      var ts = $scope.ts = CRM.ts('org.civicrm.afform_admin');

      this.afform = null;
      $scope.saving = false;
      $scope.selectedEntityName = null;
      this.meta = afGui.meta;
      var editor = this,
        sortableOptions = {};

      this.$onInit = function() {
        // Load the current form plus blocks & fields
        afGui.resetMeta();
        afGui.addMeta(this.data);
        initializeForm();

        $timeout(fixEditorHeight);
        $timeout(editor.adjustTabWidths);
        $(window)
          .on('resize.afGuiEditor', fixEditorHeight)
          .on('resize.afGuiEditor', editor.adjustTabWidths);
      };

      this.$onDestroy = function() {
        $(window).off('.afGuiEditor');
      };

      // Initialize the current form
      function initializeForm() {
        editor.afform = editor.data.definition;
        if (!editor.afform) {
          alert('Error: unknown form');
        }
        if (editor.mode === 'clone') {
          delete editor.afform.name;
          delete editor.afform.server_route;
          editor.afform.is_dashlet = false;
          editor.afform.title += ' ' + ts('(copy)');
        }
        $scope.canvasTab = 'layout';
        $scope.layoutHtml = '';
        editor.layout = {'#children': []};
        $scope.entities = {};

        if (editor.getFormType() === 'form') {
          editor.allowEntityConfig = true;
          editor.layout['#children'] = afGui.findRecursive(editor.afform.layout, {'#tag': 'af-form'})[0]['#children'];
          $scope.entities = _.mapValues(afGui.findRecursive(editor.layout['#children'], {'#tag': 'af-entity'}, 'name'), backfillEntityDefaults);

          if (editor.mode === 'create') {
            editor.addEntity(editor.entity);
            editor.afform.create_submission = true;
            editor.layout['#children'].push(afGui.meta.elements.submit.element);
          }
        }

        else if (editor.getFormType() === 'block') {
          editor.layout['#children'] = editor.afform.layout;
          editor.blockEntity = editor.afform.join_entity || editor.afform.entity_type;
          $scope.entities[editor.blockEntity] = backfillEntityDefaults({
            type: editor.blockEntity,
            name: editor.blockEntity,
            label: afGui.getEntity(editor.blockEntity).label
          });
        }

        else if (editor.getFormType() === 'search') {
          editor.layout['#children'] = afGui.findRecursive(editor.afform.layout, {'af-fieldset': ''})[0]['#children'];
          editor.searchDisplay = afGui.findRecursive(editor.layout['#children'], function(item) {
            return item['#tag'] && item['#tag'].indexOf('crm-search-display-') === 0;
          })[0];
          editor.searchFilters = getSearchFilterOptions();
        }

        // Set changesSaved to true on initial load, false thereafter whenever changes are made to the model
        $scope.changesSaved = editor.mode === 'edit' ? 1 : false;
        $scope.$watch('editor.afform', function () {
          $scope.changesSaved = $scope.changesSaved === 1;
        }, true);
      }

      this.getFormType = function() {
        return editor.afform.type;
      };

      $scope.updateLayoutHtml = function() {
        $scope.layoutHtml = '...Loading...';
        crmApi4('Afform', 'convert', {layout: editor.afform.layout, from: 'deep', to: 'html', formatWhitespace: true})
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
        $scope.entities[type + num] = backfillEntityDefaults(_.assign($parse(meta.defaults)(editor), {
          '#tag': 'af-entity',
          type: meta.entity,
          name: type + num,
          label: meta.label + ' ' + num,
          loading: true,
        }));

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
            $timeout(function() {
              editor.scrollToEntity(type + num);
            });
          }
          $timeout(editor.adjustTabWidths);
        }

        if (meta.fields) {
          addToCanvas();
        } else {
          $timeout(editor.adjustTabWidths);
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
        $timeout(editor.adjustTabWidths);
      };

      this.getEntity = function(entityName) {
        return $scope.entities[entityName];
      };

      this.getSelectedEntityName = function() {
        return $scope.selectedEntityName;
      };

      this.getEntityDefn = function(entity) {
        if (entity.type === 'Contact' && entity.data.contact_type) {
          return editor.meta.entities[entity.data.contact_type];
        }
        return editor.meta.entities[entity.type];
      };

      // Scroll an entity's first fieldset into view of the canvas
      this.scrollToEntity = function(entityName) {
        var $canvas = $('#afGuiEditor-canvas-body'),
          $entity = $('.af-gui-container-type-fieldset[data-entity="' + entityName + '"]').first(),
          // Scrolltop value needed to place entity's fieldset at top of canvas
          scrollValue = $canvas.scrollTop() + ($entity.offset().top - $canvas.offset().top),
          // Maximum possible scrollTop (height minus contents height, adjusting for padding)
          maxScroll = $('#afGuiEditor-canvas-body > *').height() - $canvas.height() + 20;
        // Exceeding the maximum scrollTop breaks the animation so keep it under the limit
        $canvas.animate({scrollTop: scrollValue > maxScroll ? maxScroll : scrollValue}, 500);
      };

      this.getAfform = function() {
        return editor.afform;
      };

      this.getEntities = function(filter) {
        return filter ? _.filter($scope.entities, filter) : _.toArray($scope.entities);
      };

      this.toggleContactSummary = function() {
        if (editor.afform.contact_summary) {
          editor.afform.contact_summary = false;
          if (editor.afform.type === 'search') {
            delete editor.searchDisplay.filters;
          }
        } else {
          editor.afform.contact_summary = 'block';
          if (editor.afform.type === 'search') {
            editor.searchDisplay.filters = editor.searchFilters[0].key;
          }
        }
      };

      function getSearchFilterOptions() {
        var searchDisplay = editor.meta.searchDisplays[editor.searchDisplay['search-name'] + '.' + editor.searchDisplay['display-name']],
          entityCount = {},
          options = [];

        addFields(searchDisplay['saved_search.api_entity'], '');

        _.each(searchDisplay['saved_search.api_params'].join, function(join) {
          var joinInfo = join[0].split(' AS ');
          addFields(joinInfo[0], joinInfo[1] + '.');
        });

        function addFields(entityName, prefix) {
          var entity = afGui.getEntity(entityName);
          entityCount[entity.entity] = (entityCount[entity.entity] || 0) + 1;
          var count = (entityCount[entity.entity] > 1 ? ' ' + entityCount[entity.entity] : '');
          if (entityName === 'Contact') {
            options.push({
              key: "{'" + prefix + "id': options.contact_id}",
              label: entity.label + count
            });
          } else {
            _.each(entity.fields, function(field) {
              if (field.fk_entity === 'Contact') {
                options.push({
                  key: "{'" + prefix + field.name + "': options.contact_id}",
                  label: entity.label + count + ' ' + field.label
                });
              }
            });
          }
        }
        return options;
      }

      this.getLink = function() {
        if (editor.afform.server_route) {
          return CRM.url(editor.afform.server_route, null, editor.afform.is_public ? 'front' : 'back');
        }
      };

      // Options for ui-sortable in field palette
      this.getSortableOptions = function(entityName) {
        if (!sortableOptions[entityName + '']) {
          sortableOptions[entityName + ''] = {
            helper: 'clone',
            appendTo: '#afGuiEditor-canvas-body > af-gui-container',
            containment: '#afGuiEditor-canvas-body',
            update: editor.onDrop,
            items: '> div:not(.disabled)',
            connectWith: '#afGuiEditor-canvas ' + (entityName ? '[data-entity="' + entityName + '"] > ' : '') + '[ui-sortable]',
            placeholder: 'af-gui-dropzone',
            tolerance: 'pointer',
            scrollSpeed: 8
          };
        }
        return sortableOptions[entityName + ''];
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
          if ($item.find('af-gui-field').length) {
            if ($source.closest('[data-entity]').attr('data-entity') !== $target.closest('[data-entity]').attr('data-entity')) {
              return sort.cancel();
            }
          }
          // Entity-fieldsets cannot be dropped into other entity-fieldsets
          if ((sort.model['af-fieldset'] || $item.find('.af-gui-fieldset').length) && $target.closest('.af-gui-fieldset').length) {
            return sort.cancel();
          }
        }
      };

      $scope.save = function() {
        var afform = JSON.parse(angular.toJson(editor.afform));
        // This might be set to undefined by validation
        afform.server_route = afform.server_route || '';
        $scope.saving = $scope.changesSaved = true;
        crmApi4('Afform', 'save', {formatWhitespace: true, records: [afform]})
          .then(function (data) {
            $scope.saving = false;
            editor.afform.name = data[0].name;
            if (editor.mode !== 'edit') {
              $location.url('/edit/' + data[0].name);
            }
          });
      };

      $scope.$watch('editor.afform.title', function(newTitle, oldTitle) {
        if (typeof oldTitle === 'string') {
          _.each($scope.entities, function(entity) {
            if (entity.data && 'source' in entity.data && (entity.data.source || '') === oldTitle) {
              entity.data.source = newTitle;
            }
          });
        }
      });

      // Force editor panels to a fixed height, to avoid palette scrolling offscreen
      function fixEditorHeight() {
        var height = $(window).height() - $('#afGuiEditor').offset().top;
        $('#afGuiEditor').height(Math.floor(height));
      }

      // Compress tabs on small screens
      this.adjustTabWidths = function() {
        $('#afGuiEditor .panel-heading ul.nav-tabs li.active').css('max-width', '');
        $('#afGuiEditor .panel-heading ul.nav-tabs').each(function() {
          var remainingSpace = Math.floor($(this).width()) - 1,
            inactiveTabs = $(this).children('li.fluid-width-tab').not('.active');
          $(this).children('.active,:not(.fluid-width-tab)').each(function() {
            remainingSpace -= $(this).width();
          });
          if (inactiveTabs.length) {
            inactiveTabs.css('max-width', Math.floor(remainingSpace / inactiveTabs.length) + 'px');
          }
        });
      };
    }
  });

})(angular, CRM.$, CRM._);
