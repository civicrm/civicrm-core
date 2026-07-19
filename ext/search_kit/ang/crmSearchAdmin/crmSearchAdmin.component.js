(function(angular, $, _) {
  "use strict";

  // Hooks allow code outside this component to modify behaviors.
  // Register a hook by decorating "crmSearchAdminDirective". Ex:
  //   angular.module('myModule').decorator('crmSearchAdminDirective', function($delegate) {
  //     $delegate[0].controller.hook.postSaveDisplay.push(function(display) {
  //       console.log(display);
  //     });
  //     return $delegate;
  //   });
  const hook = {
    findCriticalChanges: [],
    preSaveDisplay: [],
    postSaveDisplay: []
  };

  // Dispatch {hookName} on behalf of each {target}. Pass-through open-ended {data}.
  function fireHooks(hookName, targets, data) {
    if (hook[hookName].length) {
      targets.forEach(function(target) {
        hook[hookName].forEach(function(callback) {
          callback(target, data);
        });
      });
    }
  }

  // Controller function for main crmSearchAdmin component
  const ctrl = function($scope, $element, $location, $timeout, crmApi4, dialogService, searchMeta, crmUiHelp) {
    const ts = $scope.ts = CRM.ts('org.civicrm.search_kit');
    const ctrl = this;
    let afformLoad;
    $scope.hs = crmUiHelp({file: 'CRM/Search/Help/Compose'});

    this.afformEnabled = 'org.civicrm.afform' in CRM.crmSearchAdmin.modules;
    this.afformAdminEnabled = CRM.checkPerm('manage own afform') &&
      'org.civicrm.afform_admin' in CRM.crmSearchAdmin.modules;
    this.displayTypes = Object.fromEntries(CRM.crmSearchAdmin.displayTypes.map(type => [type.id, type]));

    // Only super admins are allowed to create entity displays
    if (!CRM.checkPerm('all CiviCRM permissions and ACLs')) {
      delete this.displayTypes.entity;
    }

    this.searchDisplayPath = CRM.url('civicrm/search');
    this.afformPath = CRM.url('civicrm/admin/afform');
    this.debug = {};

    this.mainTabs = [];

    const buildTabs = () => {
      this.mainTabs.length = 0;

      // Regular tabs
      if (!this.isEntitySet()) {
        this.mainTabs.push(
          {
            key: 'for',
            title: ts('Search For'),
            icon: 'fa-search',
            template: '~/crmSearchAdmin/crmSearch-for.html',
          },
          {
            key: 'fields',
            title: ts('Select Fields'),
            icon: 'fa-columns',
            template: '~/crmSearchAdmin/crmSearch-fields.html',
          },
        );
      }

      // EntitySet tabs
      else {
        this.mainTabs.push(
          {
            key: 'entitySets',
            title: ts('Combine Searches'),
            icon: 'fa-layer-group',
            template: '~/crmSearchAdmin/crmSearch-entitySets.html',
          },
        );
        this.savedSearch.api_params.sets.forEach((set, index) => {
          const setKey = 'set_' + index;
          const entity = searchMeta.getEntity(set[1]);
          this.mainTabs.push(
            {
              key: setKey,
              title: '' + (index + 1) + '. ' + entity.title_plural,
              icon: entity.icon,
              template: '~/crmSearchAdmin/crmSearch-entitySetQuery.html',
              entitySet: set,
            },
          );
        });
      }

      this.mainTabs.push(
        {
          key: 'conditions',
          title: ts('Filter Conditions'),
          icon: 'fa-filter',
          template: '~/crmSearchAdmin/crmSearch-conditions.html',
        },
        {
          key: 'settings',
          title: ts('Configure Settings'),
          icon: 'fa-gears',
          template: '~/crmSearchAdmin/crmSearch-settings.html',
        },
        {
          key: 'query',
          title: ts('Query Info'),
          icon: 'fa-info-circle',
          template: '~/crmSearchAdmin/crmSearch-query.html',
        }
      );
    };

    this.selectedDisplay = function() {
      // Could return the display but for now we don't need it
      return $scope.controls.tab.startsWith('display_');
    };

    $scope.joinTypes = [
      {k: 'LEFT', v: ts('With (optional)')},
      {k: 'INNER', v: ts('With (required)')},
      {k: 'EXCLUDE', v: ts('Without')},
    ];
    $scope.getEntity = searchMeta.getEntity;
    $scope.getField = (fieldName, entityName) => searchMeta.getField(fieldName, entityName || ctrl.savedSearch);
    this.perm = {
      viewDebugOutput: CRM.checkPerm('view debug output'),
      editGroups: CRM.checkPerm('edit groups')
    };

    // ngModelOptions to debounce input, prevent browser history items for every character
    this.debounceMode = {
      updateOn: 'default blur',
      debounce: {
        default: 2000,
        blur: 0
      }
    };

    // Populates EntitySet.fields with the field metadata from the first set
    // This allows the fields to be selected in the outer query
    const updateEntitySetFields = () => {
      const entitySet = searchMeta.getEntity('EntitySet');
      if (!entitySet) {
        return;
      }
      const sets = ctrl.savedSearch?.api_params?.sets || [];
      if (!sets.length) {
        entitySet.fields = [];
        return;
      }
      const firstSet = sets[0];
      const firstEntity = firstSet[1];
      const firstParams = firstSet[3];
      const firstSelect = firstParams?.select || [];

      entitySet.fields = firstSelect.map((selectExpr, i) => {
        const info = searchMeta.parseExpr(selectExpr, {api_entity: firstEntity, api_params: firstParams});
        const arg = info.args.find(arg => arg.type === 'field');
        const baseField = arg ? arg.field : null;

        const field = baseField ? angular.copy(baseField) : {
          type: 'Field',
          data_type: info.data_type || 'String'
        };

        // Note that when the field expression uses " AS ", the alias should be used as the field name.
        field.name = info.alias.split(':')[0];
        field.fieldName = field.name;

        // The label should combine the labels of all sets.
        const setLabels = [];
        sets.forEach(set => {
          const setEntity = set[1];
          const setParams = set[3];
          const setSelectExpr = setParams?.select?.[i];
          if (setSelectExpr) {
            const label = searchMeta.getDefaultLabel(setSelectExpr, {api_entity: setEntity, api_params: setParams});
            if (label) {
              setLabels.push(label);
            }
          }
        });

        field.label = _.uniq(setLabels).join(' / ');
        return field;
      });
    };

    let entitySetWatcher = null;
    let optionsLoadedListener = null;

    // Sets up watcher/listener to sync field metadata when using an EntitySet
    const startEntitySetWatcher = () => {
      if (!entitySetWatcher && ctrl.isEntitySet()) {
        entitySetWatcher = $scope.$watch('$ctrl.savedSearch.api_params.sets', () => {
          updateEntitySetFields();
        }, true);
        // When field options have been loaded, re-sync field metadata
        optionsLoadedListener = $scope.$on('searchMetaFieldOptionsLoaded', () => {
          updateEntitySetFields();
        });
      }
    };

    const stopEntitySetWatcher = () => {
      if (entitySetWatcher) {
        entitySetWatcher();
        entitySetWatcher = null;
      }
      if (optionsLoadedListener) {
        optionsLoadedListener();
        optionsLoadedListener = null;
      }
    };

    this.$onInit = function() {
      this.entityTitle = searchMeta.getEntity(this.savedSearch.api_entity).title_plural;

      this.savedSearch.displays = this.savedSearch.displays || [];
      this.savedSearch.form_values = this.savedSearch.form_values || {};
      this.savedSearch.form_values.join = this.savedSearch.form_values.join || {};
      this.savedSearch.groups = this.savedSearch.groups || [];
      this.savedSearch.tag_id = this.savedSearch.tag_id || [];
      this.originalSavedSearch = _.cloneDeep(this.savedSearch);
      this.groupExists = !!this.savedSearch.groups.length;

      this.savedSearch.displays.forEach(function(display) {
        // PHP json_encode() turns an empty object into []. Convert back to {}.
        if (display.settings && Array.isArray(display.settings.pager)) {
          display.settings.pager = {};
        }
      });

      const path = $location.path();
      // In create mode, set defaults and bind params to route for easy copy/paste
      if (path.includes('create/')) {
        const defaults = {
          version: 4,
          select: searchMeta.getEntity(ctrl.savedSearch.api_entity).default_columns,
          orderBy: {},
          where: [],
        };
        ['groupBy', 'join', 'having'].forEach(param => {
          if (ctrl.paramExists(param)) {
            defaults[param] = [];
          }
        });

        $scope.$bindToRoute({
          param: 'params',
          expr: '$ctrl.savedSearch.api_params',
          deep: true,
          default: defaults
        });

        $scope.$bindToRoute({
          param: 'label',
          expr: '$ctrl.savedSearch.label',
          format: 'raw',
          default: ctrl.savedSearch.label
        });
      }

      $scope.mainEntitySelect = searchMeta.getPrimaryAndSecondaryEntitySelect();

      $scope.$watch('$ctrl.savedSearch', onChangeAnything, true);

      // After watcher runs for the first time and messes up the status, set it correctly
      $timeout(function() {
        $scope.status = ctrl.savedSearch && ctrl.savedSearch.id ? 'saved' : 'unsaved';
      });

      startEntitySetWatcher();
      buildTabs();

      // Set current tab: Tab is passed through url by toggleEntitySet
      let defaultTab = $location.search().tab;
      if (!defaultTab || !this.mainTabs.some(t => t.key === defaultTab)) {
        defaultTab = this.mainTabs[0].key;
      }
      $scope.controls = {tab: defaultTab, joinType: 'LEFT'};

      this.loadFieldOptions();
      loadAfforms();
    };

    this.displayIsViewable = function (display) {
      return display.id && (ctrl.displayTypes[display.type] && ctrl.displayTypes[display.type].grouping !== 'non-viewable');
    };

    this.canAddSmartGroup = function() {
      return !ctrl.savedSearch.groups.length && !ctrl.savedSearch.is_template;
    };

    this.isEntitySet = () => {
      return ctrl.savedSearch.api_entity === 'EntitySet';
    };

    this.toggleEntitySet = () => {
      let newEntity, newParams;

      // Convert back to single-entity: restore from the first set, discard the rest
      if (this.isEntitySet()) {
        const firstSet = this.savedSearch.api_params.sets[0];
        newEntity = firstSet[1];
        newParams = angular.copy(firstSet[3]);
        newParams.version = 4;
      }
      // Convert to EntitySet: move current entity/params into the first set
      else {
        const entity = this.savedSearch.api_entity;
        const params = JSON.parse(JSON.stringify(this.savedSearch.api_params));
        delete params.version;
        delete params.having;
        newEntity = 'EntitySet';
        newParams = {
          version: 4,
          select: params.select.map((field) => _.last(field.split(' AS '))),
          sets: [['UNION ALL', entity, 'get', params]],
        };
      }

      // In create mode, update the url params and the screen will auto-refresh
      const path = $location.path();
      if (path.includes('create/')) {
        const search = angular.copy($location.search());
        $location.path('/create/' + newEntity);
        search.params = angular.toJson(newParams);
        search.tab = $scope.controls.tab;
        $location.search(search);
        return;
      }

      // In update mode, no refresh so we'll reinit everything in place
      stopEntitySetWatcher();
      this.savedSearch.api_entity = newEntity;
      this.savedSearch.api_params = newParams;
      startEntitySetWatcher();
      buildTabs();
    };

    function onChangeAnything(newVal, oldVal) {
      $scope.status = 'unsaved';
      /* jshint -W119 */
      if (JSON.stringify(newVal?.api_params?.select) !== JSON.stringify(oldVal?.api_params?.select)) {
        onChangeSelect();
      }
      // When adding or removing an entity from an EntitySet
      if (ctrl.isEntitySet() && newVal?.api_params?.sets?.length !== oldVal?.api_params?.sets?.length) {
        buildTabs();
      }
    }

    // Generate the confirmation dialog
    this.confirmSave = function() {
      // Build displays. For each, identify the {original: ..., updated: ...} variants..
      const targets = {}, data = {messages: []};
      let newCount = 0;
      ctrl.originalSavedSearch.displays.forEach(function(original) {
        const key = original.id ? ('id_' + original.id) : ('new_' + (newCount++));
        targets[key] = targets[key] || {};
        targets[key].original = _.cloneDeep(original);
      });
      ctrl.savedSearch.displays.forEach(function(updated) {
        const key = updated.id ? ('id_' + updated.id) : ('new_' + (newCount++));
        targets[key] = targets[key] || {};
        targets[key].updated = _.cloneDeep(updated);
      });

      fireHooks('findCriticalChanges', Object.values(targets), data);
      if (data.messages.length < 1) return {confirmed: true};
      return {
        title: ts('Are you sure?'),
        template: '<p>' + ts('The following change(s) may affect other customizations:') +'</p><hr/><p ng-repeat="message in messages"><small>{{::message}}</small></p>',
        export: data
      };
    };

    this.save = function() {
      if (!validate()) {
        return;
      }
      $scope.status = 'saving';
      const params = _.cloneDeep(ctrl.savedSearch),
        apiCalls = {},
        chain = {};

      // Chain Group.save
      if (ctrl.groupExists) {
        chain.groups = ['Group', 'save', {defaults: {saved_search_id: '$id'}, records: params.groups}];
      } else if (params.id) {
        apiCalls.deleteGroup = ['Group', 'delete', {where: [['saved_search_id', '=', params.id]]}];
      }
      delete params.groups;

      // Chain SearchDisplay.replace or delete
      const displays = params.displays.filter(display => !display.trashed);
      if (displays.length) {
        fireHooks('preSaveDisplay', displays, apiCalls);
        chain.displays = ['SearchDisplay', 'replace', {
          where: [['saved_search_id', '=', '$id']],
          records: displays,
          reload: ['*', 'is_autocomplete_default'],
        }];
      } else if (params.id) {
        apiCalls.deleteDisplays = ['SearchDisplay', 'delete', {where: [['saved_search_id', '=', params.id]]}];
      }
      delete params.displays;

      // Chain EntityTag.replace or delete
      if (params.tag_id && params.tag_id.length) {
        chain.tag_id = ['EntityTag', 'replace', {
          where: [['entity_id', '=', '$id'], ['entity_table', '=', 'civicrm_saved_search']],
          match: ['entity_id', 'entity_table', 'tag_id'],
          records: params.tag_id.map(id => ({tag_id: id}))
        }];
      } else if (params.id) {
        chain.tag_id = ['EntityTag', 'delete', {
          where: [['entity_id', '=', '$id'], ['entity_table', '=', 'civicrm_saved_search']]
        }];
      }
      delete params.tag_id;

      apiCalls.saved = ['SavedSearch', 'save', {records: [params], chain: chain}, 0];
      crmApi4(apiCalls).then(function(results) {
        // Call postSaveDisplay hook
        if (chain.displays) {
          fireHooks('postSaveDisplay', results.saved.displays, results);
        }
        // After saving a new search, redirect to the edit url
        if (!ctrl.savedSearch.id) {
          $location.url('edit/' + results.saved.id);
        }
        // Set new status to saved unless the user changed something in the interim
        const newStatus = $scope.status === 'unsaved' ? 'unsaved' : 'saved';
        if (results.saved.groups && results.saved.groups.length) {
          ctrl.savedSearch.groups[0].id = results.saved.groups[0].id;
        }
        ctrl.savedSearch.displays = results.saved.displays || [];
        ctrl.originalSavedSearch = _.cloneDeep(ctrl.savedSearch);
        // Wait until after onChangeAnything to update status
        $timeout(function() {
          $scope.status = newStatus;
        });
      });
    };

    this.paramExists = function(param) {
      return searchMeta.getEntity(ctrl.savedSearch.api_entity).params?.includes(param);
    };

    this.hasFunction = function(expr) {
      return typeof expr === 'string' && expr.includes('(');
    };

    this.addDisplay = function(type) {
      const count = ctrl.savedSearch.displays.filter(d => d.type === type).length;
      const searchLabel = ctrl.savedSearch.label || searchMeta.getEntity(ctrl.savedSearch.api_entity).title_plural;
      ctrl.savedSearch.displays.push({
        type: type,
        label: searchLabel + ' ' + ctrl.displayTypes[type].label + ' ' + (count + 1),
      });
      $scope.selectTab('display_' + (ctrl.savedSearch.displays.length - 1));
    };

    this.removeDisplay = function(index) {
      const display = ctrl.savedSearch.displays[index];
      if (display.id) {
        display.trashed = !display.trashed;
        if ($scope.controls.tab === ('display_' + index) && display.trashed) {
          $scope.selectTab('for');
        } else if (!display.trashed) {
          $scope.selectTab('display_' + index);
        }
        if (display.trashed && afformLoad) {
          afformLoad.then(function() {
            const displayForms = ctrl.afforms.filter(form => form.displays.includes(ctrl.savedSearch.name + '.' + display.name));
            if (displayForms.length) {
              let msg = displayForms.length === 1 ?
                ts('Form "%1" will be deleted if the embedded display "%2" is deleted.', {1: displayForms[0].title, 2: display.label}) :
                ts('%1 forms will be deleted if the embedded display "%2" is deleted.', {1: displayForms.length, 2: display.label});
              CRM.alert(msg, ts('Display embedded'), 'alert');
            }
          });
        }
      } else {
        $scope.selectTab('for');
        ctrl.savedSearch.displays.splice(index, 1);
      }
    };

    this.cloneDisplay = function(display) {
      const newDisplay = angular.copy(display);
      delete newDisplay.name;
      delete newDisplay.id;
      newDisplay.label += ' ' + ts('(copy)');
      ctrl.savedSearch.displays.push(newDisplay);
      $scope.selectTab('display_' + (ctrl.savedSearch.displays.length - 1));
    };

    this.addGroup = function() {
      ctrl.savedSearch.groups.push({
        title: '',
        description: '',
        visibility: 'User and User Admin Only',
        group_type: []
      });
      ctrl.groupExists = true;
      $scope.selectTab('group');
    };

    $scope.selectTab = function(tab) {
      // Ensure select clause contains unique values
      ctrl.savedSearch.api_params.select = [...new Set(ctrl.savedSearch.api_params.select)];
      $scope.controls.tab = tab;
    };

    this.removeGroup = function() {
      ctrl.groupExists = !ctrl.groupExists;
      $scope.status = 'unsaved';
      if (!ctrl.groupExists && (!ctrl.savedSearch.groups.length || !ctrl.savedSearch.groups[0].id)) {
        ctrl.savedSearch.groups.length = 0;
      }
      if ($scope.controls.tab === 'group') {
        $scope.selectTab('for');
      }
    };

    // Because angular dropdowns must be a by-reference variable
    const suffixOptionCache = {};

    this.getSuffixOptions = function(expr) {
      const info = searchMeta.parseExpr(expr, ctrl.savedSearch);
      if (!info.fn && info.args[0] && info.args[0].field && info.args[0].field.suffixes) {
        let cacheKey = info.args[0].field.suffixes.join();
        if (!(cacheKey in suffixOptionCache)) {
          suffixOptionCache[cacheKey] = Object.keys(CRM.crmSearchAdmin.optionAttributes)
            .filter(key => info.args[0].field.suffixes.includes(key))
            .reduce((filteredOptions, key) => {
              filteredOptions[key] = CRM.crmSearchAdmin.optionAttributes[key];
              return filteredOptions;
            }, {});
        }
        return suffixOptionCache[cacheKey];
      }
    };

    this.reconcileAggregateColumns = () => {
      ctrl.savedSearch.api_params.select.forEach((col, pos) => {
        const info = searchMeta.parseExpr(col, ctrl.savedSearch);
        const fieldExpr = (info.args.find(arg => arg.type === 'field') || {}).value;
        if (ctrl.mustAggregate(col, ctrl.savedSearch)) {
          // Ensure all non-grouped columns are aggregated if using GROUP BY
          if (!info.fn || info.fn.category !== 'aggregate') {
            let dflFn = searchMeta.getDefaultAggregateFn(info, ctrl.savedSearch) || 'GROUP_CONCAT';
            let flagBefore = dflFn === 'GROUP_CONCAT' ? 'DISTINCT ' : '';
            ctrl.savedSearch.api_params.select[pos] = dflFn + '(' + flagBefore + fieldExpr + ') AS ' + dflFn + '_' + fieldExpr.replace(/[.:]/g, '_');
          }
        } else {
          // Remove aggregate functions when no grouping
          if (info.fn && info.fn.category === 'aggregate') {
            ctrl.savedSearch.api_params.select[pos] = fieldExpr;
          }
        }
      });
    };

    // Returns true if a clause contains one of the
    function clauseUsesFields(clause, fields) {
      if (!fields || !fields.length) {
        return false;
      }
      if (fields.includes(clause[0])) {
        return true;
      }
      if (Array.isArray(clause[1])) {
        return clause[1].some(function(subClause) {
          return clauseUsesField(subClause, fields);
        });
      }
      return false;
    }

    function validate() {
      const errors = [];
      let errorEl,
        label,
        tab;
      if (!ctrl.savedSearch.label) {
        errorEl = '#crm-saved-search-label';
        label = ts('Search Label');
        errors.push(ts('%1 is a required field.', {1: label}));
      }
      if (ctrl.groupExists && !ctrl.savedSearch.groups[0].title) {
        errorEl = '#crm-search-admin-group-title';
        label = ts('Group Title');
        errors.push(ts('%1 is a required field.', {1: label}));
        tab = 'group';
      }
      ctrl.savedSearch.displays.forEach((display, index) => {
        if (!display.trashed && !display.label) {
          errorEl = '#crm-search-admin-display-label';
          label = ts('Display Label');
          errors.push(ts('%1 is a required field.', {1: label}));
          tab = 'display_' + index;
        }
      });
      if (errors.length) {
        if (tab) {
          $scope.selectTab(tab);
        }
        $(errorEl).crmError(errors.join('<br>'), ts('Error Saving'), {expires: 5000});
      }
      return !errors.length;
    }

    this.addParam = function(name, value) {
      if (value && !ctrl.savedSearch.api_params[name].includes(value)) {
        ctrl.savedSearch.api_params[name].push(value);
        // This needs to be called when adding a field as well as changing groupBy
        ctrl.reconcileAggregateColumns();
      }
    };

    // Deletes an item from an array param
    this.clearParam = function(name, idx) {
      ctrl.savedSearch.api_params[name].splice(idx, 1);
    };

    function onChangeSelect(newSelect, oldSelect) {
      // When removing a column from SELECT, also remove from ORDER BY & HAVING
      (oldSelect || []).filter(col => !newSelect.includes(col)).forEach(col => {
        col = col.split(' AS ').at(-1);
        delete ctrl.savedSearch.api_params.orderBy[col];
        if (ctrl.savedSearch.api_params.having && ctrl.savedSearch.api_params.having.length) {
          ctrl.savedSearch.api_params.having = ctrl.savedSearch.api_params.having.filter(clause =>
            !clauseUsesFields(clause, [col])
          );
        }
      });
    }

    this.getFieldLabel = (col, savedSearch) => searchMeta.getDefaultLabel(col, savedSearch ?? ctrl.savedSearch);

    // Is a column required to use an aggregate function?
    this.mustAggregate = function(col, savedSearch) {
      savedSearch = savedSearch || ctrl.savedSearch;
      // If the query does not use grouping, it's never required
      if (!savedSearch.api_params.groupBy || !savedSearch.api_params.groupBy.length) {
        return false;
      }
      const arg = searchMeta.parseExpr(col, savedSearch).args.find(arg => arg.type === 'field') || {};
      // If the column is not a database field, no
      if (!arg.field || !arg.field.entity || !['Field', 'Custom', 'Extra'].includes(arg.field.type)) {
        return false;
      }
      // If the column is used for a groupBy, no
      if (savedSearch.api_params.groupBy.indexOf(arg.path) > -1) {
        return false;
      }
      const primaryKeys = searchMeta.getEntity(arg.field.entity)?.primary_key;
      if (!primaryKeys || !primaryKeys.length) {
        return true;
      }
      // If the entity this column belongs to is being grouped by primary key, then also no
      return savedSearch.api_params.groupBy.indexOf(arg.prefix + primaryKeys[0]) < 0;
    };

    this.fieldsForSelect = function() {
      return {
        results: ctrl.getAllFields(ctrl.savedSearch, ':label', ['Field', 'Custom', 'Extra', 'Pseudo'], (key) => {
          ctrl.savedSearch.api_params.select.includes(key);
        })
      };
    };

    this.getAllFields = function(savedSearch, suffix, allowedTypes, disabledIf, topJoin) {
      disabledIf = disabledIf || (() => false);
      allowedTypes = allowedTypes || ['Field', 'Custom', 'Extra', 'Filter'];
      const info = searchMeta.getSearchInfo(savedSearch);

      const getFieldOptionsForFields = (fields, prefix = '') => {
        return fields
          .filter((field) => allowedTypes.includes(field.type))
          .map((field) => {
            // Use options suffix if available.
            const id = prefix + field.name + ((field.suffixes || []).includes(suffix.replace(':', '')) ? suffix : '');
            return {
              id: id,
              text: field.label,
              description: field.description,
              disabled: disabledIf(id)
            };
          });
      };

      const getFieldOptionsForEntity = (entityName, join = null) => {
        const result = [];
        const prefix = join ? (join.alias + '.') : '';

        // Add extra searchable fields from bridge entity
        if (join && join.bridge) {
          const joinFields = searchMeta.getEntity(join.bridge).fields.filter((field) =>
            field.name !== 'id' &&
            field.name !== 'entity_id' &&
            field.name !== 'entity_table' &&
            field.fk_entity !== entityName
          );
          result.push(...getFieldOptionsForFields(joinFields, prefix));
        }

        result.push(...getFieldOptionsForFields(searchMeta.getEntity(entityName).fields, prefix));
        return result;
      };

      const getFieldGroupForJoin = (join) => {
        const joinInfo = searchMeta.getJoin(savedSearch, join);
        const joinEntity = searchMeta.getEntity(joinInfo.entity);

        return {
          text: joinInfo.label,
          description: joinInfo.description,
          icon: joinEntity.icon,
          children: getFieldOptionsForEntity(joinEntity.name, joinInfo),
          alias: joinInfo.alias
        };
      };

      const mainEntity = searchMeta.getEntity(info.api_entity);
      const joins = (info.api_params.join || []).map((joinDef) => joinDef[0]);

      const result = [];

      result.push({
        text: mainEntity.title_plural,
        icon: mainEntity.icon,
        children: getFieldOptionsForEntity(info.api_entity)
      });

      // Include SearchKit's pseudo-fields if specifically requested
      if (allowedTypes.includes('Pseudo')) {
        result.push({
          text: ts('Extra'),
          icon: 'fa-gear',
          children: getFieldOptionsForFields(CRM.crmSearchAdmin.pseudoFields)
        });
      }

      joins.forEach((join) => result.push(getFieldGroupForJoin(join)));

      // Place specified join at top of list
      if (topJoin) {
        const topAlias = topJoin.split(' AS ')[1];
        result.sort((a, b) => (a.alias === topAlias) ? -1 : ((b.alias === topAlias) ? 1 : 0));
      }
      return result;
    };

    this.getSelectFields = (savedSearch, disabledIf) => {
      disabledIf = disabledIf || (() => false);
      return savedSearch.api_params.select.map((fieldExpr) => {
        const info = searchMeta.parseExpr(fieldExpr, savedSearch);
        return {
          id: info.alias,
          text: ctrl.getFieldLabel(fieldExpr, savedSearch),
          description: info.fn ? info.fn.description : info.args[0].field && info.args[0].field.description,
          disabled: disabledIf(info.alias)
        };
      });
    };

    this.isPseudoField = (name) => !!CRM.crmSearchAdmin.pseudoFields.find((field) => field.name === name);

    // Ensure options are loaded for main entity + joined entities
    this.loadFieldOptions = () => {
      const entitiesToLoad = [];

      const addQueryEntities = (searchInfo) => {
        entitiesToLoad.push(searchInfo.api_entity);
        (searchInfo.api_params?.join || []).forEach(join => {
          const joinInfo = searchMeta.getJoin(searchInfo, join[0]);
          if (joinInfo) {
            entitiesToLoad.push(joinInfo.entity);
            if (joinInfo.bridge) {
              entitiesToLoad.push(joinInfo.bridge);
            }
          }
        });
      };

      // Main entity + join entities + bridge entities
      addQueryEntities(ctrl.savedSearch);
      // Extract entity/join/bridge from UNION entity sets
      if (ctrl.savedSearch.api_params.sets) {
        ctrl.savedSearch.api_params.sets.forEach(set => {
          addQueryEntities({api_entity: set[1], api_params: set[3]});
        });
      }

      searchMeta.loadFieldOptions(entitiesToLoad);
    };

    // Build a list of all possible links to main entity & join entities
    // @return {Array}
    this.buildLinks = function(isRow) {
      function addTitle(link, entityName) {
        link.text = link.text.replace('%1', entityName);
      }

      // Links to main entity
      const mainEntity = searchMeta.getEntity(ctrl.savedSearch.api_entity);
      const links = _.cloneDeep(mainEntity.links || []);
      links.forEach(link => {
        link.join = '';
        addTitle(link, mainEntity.title);
      });
      // Links to explicitly joined entities
      (ctrl.savedSearch.api_params.join || []).forEach(joinClause => {
        const join = searchMeta.getJoin(ctrl.savedSearch, joinClause[0]);
        const joinEntity = searchMeta.getEntity(join.entity);
        const bridgeEntity = typeof joinClause[2] === 'string' ? searchMeta.getEntity(joinClause[2]) : null;
        _.cloneDeep(joinEntity.links || []).forEach(link => {
          link.join = join.alias;
          addTitle(link, join.label);
          links.push(link);
        });
        _.cloneDeep(bridgeEntity?.links || []).forEach(link => {
          link.join = join.alias;
          addTitle(link, join.label + (bridgeEntity.bridge_title ? ' ' + bridgeEntity.bridge_title : ''));
          links.push(link);
        });
      });
      // Links to implicit joins
      ctrl.savedSearch.api_params.select.forEach(fieldName => {
        if (!fieldName.includes(' AS ')) {
          const info = searchMeta.parseExpr(fieldName, ctrl.savedSearch).args[0];
          if (info.field && !info.suffix && !info.fn && info.field.type === 'Field' && (info.field.fk_entity || info.field.name !== info.field.fieldName)) {
            const idFieldName = info.field.fk_entity ? fieldName : fieldName.substr(0, fieldName.lastIndexOf('.'));
            const idField = searchMeta.parseExpr(idFieldName, ctrl.savedSearch).args[0].field;
            if (!ctrl.mustAggregate(idFieldName, ctrl.savedSearch)) {
              const joinEntity = searchMeta.getEntity(idField.fk_entity);
              const label = (idField.join ? idField.join.label + ': ' : '') + (idField.input_attrs && idField.input_attrs.label || idField.label);
              _.cloneDeep(joinEntity?.links || []).forEach(link => {
                link.join = idFieldName;
                addTitle(link, label);
                links.push(link);
              });
            }
          }
        }
      });
      // Filter links according to usage - add & browse only make sense outside of a row
      return links.filter((link) => ['add', 'browse'].includes(link.action) !== isRow);
    };

    function loadAfforms() {
      ctrl.afforms = null;
      if (ctrl.afformEnabled && ctrl.savedSearch.id) {
        const findDisplays = ctrl.savedSearch.displays.reduce((findDisplays, display) => {
          if (display.id && display.name) {
            findDisplays.push(['search_displays', 'CONTAINS', ctrl.savedSearch.name + '.' + display.name]);
          }
          return findDisplays;
        }, [['search_displays', 'CONTAINS', ctrl.savedSearch.name]]);
        afformLoad = crmApi4('Afform', 'get', {
          select: ['name', 'title', 'search_displays'],
          where: [['OR', findDisplays]]
        }).then((afforms) => {
          ctrl.afforms = afforms.map(afform => ({
            title: afform.title,
            displays: afform.search_displays,
            link: ctrl.afformAdminEnabled ? CRM.url('civicrm/admin/afform#/edit/' + afform.name) : '',
          }));
          ctrl.afformCount = ctrl.afforms.length;
        });
      }
    }

    // Creating an Afform opens a new tab, so when switching back after > 10 sec, re-check for Afforms
    $(window).on('focus', _.debounce(function() {
      $scope.$apply(loadAfforms);
    }, 10000, {leading: true, trailing: false}));

  };

  ctrl.hook = hook;

  angular.module('crmSearchAdmin').component('crmSearchAdmin', {
    bindings: {
      savedSearch: '<'
    },
    templateUrl: '~/crmSearchAdmin/crmSearchAdmin.html',
    controller: ctrl
  });

})(angular, CRM.$, CRM._);
