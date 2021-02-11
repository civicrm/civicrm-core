(function(angular, $, _) {
  "use strict";

  // Declare module
  angular.module('crmSearchDisplay', CRM.angRequires('crmSearchDisplay'))

    .factory('searchDisplayUtils', function(crmApi4) {

      function replaceTokens(str, data) {
        if (!str) {
          return '';
        }
        _.each(data, function(value, key) {
          str = str.replace('[' + key + ']', value);
        });
        return str;
      }

      function getUrl(link, row) {
        var url = replaceTokens(link, row);
        if (url.slice(0, 1) !== '/' && url.slice(0, 4) !== 'http') {
          url = CRM.url(url);
        }
        return _.escape(url);
      }

      function formatSearchValue(row, col, value) {
        var type = col.dataType,
          result = value;
        if (_.isArray(value)) {
          return _.map(value, function(val) {
            return formatSearchValue(row, col, val);
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

      function prepareColumns(columns) {
        columns = _.cloneDeep(columns);
        _.each(columns, function(col) {
          col.key = _.last(col.expr.split(' AS '));
        });
        return columns;
      }

      function getApiParams(ctrl, mode) {
        return {
          return: mode || 'page:' + ctrl.page,
          savedSearch: ctrl.search,
          display: ctrl.display,
          sort: ctrl.sort,
          filters: _.assign({}, (ctrl.afFieldset ? ctrl.afFieldset.getFieldData() : {}), ctrl.filters)
        };
      }

      function getResults(ctrl) {
        var params = getApiParams(ctrl);
        crmApi4('SearchDisplay', 'run', params).then(function(results) {
          ctrl.results = results;
          if (ctrl.settings.pager && !ctrl.rowCount) {
            if (results.length < ctrl.settings.limit) {
              ctrl.rowCount = results.length;
            } else {
              var params = getApiParams(ctrl, 'row_count');
              crmApi4('SearchDisplay', 'run', params).then(function(result) {
                ctrl.rowCount = result.count;
              });
            }
          }
        });
      }

      return {
        formatSearchValue: formatSearchValue,
        prepareColumns: prepareColumns,
        getApiParams: getApiParams,
        getResults: getResults,
        replaceTokens: replaceTokens
      };
    });

})(angular, CRM.$, CRM._);
