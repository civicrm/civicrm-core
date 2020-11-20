(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('crmSearchAdmin', {
    bindings: {
      savedSearch: '<'
    },
    templateUrl: '~/crmSearchAdmin/crmSearchAdmin.html',
    controller: function($scope, $element, $location, $timeout, crmApi4, dialogService, searchMeta, formatForSelect2) {
      var ts = $scope.ts = CRM.ts(),
        ctrl = this;

      this.DEFAULT_AGGREGATE_FN = 'GROUP_CONCAT';

      this.selectedRows = [];
      this.limit = CRM.cache.get('searchPageSize', 30);
      this.page = 1;
      this.displayTypes = _.indexBy(CRM.crmSearchAdmin.displayTypes, 'name');
      // After a search this.results is an object of result arrays keyed by page,
      // Initially this.results is an empty string because 1: it's falsey (unlike an empty object) and 2: it doesn't throw an error if you try to access undefined properties (unlike null)
      this.results = '';
      this.rowCount = false;
      this.allRowsSelected = false;
      // Have the filters (WHERE, HAVING, GROUP BY, JOIN) changed?
      this.stale = true;

      $scope.controls = {tab: 'compose'};
      $scope.joinTypes = [{k: false, v: ts('Optional')}, {k: true, v: ts('Required')}];
      $scope.groupOptions = CRM.crmSearchActions.groupOptions;
      $scope.entities = formatForSelect2(CRM.vars.search.schema, 'name', 'title_plural', ['description', 'icon']);
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
        if (!ctrl.groupExists && (!ctrl.savedSearch.groups.length || !ctrl.savedSearch.groups[0].id)) {
          ctrl.savedSearch.groups.length = 0;
        }
        if ($scope.controls.tab === 'group') {
          $scope.selectTab('compose');
        }
      };

      $scope.getJoinEntities = function() {
        var joinEntities = _.transform(CRM.vars.search.links[ctrl.savedSearch.api_entity], function(joinEntities, link) {
          var entity = searchMeta.getEntity(link.entity);
          if (entity) {
            joinEntities.push({
              id: link.entity + ' AS ' + link.alias,
              text: entity.title_plural,
              description: '(' + link.alias + ')',
              icon: entity.icon
            });
          }
        }, []);
        return {results: joinEntities};
      };

      $scope.addJoin = function() {
        // Debounce the onchange event using timeout
        $timeout(function() {
          if ($scope.controls.join) {
            ctrl.savedSearch.api_params.join = ctrl.savedSearch.api_params.join || [];
            ctrl.savedSearch.api_params.join.push([$scope.controls.join, false]);
            loadFieldOptions();
          }
          $scope.controls.join = '';
        });
      };

      $scope.changeJoin = function(idx) {
        if (ctrl.savedSearch.api_params.join[idx][0]) {
          ctrl.savedSearch.api_params.join[idx].length = 2;
          loadFieldOptions();
        } else {
          ctrl.clearParam('join', idx);
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
              ctrl.savedSearch.api_params.select[pos] = ctrl.DEFAULT_AGGREGATE_FN + '(' + col + ')';
            }
          });
        }
      }

      // Debounced callback for loadResults
      function _loadResultsCallback() {
        // Multiply limit to read 2 pages at once & save ajax requests
        var params = _.merge(_.cloneDeep(ctrl.savedSearch.api_params), {debug: true, limit: ctrl.limit * 2});
        // Select the ids of joined entities (helps with displaying links)
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
        if (ctrl.rowCount === false) {
          params.select.push('row_count');
        }
        params.offset = ctrl.limit * (ctrl.page - 1);
        crmApi4(ctrl.savedSearch.api_entity, 'get', params).then(function(success) {
          if (ctrl.stale) {
            ctrl.results = {};
          }
          if (ctrl.rowCount === false) {
            ctrl.rowCount = success.count;
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
        ctrl.selectedRows.length = 0;
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
          // Save page size in localStorage
          CRM.cache.set('searchPageSize', ctrl.limit);
          ctrl.refreshAll();
        }
      };

      function onChangeSelect(newSelect, oldSelect) {
        // When removing a column from SELECT, also remove from ORDER BY
        _.each(_.difference(_.keys(ctrl.savedSearch.api_params.orderBy), newSelect), function(col) {
          delete ctrl.savedSearch.api_params.orderBy[col];
        });
        // Re-arranging or removing columns doesn't merit a refresh, only adding columns does
        if (!oldSelect || _.difference(newSelect, oldSelect).length) {
          if (ctrl.autoSearch) {
            ctrl.refreshPage();
          } else {
            ctrl.stale = true;
          }
        }
        if (ctrl.load) {
          ctrl.saved = false;
        }
      }

      function onChangeFilters() {
        ctrl.stale = true;
        ctrl.selectedRows.length = 0;
        if (ctrl.load) {
          ctrl.saved = false;
        }
        if (ctrl.autoSearch) {
          ctrl.refreshAll();
        }
      }

      $scope.selectAllRows = function() {
        // Deselect all
        if (ctrl.allRowsSelected) {
          ctrl.allRowsSelected = false;
          ctrl.selectedRows.length = 0;
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
        params.select = ['id'];
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
          key = info.fn ? (info.fn.name + ':' + info.path + info.suffix) : col,
          value = row[key];
        if (info.fn && info.fn.name === 'COUNT') {
          return value;
        }
        // Output user-facing name/label fields as a link, if possible
        if (info.field && _.includes(['display_name', 'title', 'label', 'subject'], info.field.name) && !info.fn && typeof value === 'string') {
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
            replacements = _.transform(tokens, function(replacements, token) {
              var fieldName = info.prefix + token.slice(1, token.length - 1);
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
        return {results: getAllFields('', function(key) {
            return _.contains(ctrl.savedSearch.api_params.groupBy, key);
          })
        };
      };

      $scope.fieldsForSelect = function() {
        return {results: getAllFields(':label', function(key) {
            return _.contains(ctrl.savedSearch.api_params.select, key);
          })
        };
      };

      $scope.fieldsForWhere = function() {
        return {results: getAllFields(':name', _.noop)};
      };

      $scope.fieldsForHaving = function() {
        return {results: _.transform(ctrl.savedSearch.api_params.select, function(fields, name) {
          fields.push({id: name, text: ctrl.getFieldLabel(name)});
        })};
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
        var whitelist = ['id', 'name', 'subject', 'display_name', 'label', 'title'];
        return _.transform(searchMeta.getEntity(ctrl.savedSearch.api_entity).fields, function(select, field) {
          if (_.includes(whitelist, field.name) || _.includes(field.name, '_type_id')) {
            select.push(field.name + (field.options ? ':label' : ''));
          }
        });
      }

      function getAllFields(suffix, disabledIf) {
        function formatFields(entityName, prefix) {
          return _.transform(searchMeta.getEntity(entityName).fields, function(result, field) {
            var item = {
              id: prefix + field.name + (field.options ? suffix : ''),
              text: field.label,
              description: field.description
            };
            if (disabledIf(item.id)) {
              item.disabled = true;
            }
            result.push(item);
          }, []);
        }

        var mainEntity = searchMeta.getEntity(ctrl.savedSearch.api_entity),
          result = [{
            text: mainEntity.title_plural,
            icon: mainEntity.icon,
            children: formatFields(ctrl.savedSearch.api_entity, '')
          }];
        _.each(ctrl.savedSearch.api_params.join, function(join) {
          var joinName = join[0].split(' AS '),
            joinEntity = searchMeta.getEntity(joinName[0]);
          result.push({
            text: joinEntity.title_plural + ' (' + joinName[1] + ')',
            icon: joinEntity.icon,
            children: formatFields(joinEntity.name, joinName[1] + '.')
          });
        });
        return result;
      }

      /**
       * Fetch pseudoconstants for main entity + joined entities
       *
       * Sets an optionsLoaded property on each entity to avoid duplicate requests
       */
      function loadFieldOptions() {
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
        _.each(ctrl.savedSearch.api_params.join, function(join) {
          var joinName = join[0].split(' AS '),
            joinEntity = searchMeta.getEntity(joinName[0]);
          if (typeof joinEntity.optionsLoaded === 'undefined') {
            enqueue(joinEntity);
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
