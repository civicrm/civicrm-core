(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('crmSearchAdmin', {
    bindings: {
      savedSearch: '<'
    },
    templateUrl: '~/crmSearchAdmin/crmSearchAdmin.html',
    controller: function($scope, $element, $location, $timeout, crmApi4, dialogService, searchMeta, formatForSelect2) {
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this,
        fieldsForJoinGetters = {};

      this.DEFAULT_AGGREGATE_FN = 'GROUP_CONCAT';

      this.selectedRows = [];
      this.limit = CRM.crmSearchAdmin.defaultPagerSize;
      this.page = 1;
      this.displayTypes = _.indexBy(CRM.crmSearchAdmin.displayTypes, 'id');
      // After a search this.results is an object of result arrays keyed by page,
      // Initially this.results is an empty string because 1: it's falsey (unlike an empty object) and 2: it doesn't throw an error if you try to access undefined properties (unlike null)
      this.results = '';
      this.rowCount = false;
      this.allRowsSelected = false;
      // Have the filters (WHERE, HAVING, GROUP BY, JOIN) changed?
      this.stale = true;

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

        var primaryEntities = _.filter(CRM.crmSearchAdmin.schema, {searchable: 'primary'}),
          secondaryEntities = _.filter(CRM.crmSearchAdmin.schema, {searchable: 'secondary'});
        $scope.mainEntitySelect = formatForSelect2(primaryEntities, 'name', 'title_plural', ['description', 'icon']);
        $scope.mainEntitySelect.push({
          text: ts('More...'),
          description: ts('Other less-commonly searched entities'),
          children: formatForSelect2(secondaryEntities, 'name', 'title_plural', ['description', 'icon'])
        });

        $scope.$watchCollection('$ctrl.savedSearch.api_params.select', onChangeSelect);

        $scope.$watch('$ctrl.savedSearch.api_params.where', onChangeFilters, true);

        if (this.paramExists('groupBy')) {
          this.savedSearch.api_params.groupBy = this.savedSearch.api_params.groupBy || [];
          $scope.$watchCollection('$ctrl.savedSearch.api_params.groupBy', onChangeFilters);
        }

        if (this.paramExists('join')) {
          this.savedSearch.api_params.join = this.savedSearch.api_params.join || [];
          $scope.$watch('$ctrl.savedSearch.api_params.join', onChangeFilters, true);
        }

        if (this.paramExists('having')) {
          this.savedSearch.api_params.having = this.savedSearch.api_params.having || [];
          $scope.$watch('$ctrl.savedSearch.api_params.having', onChangeFilters, true);
        }

        $scope.$watch('$ctrl.savedSearch', onChangeAnything, true);

        // After watcher runs for the first time and messes up the status, set it correctly
        $timeout(function() {
          $scope.status = ctrl.savedSearch && ctrl.savedSearch.id ? 'saved' : 'unsaved';
        });

        loadFieldOptions();
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
        if (params.displays && params.displays.length) {
          chain.displays = ['SearchDisplay', 'replace', {where: [['saved_search_id', '=', '$id']], records: params.displays}];
        } else if (params.id) {
          apiCalls.deleteDisplays = ['SearchDisplay', 'delete', {where: [['saved_search_id', '=', params.id]]}];
        }
        delete params.displays;
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

      $scope.addJoin = function() {
        // Debounce the onchange event using timeout
        $timeout(function() {
          if ($scope.controls.join) {
            ctrl.savedSearch.api_params.join = ctrl.savedSearch.api_params.join || [];
            var join = searchMeta.getJoin($scope.controls.join),
              entity = searchMeta.getEntity(join.entity),
              params = [$scope.controls.join, $scope.controls.joinType || 'LEFT'];
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
          $scope.controls.join = '';
        });
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
        if (!ctrl.savedSearch.api_params.groupBy[idx]) {
          ctrl.clearParam('groupBy', idx);
        }
        // Remove aggregate functions when no grouping
        if (!ctrl.savedSearch.api_params.groupBy.length) {
          _.each(ctrl.savedSearch.api_params.select, function(col, pos) {
            if (_.contains(col, '(')) {
              var info = searchMeta.parseExpr(col);
              if (info.fn.category === 'aggregate') {
                ctrl.savedSearch.api_params.select[pos] = info.path + info.suffix;
              }
            }
          });
        }
      };

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

      /**
       * Called when clicking on a column header
       * @param col
       * @param $event
       */
      $scope.setOrderBy = function(col, $event) {
        col = _.last(col.split(' AS '));
        var dir = $scope.getOrderBy(col) === 'fa-sort-asc' ? 'DESC' : 'ASC';
        if (!$event.shiftKey || !ctrl.savedSearch.api_params.orderBy) {
          ctrl.savedSearch.api_params.orderBy = {};
        }
        ctrl.savedSearch.api_params.orderBy[col] = dir;
        if (ctrl.results) {
          ctrl.refreshPage();
        }
      };

      /**
       * Returns crm-i icon class for a sortable column
       * @param col
       * @returns {string}
       */
      $scope.getOrderBy = function(col) {
        col = _.last(col.split(' AS '));
        var dir = ctrl.savedSearch.api_params.orderBy && ctrl.savedSearch.api_params.orderBy[col];
        if (dir) {
          return 'fa-sort-' + dir.toLowerCase();
        }
        return 'fa-sort disabled';
      };

      $scope.addParam = function(name) {
        if ($scope.controls[name] && !_.contains(ctrl.savedSearch.api_params[name], $scope.controls[name])) {
          ctrl.savedSearch.api_params[name].push($scope.controls[name]);
          if (name === 'groupBy') {
            // Expand the aggregate block
            $timeout(function() {
              $('#crm-search-build-group-aggregate.collapsed .collapsible-title').click();
            }, 10);
          }
        }
        $scope.controls[name] = '';
      };

      // Deletes an item from an array param
      this.clearParam = function(name, idx) {
        ctrl.savedSearch.api_params[name].splice(idx, 1);
      };

      // Prevent visual jumps in results table height during loading
      function lockTableHeight() {
        var $table = $('.crm-search-results', $element);
        $table.css('height', $table.height());
      }

      function unlockTableHeight() {
        $('.crm-search-results', $element).css('height', '');
      }

      // Ensure all non-grouped columns are aggregated if using GROUP BY
      function aggregateGroupByColumns() {
        if (ctrl.savedSearch.api_params.groupBy.length) {
          _.each(ctrl.savedSearch.api_params.select, function(col, pos) {
            if (!_.contains(col, '(') && ctrl.canAggregate(col)) {
              ctrl.savedSearch.api_params.select[pos] = ctrl.DEFAULT_AGGREGATE_FN + '(DISTINCT ' + col + ') AS ' + ctrl.DEFAULT_AGGREGATE_FN + '_DISTINCT_' + col.replace(/[.:]/g, '_');
            }
          });
        }
      }

      // Debounced callback for loadResults
      function _loadResultsCallback() {
        // Multiply limit to read 2 pages at once & save ajax requests
        var params = _.merge(_.cloneDeep(ctrl.savedSearch.api_params), {debug: true, limit: ctrl.limit * 2});
        // Select the ids of implicitly joined entities (helps with displaying links)
        _.each(params.select, function(fieldName) {
          if (_.includes(fieldName, '.') && !_.includes(fieldName, ' AS ')) {
            var info = searchMeta.parseExpr(fieldName);
            if (info.field && !info.suffix && !info.fn && (info.field.name !== info.field.fieldName)) {
              var idField = fieldName.substr(0, fieldName.lastIndexOf('.'));
              if (!_.includes(params.select, idField) && !ctrl.canAggregate(idField)) {
                params.select.push(idField);
              }
            }
          }
        });
        // Select the ids of explicitly joined entities (helps with displaying links)
        _.each(params.join, function(join) {
          var idField = join[0].split(' AS ')[1] + '.id';
          if (!_.includes(params.select, idField) && !ctrl.canAggregate(idField)) {
            params.select.push(idField);
          }
        });
        lockTableHeight();
        $scope.error = false;
        if (ctrl.stale) {
          ctrl.page = 1;
          ctrl.rowCount = false;
        }
        params.offset = ctrl.limit * (ctrl.page - 1);
        crmApi4(ctrl.savedSearch.api_entity, 'get', params).then(function(success) {
          if (ctrl.stale) {
            ctrl.results = {};
            // Get row count for pager
            if (success.length < params.limit) {
              ctrl.rowCount = success.count;
            } else {
              var countParams = _.cloneDeep(params);
              // Select is only needed needed by HAVING
              countParams.select = countParams.having && countParams.having.length ? countParams.select : [];
              countParams.select.push('row_count');
              delete countParams.debug;
              crmApi4(ctrl.savedSearch.api_entity, 'get', countParams).then(function(result) {
                ctrl.rowCount = result.count;
              });
            }
          }
          ctrl.debug = success.debug;
          // populate this page & the next
          ctrl.results[ctrl.page] = success.slice(0, ctrl.limit);
          if (success.length > ctrl.limit) {
            ctrl.results[ctrl.page + 1] = success.slice(ctrl.limit);
          }
          $scope.loading = false;
          ctrl.stale = false;
          unlockTableHeight();
        }, function(error) {
          $scope.loading = false;
          ctrl.results = {};
          ctrl.stale = true;
          ctrl.debug = error.debug;
          $scope.error = errorMsg(error);
        })
          .finally(function() {
            if (ctrl.debug) {
              ctrl.debug.params = JSON.stringify(params, null, 2);
              if (ctrl.debug.timeIndex) {
                ctrl.debug.timeIndex = Number.parseFloat(ctrl.debug.timeIndex).toPrecision(2);
              }
            }
          });
      }

      var _loadResults = _.debounce(_loadResultsCallback, 250);

      function loadResults() {
        $scope.loading = true;
        aggregateGroupByColumns();
        _loadResults();
      }

      // What to tell the user when search returns an error from the server
      // Todo: parse error codes and give helpful feedback.
      function errorMsg(error) {
        return ts('Ensure all search critera are set correctly and try again.');
      }

      this.changePage = function() {
        if (ctrl.stale || !ctrl.results[ctrl.page]) {
          lockTableHeight();
          loadResults();
        }
      };

      this.refreshAll = function() {
        ctrl.stale = true;
        clearSelection();
        loadResults();
      };

      // Refresh results while staying on current page.
      this.refreshPage = function() {
        lockTableHeight();
        ctrl.results = {};
        loadResults();
      };

      $scope.onClickSearch = function() {
        if (ctrl.autoSearch) {
          ctrl.autoSearch = false;
        } else {
          ctrl.refreshAll();
        }
      };

      $scope.onClickAuto = function() {
        ctrl.autoSearch = !ctrl.autoSearch;
        if (ctrl.autoSearch && ctrl.stale) {
          ctrl.refreshAll();
        }
        $('.crm-search-auto-toggle').blur();
      };

      $scope.onChangeLimit = function() {
        // Refresh only if search has already been run
        if (ctrl.autoSearch || ctrl.results) {
          ctrl.refreshAll();
        }
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
        // Re-arranging or removing columns doesn't merit a refresh, only adding columns does
        if (!oldSelect || _.difference(newSelect, oldSelect).length) {
          if (ctrl.autoSearch) {
            ctrl.refreshPage();
          } else {
            ctrl.stale = true;
          }
        }
      }

      function onChangeFilters() {
        ctrl.stale = true;
        clearSelection();
        if (ctrl.autoSearch) {
          ctrl.refreshAll();
        }
      }

      function clearSelection() {
        ctrl.allRowsSelected = false;
        ctrl.selectedRows.length = 0;
      }

      $scope.selectAllRows = function() {
        // Deselect all
        if (ctrl.allRowsSelected) {
          clearSelection();
          return;
        }
        // Select all
        ctrl.allRowsSelected = true;
        if (ctrl.page === 1 && ctrl.results[1].length < ctrl.limit) {
          ctrl.selectedRows = _.pluck(ctrl.results[1], 'id');
          return;
        }
        // If more than one page of results, use ajax to fetch all ids
        $scope.loadingAllRows = true;
        var params = _.cloneDeep(ctrl.savedSearch.api_params);
        // Select is only needed needed by HAVING
        params.select = params.having && params.having.length ? params.select : [];
        params.select.push('id');
        crmApi4(ctrl.savedSearch.api_entity, 'get', params, ['id']).then(function(ids) {
          $scope.loadingAllRows = false;
          ctrl.selectedRows = _.toArray(ids);
        });
      };

      $scope.selectRow = function(row) {
        var index = ctrl.selectedRows.indexOf(row.id);
        if (index < 0) {
          ctrl.selectedRows.push(row.id);
          ctrl.allRowsSelected = (ctrl.rowCount === ctrl.selectedRows.length);
        } else {
          ctrl.allRowsSelected = false;
          ctrl.selectedRows.splice(index, 1);
        }
      };

      $scope.isRowSelected = function(row) {
        return ctrl.allRowsSelected || _.includes(ctrl.selectedRows, row.id);
      };

      this.getFieldLabel = searchMeta.getDefaultLabel;

      // Is a column eligible to use an aggregate function?
      this.canAggregate = function(col) {
        // If the query does not use grouping, never
        if (!ctrl.savedSearch.api_params.groupBy.length) {
          return false;
        }
        var info = searchMeta.parseExpr(col);
        // If the column is used for a groupBy, no
        if (ctrl.savedSearch.api_params.groupBy.indexOf(info.path) > -1) {
          return false;
        }
        // If the entity this column belongs to is being grouped by id, then also no
        return ctrl.savedSearch.api_params.groupBy.indexOf(info.prefix + 'id') < 0;
      };

      $scope.formatResult = function(row, col) {
        var info = searchMeta.parseExpr(col),
          value = row[info.alias];
        if (info.fn && info.fn.name === 'COUNT') {
          return value;
        }
        // Output user-facing name/label fields as a link, if possible
        if (info.field && info.field.fieldName === searchMeta.getEntity(info.field.entity).label_field && !info.fn && typeof value === 'string') {
          var link = getEntityUrl(row, info);
          if (link) {
            return '<a href="' + _.escape(link.url) + '" title="' + _.escape(link.title) + '">' + formatFieldValue(info.field, value) + '</a>';
          }
        }
        return formatFieldValue(info.field, value);
      };

      // Attempts to construct a view url for a given entity
      function getEntityUrl(row, info) {
        var entity = searchMeta.getEntity(info.field.entity),
          path = _.result(_.findWhere(entity.paths, {action: 'view'}), 'path');
        // Only proceed if the path metadata exists for this entity
        if (path) {
          // Replace tokens in the path (e.g. [id])
          var tokens = path.match(/\[\w*]/g) || [],
            prefix = info.prefix;
          var replacements = _.transform(tokens, function(replacements, token) {
            var fieldName = token.slice(1, token.length - 1);
            // For implicit join fields
            if (fieldName === 'id' && info.field.name !== info.field.fieldName) {
              fieldName = info.field.name.substr(0, info.field.name.lastIndexOf('.'));
            }
            fieldName = prefix + fieldName;
            if (row[fieldName]) {
              replacements.push(row[fieldName]);
            }
          });
          // Only proceed if the row contains all the necessary data to resolve tokens
          if (tokens.length === replacements.length) {
            _.each(tokens, function(token, index) {
              path = path.replace(token, replacements[index]);
            });
            return {url: CRM.url(path), title: path.title};
          }
        }
      }

      function formatFieldValue(field, value) {
        var type = field.data_type,
          result = value;
        if (_.isArray(value)) {
          return _.map(value, function(val) {
            return formatFieldValue(field, val);
          }).join(', ');
        }
        if (value && (type === 'Date' || type === 'Timestamp') && /^\d{4}-\d{2}-\d{2}/.test(value)) {
          result = CRM.utils.formatDate(value, null, type === 'Timestamp');
        }
        else if (type === 'Boolean' && typeof value === 'boolean') {
          result = value ? ts('Yes') : ts('No');
        }
        else if (type === 'Money' && typeof value === 'number') {
          result = CRM.formatMoney(value);
        }
        return _.escape(result);
      }

      $scope.fieldsForGroupBy = function() {
        return {results: ctrl.getAllFields('', ['Field', 'Custom'], function(key) {
            return _.contains(ctrl.savedSearch.api_params.groupBy, key);
          })
        };
      };

      $scope.fieldsForSelect = function() {
        return {results: ctrl.getAllFields(':label', ['Field', 'Custom', 'Extra'], function(key) {
            return _.contains(ctrl.savedSearch.api_params.select, key);
          })
        };
      };

      function getFieldsForJoin(joinEntity) {
        return {results: ctrl.getAllFields(':name', ['Field', 'Custom'], null, joinEntity)};
      }

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

      $scope.sortableColumnOptions = {
        axis: 'x',
        handle: '.crm-draggable',
        update: function(e, ui) {
          // Don't allow items to be moved to position 0 if locked
          if (!ui.item.sortable.dropindex && ctrl.groupExists) {
            ui.item.sortable.cancel();
          }
        }
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
        function formatFields(entityName, join) {
          var prefix = join ? join.alias + '.' : '',
            result = [];

          function addFields(fields) {
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
          }

          // Add extra searchable fields from bridge entity
          if (join && join.bridge) {
            addFields(_.filter(searchMeta.getEntity(join.bridge).fields, function(field) {
              return (field.name !== 'id' && field.name !== 'entity_id' && field.name !== 'entity_table' && !field.fk_entity && !_.includes(field.name, '.'));
            }));
          }

          addFields(searchMeta.getEntity(entityName).fields);
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
            children: formatFields(joinEntity.name, joinInfo)
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
          children: formatFields(ctrl.savedSearch.api_entity)
        });
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
            description: info.field && info.field.description
          };
          if (disabledIf(item.id)) {
            item.disabled = true;
          }
          fields.push(item);
        });
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

    }
  });

})(angular, CRM.$, CRM._);
