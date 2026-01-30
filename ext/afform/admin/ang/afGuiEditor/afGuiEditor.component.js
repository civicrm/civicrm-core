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
    controller: function($scope, crmApi4, crmUiHelp, afGui, $parse, $timeout, $location, $route, $rootScope, formatForSelect2) {
      const ts = $scope.ts = CRM.ts('org.civicrm.afform_admin');
      $scope.hs = crmUiHelp({file: 'CRM/AfformAdmin/afformBuilder'});

      this.afform = null;
      $scope.saving = false;
      $scope.selectedEntityName = null;
      $scope.searchDisplayListFilter = {};
      this.meta = afGui.meta;
      const editor = this;
      let undoHistory = [];
      let undoPosition = 0;
      let undoAction = null;
      let lastSaved = {};
      const sortableOptions = {};
      this.afformTags = formatForSelect2(this.meta.afform_fields.tags.options || [], 'id', 'label', ['description', 'color']);

      // ngModelOptions to debounce input
      // Used to prevent cluttering the undo history with every keystroke
      this.debounceMode = {
        updateOn: 'default blur',
        debounce: {
          default: 2000,
          blur: 0
        }
      };

      this.securityModes = [
        {id: 'RBAC', icon: 'fa-user', text: ts('User-Based'), description: ts('Inherit permissions based on the current user or role')},
        {id: 'FBAC', icon: 'fa-file-text', text: ts('Form-Based'), description: ts('Allow access to any fields listed on the form')},
      ];

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
          delete editor.afform.navigation;
          editor.afform.title += ' ' + ts('(copy)');
        }
        editor.afform.icon = editor.afform.icon || 'fa-list-alt';
        editor.afform.placement = editor.afform.placement || [];
        // An empty object gets miscast by json_encode as [].
        // FIXME: Maybe the Afform.get api ought to return empty arrays as NULL to avoid this problem.
        if (!editor.afform.placement_filters || Array.isArray(editor.afform.placement_filters)) {
          editor.afform.placement_filters = {};
        }
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
            editor.afform.submit_enabled = true;
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

        editor.afform.permission_operator = editor.afform.permission_operator || 'AND';
        // set redirect to url as default if not set
        if (!editor.afform.confirmation_type && editor.meta.afform_fields.confirmation_type.options.length > 0) {
          editor.afform.confirmation_type = editor.meta.afform_fields.confirmation_type.options[0].id;
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
        const meta = afGui.meta.entities[type];
        let num = 1;
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
          // Set default mode of behaviors
          if (afGui.meta.behaviors[meta.entity]) {
            afGui.meta.behaviors[meta.entity].forEach(behavior => {
              if (behavior.default_mode) {
                $scope.entities[type + num][behavior.key] = behavior.default_mode;
              }
            });
          }
          // Add this af-entity tag after the last existing one
          let pos = 1 + _.findLastIndex(editor.layout['#children'], {'#tag': 'af-entity'});
          editor.layout['#children'].splice(pos, 0, $scope.entities[type + num]);
          // Create a new af-fieldset container for the entity
          if (meta.boilerplate !== false) {
            const fieldset = _.cloneDeep(afGui.meta.elements.fieldset.element);
            fieldset['af-fieldset'] = type + num;
            fieldset['af-title'] = meta.label + ' ' + num;
            // Add boilerplate contents if any
            if (Array.isArray(meta.boilerplate) && meta.boilerplate.length) {
              fieldset['#children'].push(...meta.boilerplate);
            }
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
        return editor.meta.entities[entity.type];
      };

      // Scroll an entity's first fieldset into view of the canvas
      this.scrollToEntity = function(entityName) {
        const $canvas = $('#afGuiEditor-canvas-body');
        const $entity = $('.af-gui-container-type-fieldset[data-entity="' + entityName + '"]').first();
        let scrollValue;
        let maxScroll;
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

      // Get all entities or a filtered list
      this.getEntities = function(filter) {
        return filter ? _.filter($scope.entities, filter) : _.toArray($scope.entities);
      };

      const placementEntities = {};
      const allPlacementEntities = getPlacementEntitiesFromMeta(this.meta.afform_placement);

      // Converts metaPlacements array to `{contact_id: Contact, event_id: Event, etc.}`
      function getPlacementEntitiesFromMeta(metaPlacements) {
        const placements = {};
        metaPlacements.forEach((item) => {
          _.extend(placements, editor.meta.placement_entities[item.id]);
        });
        return placements;
      }

      function getPlacementEntityLabel(entityName) {
        const entityLabel = (afGui.getEntity(entityName) || {}).label || entityName;
        return ts('%1 being Viewed', {1: entityLabel});
      }

      this.hasPlacementEntities = function() {
        if (editor.afform.placement.length > 0) {
          return editor.meta.afform_placement.some((item) => editor.afform.placement.includes(item.id) && item.grouping);
        }
        return false;
      };

      // Returns currently available placementEntities (as reference for compatibility with ng-repeat)
      this.getPlacementEntities = function() {
        // Return the placementEntities object after ensuring it is up-to-date
        if (editor.afform.placement.length > 0) {
          const placements = getPlacementEntitiesFromMeta(editor.meta.afform_placement.filter(item => editor.afform.placement.includes(item.id)));
          // Unset any unused keys e.g. if a placement has been deselected
          Object.keys(allPlacementEntities).forEach((key) => {
            if (!(key in placements)) {
              delete placementEntities[key];
            }
          });
          // Add items from current placements
          Object.keys(placements).forEach((key) => {
            if (!(key in placementEntities)) {
              placementEntities[key] = {
                key: key,
                entity: placements[key],
                label: getPlacementEntityLabel(placements[key]),
                filter: editor.meta.placement_filters[placements[key]],
              };
            }
          });
        } else {
          Object.keys(placementEntities).forEach((key) => delete placementEntities[key]);
        }
        return placementEntities;
      };

      this.onChangePlacement = function() {
        if (!editor.searchDisplays) {
          return;
        }
        const placementEntities = this.getPlacementEntities();
        if (Object.keys(placementEntities).length) {
          editor.afform.placement_filters = editor.afform.placement_filters || {};
        } else {
          delete editor.afform.placement_filters;
        }
        Object.values(editor.searchDisplays).forEach((searchDisplay) => {
          const filterValues = [];
          // Remove any non-applicable filters
          const filters = afGui.parseDisplayFilters(searchDisplay.element.filters).filter((filter) => {
            if (filter.mode === 'options') {
              if (filter.value in allPlacementEntities && !(filter.value in placementEntities)) {
                return false;
              }
              filterValues.push(filter.value);
            }
            return true;
          });
          // Set default filters for newly-added placements
          Object.keys(placementEntities).forEach((key) => {
            if (!(key in filterValues)) {
              const targetEntity = placementEntities[key].entity;
              const searchEntity = searchDisplay.settings['saved_search_id.api_entity'];
              // Filter on main entity id
              if (targetEntity === searchEntity) {
                filters.push({
                  mode: 'options',
                  name: 'id',
                  value: key,
                });
              }
              // Filter on a reference e.g. Address.contact_id
              else {
                const entityDef = afGui.getEntity(searchEntity);
                const referenceField = Object.values(entityDef.fields).find((field) => field.fk_entity === targetEntity);
                if (referenceField) {
                  filters.push({
                    mode: 'options',
                    name: referenceField.name,
                    value: key,
                  });
                }
              }
            }
          });
          searchDisplay.element.filters = afGui.stringifyDisplayFilters(filters);
          if (!searchDisplay.element.filters) {
            delete searchDisplay.element.filters;
          }
        });
      };

      this.placementRequiresServerRoute = function() {
        let requiresServerRoute = false;
        editor.afform.placement.forEach(function(placement) {
          const item = editor.meta.afform_placement.find(item => item.id === placement);
          if (item && item.filter) {
            requiresServerRoute = item.text;
          }
        });
        return requiresServerRoute;
      };

      // Gets complete field defn, merging values from the field with default values
      function fillFieldDefn(entityType, field) {
        const spec = _.cloneDeep(afGui.getField(entityType, field.name));
        return _.merge(spec, field.defn || {});
      }

      // Get all fields on the form for a particular entity
      this.getEntityFields = function(entityName) {
        const fieldsets = afGui.findRecursive(editor.layout['#children'], {'af-fieldset': entityName}),
          entityType = editor.getEntity(entityName).type,
          entityFields = {fields: [], joins: []},
          isJoin = function (item) {
            return _.isPlainObject(item) && ('af-join' in item);
          };
        _.each(fieldsets, function(fieldset) {
          _.each(afGui.getFormElements(fieldset['#children'], {'#tag': 'af-field'}, isJoin), function(field) {
            if (field.name) {
              entityFields.fields.push(fillFieldDefn(entityType, field));
            }
          });
          _.each(afGui.getFormElements(fieldset['#children'], isJoin), function(join) {
            const joinFields = [];
            _.each(afGui.getFormElements(join['#children'], {'#tag': 'af-field'}), function(field) {
              if (field.name) {
                joinFields.push(fillFieldDefn(join['af-join'], field));
              }
            });
            if (joinFields.length) {
              entityFields.joins.push({
                entity: join['af-join'],
                fields: joinFields
              });
            }
          });
        });
        return entityFields;
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

      this.toggleManualProcessing = function() {
        if (editor.afform.manual_processing) {
          editor.afform.manual_processing = null;
        } else {
          editor.afform.create_submission = true;
        }
      };

      this.toggleEmailVerification = function() {
        if (editor.afform.allow_verification_by_email) {
          editor.afform.allow_verification_by_email = null;
        } else {
          editor.afform.create_submission = true;
          editor.afform.manual_processing = true;
        }
      };

      function loadNavigationMenu() {
        if ('navigationMenu' in editor) {
          return;
        }
        editor.navigationMenu = null;
        const conditions = [
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
            const children = buildTree(items, item.id),
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
        const searchFieldsets = afGui.findRecursive(editor.afform.layout, {'af-fieldset': ''});
        return _.transform(searchFieldsets, function(searchDisplays, fieldset) {
          const displayElement = afGui.findRecursive(fieldset['#children'], function (item) {
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
        const searchName = display.key.split('.')[0];
        const displayName = display.key.split('.')[1] || '';
        const fieldset = {
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
        const meta = {
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
          const current = getSearchDisplaysOnForm();
          Object.keys(editor.searchDisplays).forEach(key => {
            if (!(key in current)) {
              delete editor.searchDisplays[key];
              editor.selectEntity(null);
            }
          });
        }
      };

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
        const sort = ui.item.sortable;
        // Check if this is a callback for an item dropped into a different container
        // @see https://github.com/angular-ui/ui-sortable notes on canceling
        if (!sort.received && sort.source[0] !== sort.droptarget[0]) {
          const $source = $(sort.source[0]),
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
        const afform = JSON.parse(angular.toJson(editor.afform));
        // This might be set to undefined by validation
        afform.server_route = afform.server_route || '';
        // create submission is required if email confirmation is selected.
        if (afform.manual_processing || afform.allow_verification_by_email) {
          afform.create_submission = true;
        }
        $scope.saving = true;
        crmApi4('Afform', 'save', {formatWhitespace: true, records: [afform]})
          .then(function (data) {
            $scope.saving = false;
            // When saving a new form for the first time
            if (!editor.afform.name) {
              undoAction = 'save';
              editor.afform.name = data[0].name;
              // Update path to editing url
              changePathQuietly('/edit/' + data[0].name);
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
        const height = $(window).height() - $('#afGuiEditor').offset().top;
        $('#afGuiEditor').height(Math.floor(height));
      }

      // Compress tabs on small screens
      this.adjustTabWidths = function() {
        $('#afGuiEditor .panel-heading ul.nav-tabs li.active').css('max-width', '');
        $('#afGuiEditor .panel-heading ul.nav-tabs').each(function() {
          let remainingSpace = Math.floor($(this).width()) - 1,
            inactiveTabs = $(this).children('li.fluid-width-tab').not('.active');
          $(this).children('.active,:not(.fluid-width-tab)').each(function() {
            remainingSpace -= $(this).width();
          });
          if (inactiveTabs.length) {
            inactiveTabs.css('max-width', Math.floor(remainingSpace / inactiveTabs.length) + 'px');
          }
        });
      };

      // Change the URL path without triggering a route change
      function changePathQuietly(newPath) {
        const lastRoute = $route.current;
        // Intercept location change and restore current route
        const un = $rootScope.$on('$locationChangeSuccess', function() {
          $route.current = lastRoute;
          un();
        });
        return $location.path(newPath);
      }

    }
  });

})(angular, CRM.$, CRM._);
