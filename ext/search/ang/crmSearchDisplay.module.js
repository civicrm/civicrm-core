(function(angular, $, _) {
  "use strict";

  // Declare module
  angular.module('crmSearchDisplay', CRM.angRequires('crmSearchDisplay'))

    .factory('searchDisplayUtils', function() {
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

      function formatSearchValue(row, col, value) {
        var type = col.dataType,
          result = value;
        if (_.isArray(value)) {
          return _.map(value, function(val) {
            return formatSearchValue(col, val);
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

      function canAggregate(fieldName, prefix, apiParams) {
        // If the query does not use grouping, never
        if (!apiParams.groupBy.length) {
          return false;
        }
        // If the column is used for a groupBy, no
        if (apiParams.groupBy.indexOf(prefix + fieldName) > -1) {
          return false;
        }
        // If the entity this column belongs to is being grouped by id, then also no
        return apiParams.groupBy.indexOf(prefix + 'id') < 0;
      }

      function prepareColumns(columns, apiParams) {
        columns = _.cloneDeep(columns);
        _.each(columns, function(col, num) {
          var index = apiParams.select.indexOf(col.expr);
          if (_.includes(col.expr, '(') && !_.includes(col.expr, ' AS ')) {
            col.expr += ' AS column_' + num;
            apiParams.select[index] += ' AS column_' + num;
          }
          col.key = _.last(col.expr.split(' AS '));
        });
        return columns;
      }

      function prepareParams(apiParams, filters, page) {
        var params = _.cloneDeep(apiParams);
        if (_.isEmpty(params.where)) {
          params.where = [];
        }
        // Select the ids of joined entities (helps with displaying links)
        _.each(params.join, function(join) {
          var joinEntity = join[0].split(' AS ')[1],
            idField = joinEntity + '.id';
          if (!_.includes(params.select, idField) && !searchDisplayUtils.canAggregate('id', joinEntity + '.', params)) {
            params.select.push(idField);
          }
        });
        _.each(filters, function(value, key) {
          if (value) {
            params.where.push([key, 'CONTAINS', value]);
          }
        });
        if (page) {
          params.offset = (page - 1) * apiParams.limit;
          params.select.push('row_count');
        }
        return params;
      }

      return {
        formatSearchValue: formatSearchValue,
        canAggregate: canAggregate,
        prepareColumns: prepareColumns,
        prepareParams: prepareParams
      };
    });

})(angular, CRM.$, CRM._);
