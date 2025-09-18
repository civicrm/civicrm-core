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
  var hook = {
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
  var ctrl = function($scope, $element, $location, $timeout, crmApi4, dialogService, searchMeta, crmUiHelp) {
    var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
      ctrl = this,
      afformLoad,
      fieldsForJoinGetters = {};
    $scope.hs = crmUiHelp({file: 'CRM/Search/Help/Compose'});

    this.afformEnabled = 'org.civicrm.afform' in CRM.crmSearchAdmin.modules;
    this.afformAdminEnabled = CRM.checkPerm('manage own afform') &&
      'org.civicrm.afform_admin' in CRM.crmSearchAdmin.modules;
    this.displayTypes = _.indexBy(CRM.crmSearchAdmin.displayTypes, 'id');
    this.searchDisplayPath = CRM.url('civicrm/search');
    this.afformPath = CRM.url('civicrm/admin/afform');
    this.debug = {};

    this.mainTabs = [
      {
        key: 'for',
        title: ts('Search For'),
        icon: 'fa-search',
      },
      {
        key: 'conditions',
        title: ts('Filter Conditions'),
        icon: 'fa-filter',
      },
      {
        key: 'fields',
        title: ts('Select Fields'),
        icon: 'fa-columns',
      },
      {
        key: 'settings',
        title: ts('Configure Settings'),
        icon: 'fa-gears',
      },
      {
        key: 'query',
        title: ts('Query Info'),
        icon: 'fa-info-circle',
      },
    ];

    $scope.controls = {tab: this.mainTabs[0].key, joinType: 'LEFT'};

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
    $scope.getField = searchMeta.getField;
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
        var defaults = {
          version: 4,
          select: searchMeta.getEntity(ctrl.savedSearch.api_entity).default_columns,
          orderBy: {},
          where: [],
        };
        _.each(['groupBy', 'join', 'having'], function(param) {
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

        // Set default label
        ctrl.savedSearch.label = ctrl.savedSearch.label || ts('%1 Search by %2', {
          1: searchMeta.getEntity(ctrl.savedSearch.api_entity).title,
          2: CRM.crmSearchAdmin.myName
        });
        $scope.$bindToRoute({
          param: 'label',
          expr: '$ctrl.savedSearch.label',
          format: 'raw',
          default: ctrl.savedSearch.label
        });
      }

      $scope.getJoin = _.wrap(this.savedSearch, searchMeta.getJoin);

      $scope.mainEntitySelect = searchMeta.getPrimaryAndSecondaryEntitySelect();

      $scope.$watchCollection('$ctrl.savedSearch.api_params.select', onChangeSelect);

      $scope.$watch('$ctrl.savedSearch', onChangeAnything, true);

      // After watcher runs for the first time and messes up the status, set it correctly
      $timeout(function() {
        $scope.status = ctrl.savedSearch && ctrl.savedSearch.id ? 'saved' : 'unsaved';
      });

      loadFieldOptions();
      loadAfforms();
    };

    this.displayIsViewable = function (display) {
      return display.id && (ctrl.displayTypes[display.type] && ctrl.displayTypes[display.type].grouping !== 'non-viewable');
    };

    this.canAddSmartGroup = function() {
      return !ctrl.savedSearch.groups.length && !ctrl.savedSearch.is_template;
    };

    function onChangeAnything() {
      $scope.status = 'unsaved';
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

      fireHooks('findCriticalChanges', _.values(targets), data);
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
      var params = _.cloneDeep(ctrl.savedSearch),
        apiCalls = {},
        chain = {};
      if (ctrl.groupExists) {
        chain.groups = ['Group', 'save', {defaults: {saved_search_id: '$id'}, records: params.groups}];
        delete params.groups;
      } else if (params.id) {
        apiCalls.deleteGroup = ['Group', 'delete', {where: [['saved_search_id', '=', params.id]]}];
      }
      _.remove(params.displays, {trashed: true});
      if (params.displays && params.displays.length) {
        fireHooks('preSaveDisplay', params.displays, apiCalls);
        chain.displays = ['SearchDisplay', 'replace', {
          where: [['saved_search_id', '=', '$id']],
          records: params.displays,
          reload: ['*', 'is_autocomplete_default'],
        }];
      } else if (params.id) {
        apiCalls.deleteDisplays = ['SearchDisplay', 'delete', {where: [['saved_search_id', '=', params.id]]}];
      }
      delete params.displays;
      if (params.tag_id && params.tag_id.length) {
        chain.tag_id = ['EntityTag', 'replace', {
          where: [['entity_id', '=', '$id'], ['entity_table', '=', 'civicrm_saved_search']],
          match: ['entity_id', 'entity_table', 'tag_id'],
          records: _.transform(params.tag_id, function(records, id) {records.push({tag_id: id});})
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
        var newStatus = $scope.status === 'unsaved' ? 'unsaved' : 'saved';
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
      return _.includes(searchMeta.getEntity(ctrl.savedSearch.api_entity).params, param);
    };

    this.hasFunction = function(expr) {
      return expr.indexOf('(') > -1;
    };

    this.addDisplay = function(type) {
      var count = _.filter(ctrl.savedSearch.displays, {type: type}).length,
        searchLabel = ctrl.savedSearch.label || searchMeta.getEntity(ctrl.savedSearch.api_entity).title_plural;
      ctrl.savedSearch.displays.push({
        type: type,
        label: searchLabel + ' ' + ctrl.displayTypes[type].label + ' ' + (count + 1),
      });
      $scope.selectTab('display_' + (ctrl.savedSearch.displays.length - 1));
    };

    this.removeDisplay = function(index) {
      var display = ctrl.savedSearch.displays[index];
      if (display.id) {
        display.trashed = !display.trashed;
        if ($scope.controls.tab === ('display_' + index) && display.trashed) {
          $scope.selectTab('for');
        } else if (!display.trashed) {
          $scope.selectTab('display_' + index);
        }
        if (display.trashed && afformLoad) {
          afformLoad.then(function() {
            var displayForms = _.filter(ctrl.afforms, function(form) {
              return _.includes(form.displays, ctrl.savedSearch.name + '.' + display.name);
            });
            if (displayForms.length) {
              var msg = displayForms.length === 1 ?
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
      var newDisplay = angular.copy(display);
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
      if (tab === 'group') {
        loadFieldOptions('Group');
        $scope.smartGroupColumns = searchMeta.getSmartGroupColumns(ctrl.savedSearch);
        var smartGroupColumns = _.map($scope.smartGroupColumns, 'id');
        if (smartGroupColumns.length && !_.includes(smartGroupColumns, ctrl.savedSearch.api_params.select[0])) {
          ctrl.savedSearch.api_params.select.unshift(smartGroupColumns[0]);
        }
      }
      ctrl.savedSearch.api_params.select = _.uniq(ctrl.savedSearch.api_params.select);
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

    function addNum(name, num) {
      return name + (num < 10 ? '_0' : '_') + num;
    }

    function getExistingJoins() {
      return _.transform(ctrl.savedSearch.api_params.join || [], function(joins, join) {
        joins[join[0].split(' AS ')[1]] = searchMeta.getJoin(ctrl.savedSearch, join[0]);
      }, {});
    }

    $scope.getJoinEntities = function() {
      var existingJoins = getExistingJoins();

      function addEntityJoins(entity, stack, baseEntity) {
        return _.transform(CRM.crmSearchAdmin.joins[entity], function(joinEntities, join) {
          var num = 0;
          if (
            // Exclude joins that singly point back to the original entity
            !(baseEntity === join.entity && !join.multi) &&
            // Exclude joins to bridge tables
            !searchMeta.getEntity(join.entity).bridge
          ) {
            do {
              appendJoin(joinEntities, join, ++num, stack, entity);
            } while (addNum((stack ? stack + '_' : '') + join.alias, num) in existingJoins);
          }
        }, []);
      }

      function appendJoin(collection, join, num, stack, baseEntity) {
        var alias = addNum((stack ? stack + '_' : '') + join.alias, num),
          opt = {
            id: join.entity + ' AS ' + alias,
            description: join.description,
            text: join.label + (num > 1 ? ' ' + num : ''),
            icon: searchMeta.getEntity(join.entity).icon,
            disabled: alias in existingJoins
          };
        if (alias in existingJoins) {
          opt.children = addEntityJoins(join.entity, alias, baseEntity);
        }
        collection.push(opt);
      }

      return {results: addEntityJoins(ctrl.savedSearch.api_entity)};
    };

    this.addJoin = function(value) {
      if (value) {
        ctrl.savedSearch.api_params.join = ctrl.savedSearch.api_params.join || [];
        var join = searchMeta.getJoin(ctrl.savedSearch, value),
          entity = searchMeta.getEntity(join.entity),
          params = [value, $scope.controls.joinType || 'LEFT'];
        _.each(_.cloneDeep(join.conditions), function(condition) {
          params.push(condition);
        });
        _.each(_.cloneDeep(join.defaults), function(condition) {
          params.push(condition);
        });
        ctrl.savedSearch.api_params.join.push(params);
        if (entity.search_fields && $scope.controls.joinType !== 'EXCLUDE') {
          // Add columns for newly-joined entity
          entity.search_fields.forEach((fieldName) => {
            // Try to avoid adding duplicate columns
            const simpleName = _.last(fieldName.split('.'));
            if (!ctrl.savedSearch.api_params.select.join(',').includes(simpleName)) {
              if (searchMeta.getField(fieldName, join.entity)) {
                ctrl.savedSearch.api_params.select.push(join.alias + '.' + fieldName);
              }
            }
          });
        }
        loadFieldOptions();
      }
    };

    // Factory returns a getter-setter function for ngModel
    this.getSetJoinLabel = function(joinName) {
      return _.wrap(joinName, getSetJoinLabel);
    };

    function getSetJoinLabel(joinName, value) {
      const joinInfo = searchMeta.getJoin(ctrl.savedSearch, joinName);
      const alias = joinInfo.alias;
      // Setter
      if (arguments.length > 1) {
        ctrl.savedSearch.form_values.join[alias] = value;
        if (!value || value === joinInfo.defaultLabel) {
          delete ctrl.savedSearch.form_values.join[alias];
        }
      }
      return ctrl.savedSearch.form_values.join[alias] || joinInfo.defaultLabel;
    }

    // Remove an explicit join + all SELECT, WHERE & other JOINs that use it
    this.removeJoin = function(index) {
      var alias = searchMeta.getJoin(ctrl.savedSearch, ctrl.savedSearch.api_params.join[index][0]).alias;
      ctrl.clearParam('join', index);
      removeJoinStuff(alias);
    };

    function removeJoinStuff(alias) {
      _.remove(ctrl.savedSearch.api_params.select, function(item) {
        var pattern = new RegExp('\\b' + alias + '\\.');
        return pattern.test(item.split(' AS ')[0]);
      });
      _.remove(ctrl.savedSearch.api_params.where, function(clause) {
        return clauseUsesJoin(clause, alias);
      });
      _.eachRight(ctrl.savedSearch.api_params.join, function(item, i) {
        var joinAlias = searchMeta.getJoin(ctrl.savedSearch, item[0]).alias;
        if (joinAlias !== alias && joinAlias.indexOf(alias) === 0) {
          ctrl.removeJoin(i);
        }
      });
      delete ctrl.savedSearch.form_values.join[alias];
    }

    this.changeJoinType = function(join) {
      if (join[1] === 'EXCLUDE') {
        removeJoinStuff(searchMeta.getJoin(ctrl.savedSearch, join[0]).alias);
      }
    };

    $scope.changeGroupBy = function(idx) {
      // When clearing a selection
      if (!ctrl.savedSearch.api_params.groupBy[idx]) {
        ctrl.clearParam('groupBy', idx);
      }
      reconcileAggregateColumns();
    };

    function reconcileAggregateColumns() {
      _.each(ctrl.savedSearch.api_params.select, function(col, pos) {
        var info = searchMeta.parseExpr(col),
          fieldExpr = (_.findWhere(info.args, {type: 'field'}) || {}).value;
        if (ctrl.mustAggregate(col)) {
          // Ensure all non-grouped columns are aggregated if using GROUP BY
          if (!info.fn || info.fn.category !== 'aggregate') {
            let dflFn = searchMeta.getDefaultAggregateFn(info, ctrl.savedSearch.api_params) || 'GROUP_CONCAT';
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
    }

    function clauseUsesJoin(clause, alias) {
      if (clause[0].indexOf(alias + '.') === 0) {
        return true;
      }
      if (_.isArray(clause[1])) {
        return clause[1].some(function(subClause) {
          return clauseUsesJoin(subClause, alias);
        });
      }
      return false;
    }

    // Returns true if a clause contains one of the
    function clauseUsesFields(clause, fields) {
      if (!fields || !fields.length) {
        return false;
      }
      if (_.includes(fields, clause[0])) {
        return true;
      }
      if (_.isArray(clause[1])) {
        return clause[1].some(function(subClause) {
          return clauseUsesField(subClause, fields);
        });
      }
      return false;
    }

    function validate() {
      var errors = [],
        errorEl,
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
      _.each(ctrl.savedSearch.displays, function(display, index) {
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
      if (value && !_.contains(ctrl.savedSearch.api_params[name], value)) {
        ctrl.savedSearch.api_params[name].push(value);
        // This needs to be called when adding a field as well as changing groupBy
        reconcileAggregateColumns();
      }
    };

    // Deletes an item from an array param
    this.clearParam = function(name, idx) {
      ctrl.savedSearch.api_params[name].splice(idx, 1);
    };

    function onChangeSelect(newSelect, oldSelect) {
      // When removing a column from SELECT, also remove from ORDER BY & HAVING
      _.each(_.difference(oldSelect, newSelect), function(col) {
        col = _.last(col.split(' AS '));
        delete ctrl.savedSearch.api_params.orderBy[col];
        _.remove(ctrl.savedSearch.api_params.having, function(clause) {
          return clauseUsesFields(clause, [col]);
        });
      });
    }

    this.getFieldLabel = searchMeta.getDefaultLabel;

    // Is a column eligible to use an aggregate function?
    this.canAggregate = function(col) {
      if (!ctrl.paramExists('groupBy')) {
        return false;
      }
      // If the query does not use grouping, it's always allowed
      if (!ctrl.savedSearch.api_params.groupBy || !ctrl.savedSearch.api_params.groupBy.length) {
        return true;
      }
      return this.mustAggregate(col);
    };

    // Is a column required to use an aggregate function?
    this.mustAggregate = function(col) {
      // If the query does not use grouping, it's never required
      if (!ctrl.savedSearch.api_params.groupBy || !ctrl.savedSearch.api_params.groupBy.length) {
        return false;
      }
      var arg = _.findWhere(searchMeta.parseExpr(col).args, {type: 'field'}) || {};
      // If the column is not a database field, no
      if (!arg.field || !arg.field.entity || !_.includes(['Field', 'Custom', 'Extra'], arg.field.type)) {
        return false;
      }
      // If the column is used for a groupBy, no
      if (ctrl.savedSearch.api_params.groupBy.indexOf(arg.path) > -1) {
        return false;
      }
      // If the entity this column belongs to is being grouped by primary key, then also no
      var idField = searchMeta.getEntity(arg.field.entity).primary_key[0];
      return ctrl.savedSearch.api_params.groupBy.indexOf(arg.prefix + idField) < 0;
    };

    $scope.fieldsForGroupBy = function() {
      return {results: ctrl.getAllFields('', ['Field', 'Custom', 'Extra'], function(key) {
          return _.contains(ctrl.savedSearch.api_params.groupBy, key);
        })
      };
    };

    function getFieldsForJoin(joinEntity) {
      return {results: ctrl.getAllFields(':name', ['Field', 'Custom', 'Extra'], null, joinEntity)};
    }

    // @return {function}
    $scope.fieldsForJoin = function(joinEntity) {
      if (!fieldsForJoinGetters[joinEntity]) {
        fieldsForJoinGetters[joinEntity] = _.wrap(joinEntity, getFieldsForJoin);
      }
      return fieldsForJoinGetters[joinEntity];
    };

    $scope.fieldsForWhere = function() {
      return {results: ctrl.getAllFields(':name')};
    };

    $scope.fieldsForHaving = function() {
      return {results: ctrl.getSelectFields()};
    };

    this.fieldsForSelect = function() {
      return {
        results: ctrl.getAllFields(':label', ['Field', 'Custom', 'Extra', 'Pseudo'], (key) => {
          ctrl.savedSearch.api_params.select.includes(key);
        })
      };
    };

    this.getAllFields = function(suffix, allowedTypes, disabledIf, topJoin) {
      disabledIf = disabledIf || _.noop;
      allowedTypes = allowedTypes || ['Field', 'Custom', 'Extra', 'Filter'];

      function formatEntityFields(entityName, join) {
        var prefix = join ? join.alias + '.' : '',
          result = [];

        // Add extra searchable fields from bridge entity
        if (join && join.bridge) {
          formatFields(_.filter(searchMeta.getEntity(join.bridge).fields, function(field) {
            return (field.name !== 'id' && field.name !== 'entity_id' && field.name !== 'entity_table' && field.fk_entity !== entityName);
          }), result, prefix);
        }

        formatFields(searchMeta.getEntity(entityName).fields, result, prefix);
        return result;
      }

      function formatFields(fields, result, prefix) {
        prefix = typeof prefix === 'undefined' ? '' : prefix;
        _.each(fields, function(field) {
          var item = {
            // Use options suffix if available.
            id: prefix + field.name + (_.includes(field.suffixes || [], suffix.replace(':', '')) ? suffix : ''),
            text: field.label,
            description: field.description
          };
          if (disabledIf(item.id)) {
            item.disabled = true;
          }
          if (_.includes(allowedTypes, field.type)) {
            result.push(item);
          }
        });
        return result;
      }

      var mainEntity = searchMeta.getEntity(ctrl.savedSearch.api_entity),
        joinEntities = _.map(ctrl.savedSearch.api_params.join, 0),
        result = [];

      function addJoin(join) {
        let joinInfo = searchMeta.getJoin(ctrl.savedSearch, join),
          joinEntity = searchMeta.getEntity(joinInfo.entity);
        result.push({
          text: joinInfo.label,
          description: joinInfo.description,
          icon: joinEntity.icon,
          children: formatEntityFields(joinEntity.name, joinInfo)
        });
      }

      // Place specified join at top of list
      if (topJoin) {
        addJoin(topJoin);
        _.pull(joinEntities, topJoin);
      }

      result.push({
        text: mainEntity.title_plural,
        icon: mainEntity.icon,
        children: formatEntityFields(ctrl.savedSearch.api_entity)
      });

      // Include SearchKit's pseudo-fields if specifically requested
      if (_.includes(allowedTypes, 'Pseudo')) {
        result.push({
          text: ts('Extra'),
          icon: 'fa-gear',
          children: formatFields(CRM.crmSearchAdmin.pseudoFields, [])
        });
      }

      _.each(joinEntities, addJoin);
      return result;
    };

    this.getSelectFields = function(disabledIf) {
      disabledIf = disabledIf || _.noop;
      return _.transform(ctrl.savedSearch.api_params.select, function(fields, name) {
        var info = searchMeta.parseExpr(name);
        var item = {
          id: info.alias,
          text: ctrl.getFieldLabel(name),
          description: info.fn ? info.fn.description : info.args[0].field && info.args[0].field.description
        };
        if (disabledIf(item.id)) {
          item.disabled = true;
        }
        fields.push(item);
      });
    };

    this.isPseudoField = function(name) {
      return _.findIndex(CRM.crmSearchAdmin.pseudoFields, {name: name}) >= 0;
    };

    // Ensure options are loaded for main entity + joined entities
    // And an optional additional entity
    function loadFieldOptions(entity) {
      // Main entity
      var entitiesToLoad = [ctrl.savedSearch.api_entity];

      // Join entities + bridge entities
      _.each(ctrl.savedSearch.api_params.join, function(join) {
        var joinInfo = searchMeta.getJoin(ctrl.savedSearch, join[0]);
        entitiesToLoad.push(joinInfo.entity);
        if (joinInfo.bridge) {
          entitiesToLoad.push(joinInfo.bridge);
        }
      });

      // Optional additional entity
      if (entity) {
        entitiesToLoad.push(entity);
      }

      searchMeta.loadFieldOptions(entitiesToLoad);
    }

    // Build a list of all possible links to main entity & join entities
    // @return {Array}
    this.buildLinks = function(isRow) {
      function addTitle(link, entityName) {
        link.text = link.text.replace('%1', entityName);
      }

      // Links to main entity
      var mainEntity = searchMeta.getEntity(ctrl.savedSearch.api_entity),
        links = _.cloneDeep(mainEntity.links || []);
      _.each(links, function(link) {
        link.join = '';
        addTitle(link, mainEntity.title);
      });
      // Links to explicitly joined entities
      _.each(ctrl.savedSearch.api_params.join, function(joinClause) {
        var join = searchMeta.getJoin(ctrl.savedSearch, joinClause[0]),
          joinEntity = searchMeta.getEntity(join.entity),
          bridgeEntity = _.isString(joinClause[2]) ? searchMeta.getEntity(joinClause[2]) : null;
        _.each(_.cloneDeep(joinEntity.links), function(link) {
          link.join = join.alias;
          addTitle(link, join.label);
          links.push(link);
        });
        _.each(_.cloneDeep(bridgeEntity && bridgeEntity.links), function(link) {
          link.join = join.alias;
          addTitle(link, join.label + (bridgeEntity.bridge_title ? ' ' + bridgeEntity.bridge_title : ''));
          links.push(link);
        });
      });
      // Links to implicit joins
      _.each(ctrl.savedSearch.api_params.select, function(fieldName) {
        if (!_.includes(fieldName, ' AS ')) {
          var info = searchMeta.parseExpr(fieldName).args[0];
          if (info.field && !info.suffix && !info.fn && info.field.type === 'Field' && (info.field.fk_entity || info.field.name !== info.field.fieldName)) {
            var idFieldName = info.field.fk_entity ? fieldName : fieldName.substr(0, fieldName.lastIndexOf('.')),
              idField = searchMeta.parseExpr(idFieldName).args[0].field;
            if (!ctrl.mustAggregate(idFieldName)) {
              var joinEntity = searchMeta.getEntity(idField.fk_entity),
                label = (idField.join ? idField.join.label + ': ' : '') + (idField.input_attrs && idField.input_attrs.label || idField.label);
              _.each(_.cloneDeep(joinEntity && joinEntity.links), function(link) {
                link.join = idFieldName;
                addTitle(link, label);
                links.push(link);
              });
            }
          }
        }
      });
      // Filter links according to usage - add & browse only make sense outside of a row
      return _.filter(links, (link) => ['add', 'browse'].includes(link.action) !== isRow);
    };

    function loadAfforms() {
      ctrl.afforms = null;
      if (ctrl.afformEnabled && ctrl.savedSearch.id) {
        var findDisplays = _.transform(ctrl.savedSearch.displays, function(findDisplays, display) {
          if (display.id && display.name) {
            findDisplays.push(['search_displays', 'CONTAINS', ctrl.savedSearch.name + '.' + display.name]);
          }
        }, [['search_displays', 'CONTAINS', ctrl.savedSearch.name]]);
        afformLoad = crmApi4('Afform', 'get', {
          select: ['name', 'title', 'search_displays'],
          where: [['OR', findDisplays]]
        }).then(function(afforms) {
          ctrl.afforms = [];
          _.each(afforms, function(afform) {
            ctrl.afforms.push({
              title: afform.title,
              displays: afform.search_displays,
              link: ctrl.afformAdminEnabled ? CRM.url('civicrm/admin/afform#/edit/' + afform.name) : '',
            });
          });
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
