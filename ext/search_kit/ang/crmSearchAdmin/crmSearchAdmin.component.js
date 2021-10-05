(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('crmSearchAdmin', {
    bindings: {
      savedSearch: '<'
    },
    templateUrl: '~/crmSearchAdmin/crmSearchAdmin.html',
    controller: function($scope, $element, $location, $timeout, crmApi4, dialogService, searchMeta) {
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this,
        afformLoad,
        fieldsForJoinGetters = {};

      this.DEFAULT_AGGREGATE_FN = 'GROUP_CONCAT';
      this.afformEnabled = CRM.crmSearchAdmin.afformEnabled;
      this.afformAdminEnabled = CRM.crmSearchAdmin.afformAdminEnabled;
      this.displayTypes = _.indexBy(CRM.crmSearchAdmin.displayTypes, 'id');

      $scope.controls = {tab: 'compose', joinType: 'LEFT'};
      $scope.joinTypes = [
        {k: 'LEFT', v: ts('With (optional)')},
        {k: 'INNER', v: ts('With (required)')},
        {k: 'EXCLUDE', v: ts('Without')},
      ];
      $scope.getEntity = searchMeta.getEntity;
      $scope.getField = searchMeta.getField;
      this.perm = {
        editGroups: CRM.checkPerm('edit groups')
      };

      this.$onInit = function() {
        this.entityTitle = searchMeta.getEntity(this.savedSearch.api_entity).title_plural;

        this.savedSearch.displays = this.savedSearch.displays || [];
        this.savedSearch.groups = this.savedSearch.groups || [];
        this.savedSearch.tag_id = this.savedSearch.tag_id || [];
        this.groupExists = !!this.savedSearch.groups.length;

        if (!this.savedSearch.id) {
          $scope.$bindToRoute({
            param: 'params',
            expr: '$ctrl.savedSearch.api_params',
            deep: true,
            default: {
              version: 4,
              select: getDefaultSelect(),
              orderBy: {},
              where: [],
            }
          });
        }

        $scope.mainEntitySelect = searchMeta.getPrimaryAndSecondaryEntitySelect();

        $scope.$watchCollection('$ctrl.savedSearch.api_params.select', onChangeSelect);

        if (this.paramExists('groupBy')) {
          this.savedSearch.api_params.groupBy = this.savedSearch.api_params.groupBy || [];
        }

        if (this.paramExists('join')) {
          this.savedSearch.api_params.join = this.savedSearch.api_params.join || [];
        }

        if (this.paramExists('having')) {
          this.savedSearch.api_params.having = this.savedSearch.api_params.having || [];
        }

        $scope.$watch('$ctrl.savedSearch', onChangeAnything, true);

        // After watcher runs for the first time and messes up the status, set it correctly
        $timeout(function() {
          $scope.status = ctrl.savedSearch && ctrl.savedSearch.id ? 'saved' : 'unsaved';
        });

        loadFieldOptions();
        loadAfforms();
      };

      function onChangeAnything() {
        $scope.status = 'unsaved';
      }

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
          chain.displays = ['SearchDisplay', 'replace', {where: [['saved_search_id', '=', '$id']], records: params.displays}];
        } else if (params.id) {
          apiCalls.deleteDisplays = ['SearchDisplay', 'delete', {where: [['saved_search_id', '=', params.id]]}];
        }
        delete params.displays;
        if (params.tag_id && params.tag_id.length) {
          chain.tag_id = ['EntityTag', 'replace', {
            where: [['entity_id', '=', '$id'], ['entity_table', '=', 'civicrm_saved_search']],
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
          // Wait until after onChangeAnything to update status
          $timeout(function() {
            $scope.status = newStatus;
          });
        });
      };

      this.paramExists = function(param) {
        return _.includes(searchMeta.getEntity(ctrl.savedSearch.api_entity).params, param);
      };

      this.addDisplay = function(type) {
        ctrl.savedSearch.displays.push({
          type: type,
          label: ''
        });
        $scope.selectTab('display_' + (ctrl.savedSearch.displays.length - 1));
      };

      this.removeDisplay = function(index) {
        var display = ctrl.savedSearch.displays[index];
        if (display.id) {
          display.trashed = !display.trashed;
          if ($scope.controls.tab === ('display_' + index) && display.trashed) {
            $scope.selectTab('compose');
          } else if (!display.trashed) {
            $scope.selectTab('display_' + index);
          }
          if (display.trashed && afformLoad) {
            afformLoad.then(function() {
              if (ctrl.afforms[display.name]) {
                var msg = ctrl.afforms[display.name].length === 1 ?
                  ts('Form "%1" will be deleted if the embedded display "%2" is deleted.', {1: ctrl.afforms[display.name][0].title, 2: display.label}) :
                  ts('%1 forms will be deleted if the embedded display "%2" is deleted.', {1: ctrl.afforms[display.name].length, 2: display.label});
                CRM.alert(msg, ts('Display embedded'), 'alert');
              }
            });
          }
        } else {
          $scope.selectTab('compose');
          ctrl.savedSearch.displays.splice(index, 1);
        }
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
          $scope.smartGroupColumns = searchMeta.getSmartGroupColumns(ctrl.savedSearch.api_entity, ctrl.savedSearch.api_params);
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
          $scope.selectTab('compose');
        }
      };

      function addNum(name, num) {
        return name + (num < 10 ? '_0' : '_') + num;
      }

      function getExistingJoins() {
        return _.transform(ctrl.savedSearch.api_params.join || [], function(joins, join) {
          joins[join[0].split(' AS ')[1]] = searchMeta.getJoin(join[0]);
        }, {});
      }

      $scope.getJoin = searchMeta.getJoin;

      $scope.getJoinEntities = function() {
        var existingJoins = getExistingJoins();

        function addEntityJoins(entity, stack, baseEntity) {
          return _.transform(CRM.crmSearchAdmin.joins[entity], function(joinEntities, join) {
            var num = 0;
            // Add all joins that don't just point directly back to the original entity
            if (!(baseEntity === join.entity && !join.multi)) {
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
            opt.children = addEntityJoins(join.entity, (stack ? stack + '_' : '') + alias, baseEntity);
          }
          collection.push(opt);
        }

        return {results: addEntityJoins(ctrl.savedSearch.api_entity)};
      };

      this.addJoin = function(value) {
        if (value) {
          ctrl.savedSearch.api_params.join = ctrl.savedSearch.api_params.join || [];
          var join = searchMeta.getJoin(value),
            entity = searchMeta.getEntity(join.entity),
            params = [value, $scope.controls.joinType || 'LEFT'];
          _.each(_.cloneDeep(join.conditions), function(condition) {
            params.push(condition);
          });
          _.each(_.cloneDeep(join.defaults), function(condition) {
            params.push(condition);
          });
          ctrl.savedSearch.api_params.join.push(params);
          if (entity.label_field && $scope.controls.joinType !== 'EXCLUDE') {
            ctrl.savedSearch.api_params.select.push(join.alias + '.' + entity.label_field);
          }
          loadFieldOptions();
        }
      };

      // Remove an explicit join + all SELECT, WHERE & other JOINs that use it
      this.removeJoin = function(index) {
        var alias = searchMeta.getJoin(ctrl.savedSearch.api_params.join[index][0]).alias;
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
          var joinAlias = searchMeta.getJoin(item[0]).alias;
          if (joinAlias !== alias && joinAlias.indexOf(alias) === 0) {
            ctrl.removeJoin(i);
          }
        });
      }

      this.changeJoinType = function(join) {
        if (join[1] === 'EXCLUDE') {
          removeJoinStuff(searchMeta.getJoin(join[0]).alias);
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
          if (ctrl.canAggregate(col)) {
            // Ensure all non-grouped columns are aggregated if using GROUP BY
            if (!info.fn || info.fn.category !== 'aggregate') {
              ctrl.savedSearch.api_params.select[pos] = ctrl.DEFAULT_AGGREGATE_FN + '(DISTINCT ' + fieldExpr + ') AS ' + ctrl.DEFAULT_AGGREGATE_FN + '_' + fieldExpr.replace(/[.:]/g, '_');
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
        if (name === 'select') {
          // Function selectors use `ng-repeat` with `track by $index` so must be refreshed when splicing the array
          ctrl.hideFuncitons();
        }
        ctrl.savedSearch.api_params[name].splice(idx, 1);
      };

      this.hideFuncitons = function() {
        $scope.controls.showFunctions = false;
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
        // If the query does not use grouping, never
        if (!ctrl.savedSearch.api_params.groupBy.length) {
          return false;
        }
        var arg = _.findWhere(searchMeta.parseExpr(col).args, {type: 'field'}) || {};
        // If the column is not a database field, no
        if (!arg.field || !arg.field.entity || arg.field.type !== 'Field') {
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
        return {results: ctrl.getAllFields('', ['Field', 'Custom'], function(key) {
            return _.contains(ctrl.savedSearch.api_params.groupBy, key);
          })
        };
      };

      function getFieldsForJoin(joinEntity) {
        return {results: ctrl.getAllFields(':name', ['Field'], null, joinEntity)};
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

      // Sets the default select clause based on commonly-named fields
      function getDefaultSelect() {
        var entity = searchMeta.getEntity(ctrl.savedSearch.api_entity);
        return _.transform(entity.fields, function(defaultSelect, field) {
          if (field.name === 'id' || field.name === entity.label_field) {
            defaultSelect.push(field.name);
          }
        });
      }

      this.getAllFields = function(suffix, allowedTypes, disabledIf, topJoin) {
        disabledIf = disabledIf || _.noop;

        function formatEntityFields(entityName, join) {
          var prefix = join ? join.alias + '.' : '',
            result = [];

          // Add extra searchable fields from bridge entity
          if (join && join.bridge) {
            formatFields(_.filter(searchMeta.getEntity(join.bridge).fields, function(field) {
              return (field.name !== 'id' && field.name !== 'entity_id' && field.name !== 'entity_table' && !field.fk_entity);
            }), result, prefix);
          }

          formatFields(searchMeta.getEntity(entityName).fields, result, prefix);
          return result;
        }

        function formatFields(fields, result, prefix) {
          prefix = typeof prefix === 'undefined' ? '' : prefix;
          _.each(fields, function(field) {
            var item = {
              id: prefix + field.name + (field.options ? suffix : ''),
              text: field.label,
              description: field.description
            };
            if (disabledIf(item.id)) {
              item.disabled = true;
            }
            if (!allowedTypes || _.includes(allowedTypes, field.type)) {
              result.push(item);
            }
          });
          return result;
        }

        var mainEntity = searchMeta.getEntity(ctrl.savedSearch.api_entity),
          joinEntities = _.map(ctrl.savedSearch.api_params.join, 0),
          result = [];

        function addJoin(join) {
          var joinInfo = searchMeta.getJoin(join),
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
        if (allowedTypes && _.includes(allowedTypes, 'Pseudo')) {
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

      /**
       * Fetch pseudoconstants for main entity + joined entities
       *
       * Sets an optionsLoaded property on each entity to avoid duplicate requests
       *
       * @var string entity - optional additional entity to load
       */
      function loadFieldOptions(entity) {
        var mainEntity = searchMeta.getEntity(ctrl.savedSearch.api_entity),
          entities = {};

        function enqueue(entity) {
          entity.optionsLoaded = false;
          entities[entity.name] = [entity.name, 'getFields', {
            loadOptions: ['id', 'name', 'label', 'description', 'color', 'icon'],
            where: [['options', '!=', false]],
            select: ['options']
          }, {name: 'options'}];
        }

        if (typeof mainEntity.optionsLoaded === 'undefined') {
          enqueue(mainEntity);
        }

        // Optional additional entity
        if (entity && typeof searchMeta.getEntity(entity).optionsLoaded === 'undefined') {
          enqueue(searchMeta.getEntity(entity));
        }

        _.each(ctrl.savedSearch.api_params.join, function(join) {
          var joinInfo = searchMeta.getJoin(join[0]),
            joinEntity = searchMeta.getEntity(joinInfo.entity),
            bridgeEntity = joinInfo.bridge ? searchMeta.getEntity(joinInfo.bridge) : null;
          if (typeof joinEntity.optionsLoaded === 'undefined') {
            enqueue(joinEntity);
          }
          if (bridgeEntity && typeof bridgeEntity.optionsLoaded === 'undefined') {
            enqueue(bridgeEntity);
          }
        });
        if (!_.isEmpty(entities)) {
          crmApi4(entities).then(function(results) {
            _.each(results, function(fields, entityName) {
              var entity = searchMeta.getEntity(entityName);
              _.each(fields, function(options, fieldName) {
                _.find(entity.fields, {name: fieldName}).options = options;
              });
              entity.optionsLoaded = true;
            });
          });
        }
      }

      // Build a list of all possible links to main entity & join entities
      this.buildLinks = function() {
        function addTitle(link, entityName) {
          switch (link.action) {
            case 'view':
              link.title = ts('View %1', {1: entityName});
              link.icon = 'fa-external-link';
              link.style = 'default';
              break;

            case 'update':
              link.title = ts('Edit %1', {1: entityName});
              link.icon = 'fa-pencil';
              link.style = 'default';
              break;

            case 'delete':
              link.title = ts('Delete %1', {1: entityName});
              link.icon = 'fa-trash';
              link.style = 'danger';
              break;
          }
        }

        // Links to main entity
        // @return {Array}
        var mainEntity = searchMeta.getEntity(ctrl.savedSearch.api_entity),
          links = _.cloneDeep(mainEntity.paths || []);
        _.each(links, function(link) {
          link.join = '';
          addTitle(link, mainEntity.title);
        });
        // Links to explicitly joined entities
        _.each(ctrl.savedSearch.api_params.join, function(joinClause) {
          var join = searchMeta.getJoin(joinClause[0]),
            joinEntity = searchMeta.getEntity(join.entity),
            primaryKey = joinEntity.primary_key[0],
            // Links for aggregate columns get aggregated using GROUP_CONCAT
            isAggregate = ctrl.canAggregate(join.alias + '.' + primaryKey),
            joinPrefix = (isAggregate ? ctrl.DEFAULT_AGGREGATE_FN + '_' : '') + join.alias + '.',
            bridgeEntity = _.isString(joinClause[2]) ? searchMeta.getEntity(joinClause[2]) : null;
          _.each(joinEntity.paths, function(path) {
            var link = _.cloneDeep(path);
            link.path = link.path.replace(/\[/g, '[' + joinPrefix);
            if (isAggregate) {
              link.path = link.path.replace(/[.:]/g, '_');
            }
            link.join = join.alias;
            addTitle(link, join.label);
            links.push(link);
          });
          _.each(bridgeEntity && bridgeEntity.paths, function(path) {
            var link = _.cloneDeep(path);
            link.path = link.path.replace(/\[/g, '[' + join.alias + '.');
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
              if (!ctrl.canAggregate(idFieldName)) {
                var joinEntity = searchMeta.getEntity(idField.fk_entity),
                  label = (idField.join ? idField.join.label + ': ' : '') + (idField.input_attrs && idField.input_attrs.label || idField.label);
                _.each((joinEntity || {}).paths, function(path) {
                  var link = _.cloneDeep(path);
                  link.path = link.path.replace(/\[id/g, '[' + idFieldName);
                  link.join = idFieldName;
                  addTitle(link, label);
                  links.push(link);
                });
              }
            }
          }
        });
        return _.uniq(links, 'path');
      };

      function loadAfforms() {
        if (ctrl.afformEnabled && ctrl.savedSearch.id) {
          var findDisplays = _.transform(ctrl.savedSearch.displays, function(findDisplays, display) {
            if (display.id && display.name) {
              findDisplays.push(['search_displays', 'CONTAINS', ctrl.savedSearch.name + '.' + display.name]);
            }
          }, []);
          if (findDisplays.length) {
            afformLoad = crmApi4('Afform', 'get', {
              select: ['name', 'title', 'search_displays'],
              where: [['OR', findDisplays]]
            }).then(function(afforms) {
              ctrl.afforms = {};
              _.each(afforms, function(afform) {
                _.each(_.uniq(afform.search_displays), function(searchNameDisplayName) {
                  var displayName = searchNameDisplayName.split('.')[1];
                  ctrl.afforms[displayName] = ctrl.afforms[displayName] || [];
                  ctrl.afforms[displayName].push({
                    title: afform.title,
                    link: ctrl.afformAdminEnabled ? CRM.url('civicrm/admin/afform#/edit/' + afform.name) : '',
                  });
                });
              });
            });
          }
        }
      }

      // Creating an Afform opens a new tab, so when switching back to this tab, re-check for Afforms
      $(window).on('focus', _.debounce(function() {
        $scope.$apply(loadAfforms);
      }, 10000, {leading: true, trailing: false}));

    }
  });

})(angular, CRM.$, CRM._);
