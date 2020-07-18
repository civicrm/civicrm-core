(function(angular, $, _) {
  "use strict";

  angular.module('search').component('crmSearch', {
    bindings: {
      entity: '='
    },
    templateUrl: '~/search/crmSearch.html',
    controller: function($scope, $element, $timeout, crmApi4, dialogService, searchMeta, formatForSelect2) {
      var ts = $scope.ts = CRM.ts(),
        ctrl = this;
      this.selectedRows = [];
      this.limit = CRM.cache.get('searchPageSize', 30);
      this.page = 1;
      this.params = {};
      // After a search this.results is an object of result arrays keyed by page,
      // Prior to searching it's an empty string because 1: falsey and 2: doesn't throw an error if you try to access undefined properties
      this.results = '';
      this.rowCount = false;
      // Have the filters (WHERE, HAVING, GROUP BY, JOIN) changed?
      this.stale = true;
      this.allRowsSelected = false;

      $scope.controls = {};
      $scope.joinTypes = [{k: false, v: ts('Optional')}, {k: true, v: ts('Required')}];
      $scope.entities = formatForSelect2(CRM.vars.search.schema, 'name', 'title', ['description', 'icon']);
      this.perm = {
        editGroups: CRM.checkPerm('edit groups')
      };

      this.getEntity = searchMeta.getEntity;

      this.paramExists = function(param) {
        return _.includes(searchMeta.getEntity(ctrl.entity).params, param);
      };

      $scope.getJoinEntities = function() {
        var joinEntities = _.transform(CRM.vars.search.links[ctrl.entity], function(joinEntities, link) {
          var entity = searchMeta.getEntity(link.entity);
          if (entity) {
            joinEntities.push({
              id: link.entity + ' AS ' + link.alias,
              text: entity.title,
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
            ctrl.params.join = ctrl.params.join || [];
            ctrl.params.join.push([$scope.controls.join, false]);
            loadFieldOptions();
          }
          $scope.controls.join = '';
        });
      };

      $scope.changeJoin = function(idx) {
        if (ctrl.params.join[idx][0]) {
          ctrl.params.join[idx].length = 2;
          loadFieldOptions();
        } else {
          ctrl.clearParam('join', idx);
        }
      };

      $scope.changeGroupBy = function(idx) {
        if (!ctrl.params.groupBy[idx]) {
          ctrl.clearParam('groupBy', idx);
        }
      };

      /**
       * Called when clicking on a column header
       * @param col
       * @param $event
       */
      $scope.setOrderBy = function(col, $event) {
        var dir = $scope.getOrderBy(col) === 'fa-sort-asc' ? 'DESC' : 'ASC';
        if (!$event.shiftKey) {
          ctrl.params.orderBy = {};
        }
        ctrl.params.orderBy[col] = dir;
      };

      /**
       * Returns crm-i icon class for a sortable column
       * @param col
       * @returns {string}
       */
      $scope.getOrderBy = function(col) {
        var dir = ctrl.params.orderBy && ctrl.params.orderBy[col];
        if (dir) {
          return 'fa-sort-' + dir.toLowerCase();
        }
        return 'fa-sort disabled';
      };

      $scope.addParam = function(name) {
        if ($scope.controls[name] && !_.contains(ctrl.params[name], $scope.controls[name])) {
          ctrl.params[name].push($scope.controls[name]);
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
        ctrl.params[name].splice(idx, 1);
      };

      // Prevent visual jumps in results table height during loading
      function lockTableHeight() {
        var $table = $('.crm-search-results', $element);
        $table.css('height', $table.height());
      }

      function unlockTableHeight() {
        $('.crm-search-results', $element).css('height', '');
      }

      // Debounced callback for loadResults
      function _loadResultsCallback() {
        // Multiply limit to read 2 pages at once & save ajax requests
        var params = angular.merge({debug: true, limit: ctrl.limit * 2}, ctrl.params);
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
        crmApi4(ctrl.entity, 'get', params).then(function(success) {
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
              ctrl.debug.params = JSON.stringify(ctrl.params, null, 2);
            }
          });
      }

      var _loadResults = _.debounce(_loadResultsCallback, 250);

      function loadResults() {
        $scope.loading = true;
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
        // Re-arranging or removing columns doesn't merit a refresh, only adding columns does
        if (!oldSelect || _.difference(newSelect, oldSelect).length) {
          if (ctrl.autoSearch) {
            ctrl.refreshPage();
          } else {
            ctrl.stale = true;
          }
        }
      }

      function onChangeOrderBy() {
        if (ctrl.results) {
          ctrl.refreshPage();
        }
      }

      function onChangeFilters() {
        ctrl.stale = true;
        ctrl.selectedRows.length = 0;
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
        var params = _.cloneDeep(ctrl.params);
        params.select = ['id'];
        crmApi4(ctrl.entity, 'get', params, ['id']).then(function(ids) {
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

      this.getFieldLabel = function(col) {
        var info = searchMeta.parseExpr(col),
          label = info.field.title;
        if (info.fn) {
          label = '(' + info.fn.title + ') ' + label;
        }
        return label;
      };

      // Is a column eligible to use an aggregate function?
      this.canAggregate = function(col) {
        // If the column is used for a groupBy, no
        if (ctrl.params.groupBy.indexOf(col) > -1) {
          return false;
        }
        // If the entity this column belongs to is being grouped by id, then also no
        var info = searchMeta.parseExpr(col);
        return ctrl.params.groupBy.indexOf(info.prefix + 'id') < 0;
      };

      $scope.formatResult = function formatResult(row, col) {
        var info = searchMeta.parseExpr(col),
          key = info.fn ? (info.fn.name + ':' + info.path + info.suffix) : col,
          value = row[key];
        // Handle grouped results
        if (info.fn && info.fn.name === 'GROUP_CONCAT' && value) {
          return formatGroupConcatValues(info, value);
        }
        else if (info.fn && info.fn.name === 'COUNT') {
          return value;
        }
        return formatFieldValue(info.field, value);
      };

      function formatFieldValue(field, value) {
        var type = field.data_type;
        if (value && (type === 'Date' || type === 'Timestamp') && /^\d{4}-\d{2}-\d{2}/.test(value)) {
          return CRM.utils.formatDate(value, null, type === 'Timestamp');
        }
        else if (type === 'Boolean' && typeof value === 'boolean') {
          return value ? ts('Yes') : ts('No');
        }
        else if (type === 'Money') {
          return CRM.formatMoney(value);
        }
        return value;
      }

      function formatGroupConcatValues(info, values) {
        return _.transform(values.split(','), function(result, val) {
          if (info.field.options && !info.suffix) {
            result.push(_.result(getOption(info.field, val), 'label'));
          } else {
            result.push(formatFieldValue(info.field, val));
          }
        }).join(', ');
      }

      function getOption(field, value) {
        return _.find(field.options, function(option) {
          // Type coersion is intentional
          return option.id == value;
        });
      }

      $scope.fieldsForGroupBy = function() {
        return {results: getAllFields('', function(key) {
            return _.contains(ctrl.params.groupBy, key);
          })
        };
      };

      $scope.fieldsForSelect = function() {
        return {results: getAllFields(':label', function(key) {
            return _.contains(ctrl.params.select, key);
          })
        };
      };

      $scope.fieldsForWhere = function() {
        return {results: getAllFields(':name', _.noop)};
      };

      $scope.fieldsForHaving = function() {
        return {results: _.transform(ctrl.params.select, function(fields, name) {
          fields.push({id: name, text: ctrl.getFieldLabel(name)});
        })};
      };

      function getDefaultSelect() {
        return _.filter(['id', 'display_name', 'label', 'title', 'location_type_id:label'], searchMeta.getField);
      }

      function getAllFields(suffix, disabledIf) {
        function formatFields(entityName, prefix) {
          return _.transform(searchMeta.getEntity(entityName).fields, function(result, field) {
            var item = {
              id: prefix + field.name + (field.options ? suffix : ''),
              text: field.title,
              description: field.description
            };
            if (disabledIf(item.id)) {
              item.disabled = true;
            }
            result.push(item);
          }, []);
        }

        var mainEntity = searchMeta.getEntity(ctrl.entity),
          result = [{
            text: mainEntity.title,
            icon: mainEntity.icon,
            children: formatFields(ctrl.entity, '')
          }];
        _.each(ctrl.params.join, function(join) {
          var joinName = join[0].split(' AS '),
            joinEntity = searchMeta.getEntity(joinName[0]);
          result.push({
            text: joinEntity.title + ' (' + joinName[1] + ')',
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
        var mainEntity = searchMeta.getEntity(ctrl.entity),
          entities = {};

        function enqueue(entity) {
          entity.optionsLoaded = false;
          entities[entity.name] = [entity.name, 'getFields', {
            loadOptions: CRM.vars.search.loadOptions,
            where: [['options', '!=', false]],
            select: ['options']
          }, {name: 'options'}];
        }

        if (typeof mainEntity.optionsLoaded === 'undefined') {
          enqueue(mainEntity);
        }
        _.each(ctrl.params.join, function(join) {
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

      this.$onInit = function() {
        $scope.$bindToRoute({
          expr: '$ctrl.params.select',
          param: 'select',
          format: 'json',
          default: getDefaultSelect()
        });
        $scope.$watchCollection('$ctrl.params.select', onChangeSelect);

        $scope.$bindToRoute({
          expr: '$ctrl.params.orderBy',
          param: 'orderBy',
          format: 'json',
          default: {}
        });
        $scope.$watchCollection('$ctrl.params.orderBy', onChangeOrderBy);

        $scope.$bindToRoute({
          expr: '$ctrl.params.where',
          param: 'where',
          format: 'json',
          default: [],
          deep: true
        });
        $scope.$watch('$ctrl.params.where', onChangeFilters, true);

        if (this.paramExists('groupBy')) {
          $scope.$bindToRoute({
            expr: '$ctrl.params.groupBy',
            param: 'groupBy',
            format: 'json',
            default: []
          });
        }
        $scope.$watchCollection('$ctrl.params.groupBy', onChangeFilters);

        if (this.paramExists('join')) {
          $scope.$bindToRoute({
            expr: '$ctrl.params.join',
            param: 'join',
            format: 'json',
            default: [],
            deep: true
          });
        }
        $scope.$watch('$ctrl.params.join', onChangeFilters, true);

        if (this.paramExists('having')) {
          $scope.$bindToRoute({
            expr: '$ctrl.params.having',
            param: 'having',
            format: 'json',
            default: [],
            deep: true
          });
        }
        $scope.$watch('$ctrl.params.having', onChangeFilters, true);

        loadFieldOptions();
      };

      $scope.saveGroup = function() {
        var selectField = ctrl.entity === 'Contact' ? 'id' : 'contact_id';
        if (ctrl.entity !== 'Contact' && !searchMeta.getField('contact_id')) {
          CRM.alert(ts('Cannot create smart group from %1.', {1: searchMeta.getEntity(true).title}), ts('Missing contact_id'), 'error', {expires: 5000});
          return;
        }
        var model = {
          title: '',
          description: '',
          visibility: 'User and User Admin Only',
          group_type: [],
          id: null,
          entity: ctrl.entity,
          params: angular.extend({}, ctrl.params, {version: 4, select: [selectField]})
        };
        delete model.params.orderBy;
        var options = CRM.utils.adjustDialogDefaults({
          autoOpen: false,
          title: ts('Save smart group')
        });
        dialogService.open('saveSearchDialog', '~/search/saveSmartGroup.html', model, options);
      };
    }
  });

})(angular, CRM.$, CRM._);
