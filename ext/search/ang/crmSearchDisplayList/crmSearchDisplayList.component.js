(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchDisplayList').component('crmSearchDisplayList', {
    bindings: {
      apiEntity: '<',
      apiParams: '<',
      settings: '<',
      filters: '<'
    },
    templateUrl: '~/crmSearchDisplayList/crmSearchDisplayList.html',
    controller: function($scope, crmApi4, formatSearchValue, searchDisplayFieldCanAggregate) {
      var ts = $scope.ts = CRM.ts(),
        ctrl = this;
      this.page = 1;

      this.$onInit = function() {
        this.limit = parseInt(ctrl.settings.limit || 0, 10);
        ctrl.columns = _.cloneDeep(ctrl.settings.columns);
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
        var params = _.merge(_.cloneDeep(ctrl.apiParams), {limit: ctrl.limit, offset: (ctrl.page - 1) * ctrl.limit});
        if (_.isEmpty(params.where)) {
          params.where = [];
        }
        // Select the ids of joined entities (helps with displaying links)
        _.each(params.join, function(join) {
          var joinEntity = join[0].split(' AS ')[1],
            idField = joinEntity + '.id';
          if (!_.includes(params.select, idField) && !searchDisplayFieldCanAggregate('id', joinEntity + '.', params)) {
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

      $scope.formatResult = function(row, col) {
        var value = row[col.key],
          formatted = formatSearchValue(row, col, value),
          output = '';
        if (formatted.length || (col.label && col.forceLabel)) {
          if (col.label && (formatted.length || col.forceLabel)) {
            output += '<label>' + _.escape(col.label) + '</label> ';
          }
          if (formatted.length) {
            output += (col.prefix || '') + formatted + (col.suffix || '');
          }
        }
        return output;
      };

    }
  });

})(angular, CRM.$, CRM._);
