(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchDisplay').component('crmSearchDisplayTable', {
    bindings: {
      apiEntity: '<',
      apiParams: '<',
      settings: '<'
    },
    templateUrl: '~/crmSearchDisplay/crmSearchDisplayTable.html',
    controller: function($scope, crmApi4) {
      var ts = $scope.ts = CRM.ts(),
        ctrl = this;

      this.page = 1;

      this.$onInit = function() {
        this.orderBy = this.apiParams.orderBy || {};
        this.limit = parseInt(ctrl.settings.limit || 0, 10);
        _.each(ctrl.settings.columns, function(col, num) {
          var index = ctrl.apiParams.select.indexOf(col.expr);
          if (_.includes(col.expr, '(') && !_.includes(col.expr, ' AS ')) {
            col.expr += ' AS column_' + num;
            ctrl.apiParams.select[index] += ' AS column_' + num;
          }
          col.key = _.last(col.expr.split(' AS '));
        });
        getResults();
      };

      function getResults() {
        var params = _.merge(_.cloneDeep(ctrl.apiParams), {limit: ctrl.limit, offset: (ctrl.page - 1) * ctrl.limit, orderBy: ctrl.orderBy});
        if (ctrl.settings.pager) {
          params.select.push('row_count');
        }
        crmApi4(ctrl.apiEntity, 'get', params).then(function(results) {
          ctrl.results = results;
          ctrl.rowCount = results.count;
        });
      }

      this.changePage = function() {
        getResults();
      };

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
        getResults();
      };

      $scope.formatResult = function(row, col) {
        var value = row[col.key];
        return formatFieldValue(col, value);
      };

      function formatFieldValue(col, value) {
        var type = col.dataType;
        if (_.isArray(value)) {
          return _.map(value, function(val) {
            return formatFieldValue(col, val);
          }).join(', ');
        }
        if (value && (type === 'Date' || type === 'Timestamp') && /^\d{4}-\d{2}-\d{2}/.test(value)) {
          return CRM.utils.formatDate(value, null, type === 'Timestamp');
        }
        else if (type === 'Boolean' && typeof value === 'boolean') {
          return value ? ts('Yes') : ts('No');
        }
        else if (type === 'Money' && typeof value === 'number') {
          return CRM.formatMoney(value);
        }
        return value;
      }

    }
  });

})(angular, CRM.$, CRM._);
