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
      $scope.searchDisplayListFilter = {};
      this.meta = afGui.meta;
      var editor = this,
        undoHistory = [],
        undoPosition = 0,
        undoAction = null,
        lastSaved,
        sortableOptions = {};

      // ngModelOptions to debounce input
      // Used to prevent cluttering the undo history with every keystroke
      this.debounceMode = {
        updateOn: 'default blur',
        debounce: {
          default: 2000,
          blur: 0
        }
      };

      // Above mode for use with getterSetter
      this.debounceWithGetterSetter = _.assign({getterSetter: true}, this.debounceMode);

      this.$onInit = function() {
        // Load the current form plus blocks & fields
        afGui.resetMeta();
        afGui.addMeta(this.data);
        initializeForm();

        $timeout(fixEditorHeight);
        $timeout(editor.adjustTabWidths);
        $(window)
          .off('.afGuiEditor')
          .on('resize.afGuiEditor', fixEditorHeight)
          .on('resize.afGuiEditor', editor.adjustTabWidths)
          .on('keyup.afGuiEditor', editor.onKeyup);

        // Warn of unsaved changes
        window.onbeforeunload = function(e) {
          if (!editor.isSaved()) {
            e.returnValue = ts("Form has not been saved.");
            return e.returnValue;
          }
        };
      };

      this.$onDestroy = function() {
        $(window).off('.afGuiEditor');
        window.onbeforeunload = null;
      };

      function setEditorLayout() {
        editor.layout = {};
        if (editor.getFormType() === 'form') {
          editor.layout['#children'] = afGui.findRecursive(editor.afform.layout, {'#tag': 'af-form'})[0]['#children'];
        }
        else {
          editor.layout['#children'] = editor.afform.layout;
        }
      }

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
        editor.afform.icon = editor.afform.icon || 'fa-list-alt';
        $scope.canvasTab = 'layout';
        $scope.layoutHtml = '';
        $scope.entities = {};
        setEditorLayout();
        setLastSaved();

        if (editor.afform.navigation) {
          loadNavigationMenu();
        }

        if (editor.getFormType() === 'form') {
          editor.allowEntityConfig = true;
          $scope.entities = _.mapValues(afGui.findRecursive(editor.layout['#children'], {'#tag': 'af-entity'}, 'name'), backfillEntityDefaults);

          if (editor.mode === 'create') {
            editor.addEntity(editor.entity);
            editor.afform.create_submission = true;
            editor.layout['#children'].push(afGui.meta.elements.submit.element);
          }
        }

        if (editor.getFormType() === 'block') {
          editor.blockEntity = editor.afform.join_entity || editor.afform.entity_type || '*';
          $scope.entities[editor.blockEntity] = backfillEntityDefaults({
            type: editor.blockEntity,
            name: editor.blockEntity,
            label: afGui.getEntity(editor.blockEntity).label
          });
        }

        else if (editor.getFormType() === 'search') {
          editor.searchDisplays = getSearchDisplaysOnForm();
        }

        // Initialize undo history
        undoAction = 'initialLoad';
        undoHistory = [{
          afform: _.cloneDeep(editor.afform),
          saved: editor.mode === 'edit',
          selectedEntityName: null
        }];
        $scope.$watch('editor.afform', function(newValue, oldValue) {
          if (!undoAction && newValue && oldValue) {
            // Clear "redo" history
            if (undoPosition) {
              undoHistory.splice(0, undoPosition);
              undoPosition = 0;
            }
            undoHistory.unshift({
              afform: _.cloneDeep(editor.afform),
              saved: false,
              selectedEntityName: $scope.selectedEntityName
            });
            // Trim to a total length of 20
            if (undoHistory.length > 20) {
              undoHistory.splice(20, undoHistory.length - 20);
            }
          }
          undoAction = null;
        }, true);
      }

      // Undo/redo keys (ctrl-z, ctrl-shift-z)
      this.onKeyup = function(e) {
        if (e.key === 'z' && e.ctrlKey && e.shiftKey) {
          editor.redo();
        }
        else if (e.key === 'z' && e.ctrlKey) {
          editor.undo();
        }
      };

      this.canUndo = function() {
        return !!undoHistory[undoPosition + 1];
      };

      this.canRedo = function() {
        return !!undoHistory[undoPosition - 1];
      };

      // Revert to a previous/next revision in the undo history
      function changeHistory(direction) {
        if (!undoHistory[undoPosition + direction]) {
          return;
        }
        undoPosition += direction;
        undoAction = 'change';
        editor.afform = _.cloneDeep(undoHistory[undoPosition].afform);
        setEditorLayout();
        $scope.canvasTab = 'layout';
        $scope.selectedEntityName = undoHistory[undoPosition].selectedEntityName;
      }

      this.undo = _.wrap(1, changeHistory);

      this.redo = _.wrap(-1, changeHistory);

      this.isSaved = function() {
        return undoHistory[undoPosition].saved;
      };

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
          if (meta.boilerplate !== false) {
            var fieldset = _.cloneDeep(afGui.meta.elements.fieldset.element);
            fieldset['af-fieldset'] = type + num;
            fieldset['af-title'] = meta.label + ' ' + num;
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
        if (entity.type === 'Contact' && entity.data && entity.data.contact_type) {
          return editor.meta.entities[entity.data.contact_type];
        }
        return editor.meta.entities[entity.type];
      };

      // Scroll an entity's first fieldset into view of the canvas
      this.scrollToEntity = function(entityName) {
        var $canvas = $('#afGuiEditor-canvas-body'),
          $entity = $('.af-gui-container-type-fieldset[data-entity="' + entityName + '"]').first(),
          scrollValue, maxScroll;
        if ($entity.length) {
          // Scrolltop value needed to place entity's fieldset at top of canvas
          scrollValue = $canvas.scrollTop() + ($entity.offset().top - $canvas.offset().top);
          // Maximum possible scrollTop (height minus contents height, adjusting for padding)
          maxScroll = $('#afGuiEditor-canvas-body > *').height() - $canvas.height() + 20;
          // Exceeding the maximum scrollTop breaks the animation so keep it under the limit
          $canvas.animate({scrollTop: scrollValue > maxScroll ? maxScroll : scrollValue}, 500);
        }
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
          _.each(editor.searchDisplays, function(searchDisplay) {
            delete searchDisplay.element.filters;
          });
        } else {
          editor.afform.contact_summary = 'block';
          _.each(editor.searchDisplays, function(searchDisplay) {
            var filterOptions = getSearchFilterOptions(searchDisplay.settings);
            if (filterOptions.length) {
              searchDisplay.element.filters = filterOptions[0].key;
            }
          });
        }
      };

      this.toggleNavigation = function() {
        if (editor.afform.navigation) {
          editor.afform.navigation = null;
        } else {
          loadNavigationMenu();
          editor.afform.navigation = {
            parent: null,
            label: editor.afform.title,
            weight: 0
          };
        }
      };

      function loadNavigationMenu() {
        if ('navigationMenu' in editor) {
          return;
        }
        editor.navigationMenu = null;
        var conditions = [
          ['domain_id', '=', 'current_domain'],
          ['name', '!=', 'Home']
        ];
        if (editor.afform.name) {
          conditions.push(['name', '!=', editor.afform.name]);
        }
        crmApi4('Navigation', 'get', {
          select: ['name', 'label', 'parent_id', 'icon'],
          where: conditions,
          orderBy: {weight: 'ASC'}
        }).then(function(items) {
          editor.navigationMenu = buildTree(items, null);
        });
      }

      function buildTree(items, parentId) {
        return _.transform(items, function(navigationMenu, item) {
          if (parentId === item.parent_id) {
            var children = buildTree(items, item.id),
              menuItem = {
                id: item.name,
                text: item.label,
                icon: item.icon
              };
            if (children.length) {
              menuItem.children = children;
            }
            navigationMenu.push(menuItem);
          }
        }, []);
      }

      // Collects all search displays currently on the form
      function getSearchDisplaysOnForm() {
        var searchFieldsets = afGui.findRecursive(editor.afform.layout, {'af-fieldset': ''});
        return _.transform(searchFieldsets, function(searchDisplays, fieldset) {
          var displayElement = afGui.findRecursive(fieldset['#children'], function(item) {
            return item['search-name'] && item['#tag'] && item['#tag'].indexOf('crm-search-display-') === 0;
          })[0];
          if (displayElement) {
            searchDisplays[displayElement['search-name'] + (displayElement['display-name'] ? '.' + displayElement['display-name'] : '')] = {
              element: displayElement,
              fieldset: fieldset,
              settings: afGui.getSearchDisplay(displayElement['search-name'], displayElement['display-name'])
            };
          }
        }, {});
      }

      // Load data for "Add search display" dropdown
      this.getSearchDisplaySelector = function() {
        // Reset search input in dropdown
        $scope.searchDisplayListFilter.label = '';
        // A value means it's alredy loaded. Null means it's loading.
        if (!editor.searchOptions && editor.searchOptions !== null) {
          editor.searchOptions = null;
          afGui.getAllSearchDisplays().then(function(links) {
            editor.searchOptions = links;
          });
        }
      };

      this.addSearchDisplay = function(display) {
        var searchName = display.key.split('.')[0];
        var displayName = display.key.split('.')[1] || '';
        var fieldset = {
          '#tag': 'div',
          'af-fieldset': '',
          'af-title': display.label,
          '#children': [
            {
              '#tag': display.tag,
              'search-name': searchName,
              'display-name': displayName,
            }
          ]
        };
        var meta = {
          fieldset: fieldset,
          element: fieldset['#children'][0],
          settings: afGui.getSearchDisplay(searchName, displayName),
        };
        editor.searchDisplays[display.key] = meta;

        function addToCanvas() {
          editor.layout['#children'].push(fieldset);
          editor.selectEntity(display.key);
        }
        if (meta.settings) {
          addToCanvas();
        } else {
          $timeout(editor.adjustTabWidths);
          crmApi4('Afform', 'loadAdminData', {
            definition: {type: 'search'},
            entity: display.key
          }, 0).then(function(data) {
            afGui.addMeta(data);
            meta.settings = afGui.getSearchDisplay(searchName, displayName);
            addToCanvas();
          });
        }
      };

      // Triggered by afGuiContainer.removeElement
      this.onRemoveElement = function() {
        // Keep this.searchDisplays in-sync when deleteing stuff from the form
        if (editor.getFormType() === 'search') {
          var current = getSearchDisplaysOnForm();
          _.each(_.keys(editor.searchDisplays), function(key) {
            if (!(key in current)) {
              delete editor.searchDisplays[key];
              editor.selectEntity(null);
            }
          });
        }
      };

      // This function used to be needed to build a menu of available contact_id fields
      // but is no longer used for that and is overkill for what it does now.
      function getSearchFilterOptions(searchDisplay) {
        var
          entityCount = {},
          options = [];

        addFields(searchDisplay['saved_search_id.api_entity'], '');

        _.each(searchDisplay['saved_search_id.api_params'].join, function(join) {
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
            appendTo: '#afGuiEditor-canvas-body > af-gui-container',
            containment: '#afGuiEditor-canvas-body',
            update: editor.onDrop,
            items: '> div:not(.disabled)',
            connectWith: '#afGuiEditor-canvas ' + (entityName ? '[data-entity="' + entityName + '"] > ' : '') + '[ui-sortable]',
            placeholder: 'af-gui-dropzone',
            scrollSpeed: 8,
            helper: function(e, $el) {
              // Prevent draggable item from being too large for the drop zones.
              return $el.clone().css({width: '50px', height: '20px'});
            }
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
        $scope.saving = true;
        crmApi4('Afform', 'save', {formatWhitespace: true, records: [afform]})
          .then(function (data) {
            $scope.saving = false;
            // When saving a new form for the first time
            if (!editor.afform.name) {
              undoAction = 'save';
              editor.afform.name = data[0].name;
            }
            // Update undo history - mark current snapshot as "saved"
            _.each(undoHistory, function(snapshot, index) {
              snapshot.saved = index === undoPosition;
              snapshot.afform.name = data[0].name;
            });
            if (!angular.equals(afform.navigation, lastSaved.navigation) ||
              (afform.server_route !== lastSaved.server_route && afform.navigation) ||
              (afform.icon !== lastSaved.icon && afform.navigation)
            ) {
              refreshMenubar();
            }
            setLastSaved();
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

      // Sets last-saved form metadata (used to determine if the menubar needs refresh)
      function setLastSaved() {
        lastSaved = JSON.parse(angular.toJson(editor.afform));
        delete lastSaved.layout;
      }

      // Force-refresh the menubar to instantly display the afform menu item
      function refreshMenubar() {
        CRM.menubar.destroy();
        CRM.menubar.cacheCode = Math.random();
        CRM.menubar.initialize();
      }

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
