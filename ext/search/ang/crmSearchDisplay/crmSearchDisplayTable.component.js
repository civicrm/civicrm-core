(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchDisplay').component('crmSearchDisplayTable', {
    bindings: {
      apiEntity: '<',
      apiParams: '<',
      settings: '<',
      filters: '<'
    },
    templateUrl: '~/crmSearchDisplay/crmSearchDisplayTable.html',
    controller: function($scope, crmApi4) {
      var ts = $scope.ts = CRM.ts(),
        ctrl = this;

      this.page = 1;
      this.selectedRows = [];
      this.allRowsSelected = false;

      this.$onInit = function() {
        this.orderBy = _.cloneDeep(this.apiParams.orderBy || {});
        this.limit = parseInt(ctrl.settings.limit || 0, 10);
        this.columns = _.cloneDeep(ctrl.settings.columns);
        _.each(ctrl.columns, function(col, num) {
          var index = ctrl.apiParams.select.indexOf(col.expr);
          if (_.includes(col.expr, '(') && !_.includes(col.expr, ' AS ')) {
            col.expr += ' AS column_' + num;
            ctrl.apiParams.select[index] += ' AS column_' + num;
          }
          col.key = _.last(col.expr.split(' AS '));
        });
      };

      this.getResults = function() {
        var params = _.merge(_.cloneDeep(ctrl.apiParams), {limit: ctrl.limit, offset: (ctrl.page - 1) * ctrl.limit, orderBy: ctrl.orderBy});
        if (_.isEmpty(params.where)) {
          params.where = [];
        }
        // Select the ids of joined entities (helps with displaying links)
        _.each(params.join, function(join) {
          var joinEntity = join[0].split(' AS ')[1],
            idField = joinEntity + '.id';
          if (!_.includes(params.select, idField) && !canAggregate('id', joinEntity + '.')) {
            params.select.push(idField);
          }
        });
        _.each(ctrl.filters, function(value, key) {
          if (value) {
            params.where.push([key, 'CONTAINS', value]);
          }
        });
        if (ctrl.settings.pager) {
          params.select.push('row_count');
        }
        crmApi4(ctrl.apiEntity, 'get', params).then(function(results) {
          ctrl.results = results;
          ctrl.rowCount = results.count;
        });
      };

      $scope.$watch('$ctrl.filters', ctrl.getResults, true);

      /**
       * Returns crm-i icon class for a sortable column
       * @param col
       * @returns {string}
       */
      $scope.getOrderBy = function(col) {
        var dir = ctrl.orderBy && ctrl.orderBy[col.key];
        if (dir) {
          return 'fa-sort-' + dir.toLowerCase();
        }
        return 'fa-sort disabled';
      };

      /**
       * Called when clicking on a column header
       * @param col
       * @param $event
       */
      $scope.setOrderBy = function(col, $event) {
        var dir = $scope.getOrderBy(col) === 'fa-sort-asc' ? 'DESC' : 'ASC';
        if (!$event.shiftKey) {
          ctrl.orderBy = {};
        }
        ctrl.orderBy[col.key] = dir;
        ctrl.getResults();
      };

      $scope.formatResult = function(row, col) {
        var value = row[col.key];
        return formatFieldValue(row, col, value);
      };

      function formatFieldValue(row, col, value) {
        var type = col.dataType,
          result = value;
        if (_.isArray(value)) {
          return _.map(value, function(val) {
            return formatFieldValue(col, val);
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
        result = _.escape(result);
        if (col.link) {
          result = '<a href="' + getUrl(col.link, row) + '">' + result + '</a>';
        }
        return result;
      }

      function getUrl(link, row) {
        var url = replaceTokens(link, row);
        if (url.slice(0, 1) !== '/' && url.slice(0, 4) !== 'http') {
          url = CRM.url(url);
        }
        return _.escape(url);
      }

      function replaceTokens(str, data) {
        _.each(data, function(value, key) {
          str = str.replace('[' + key + ']', value);
        });
        return str;
      }

      function canAggregate(fieldName, prefix) {
        // If the query does not use grouping, never
        if (!ctrl.apiParams.groupBy.length) {
          return false;
        }
        // If the column is used for a groupBy, no
        if (ctrl.apiParams.groupBy.indexOf(prefix + fieldName) > -1) {
          return false;
        }
        // If the entity this column belongs to is being grouped by id, then also no
        return ctrl.apiParams.groupBy.indexOf(prefix + 'id') < 0;
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
        var params = _.cloneDeep(ctrl.apiParams);
        params.select = ['id'];
        crmApi4(ctrl.apiEntity, 'get', params, ['id']).then(function(ids) {
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

    }
  });

})(angular, CRM.$, CRM._);
