(function(angular, $, _) {
  "use strict";

  // Declare module
  angular.module('crmSearchDisplay', CRM.angRequires('crmSearchDisplay'))

    .factory('searchDisplayUtils', function(crmApi4) {

      // Replace tokens keyed to rowData.
      // If rowMeta is provided, values will be formatted; if omitted, raw values will be provided.
      function replaceTokens(str, rowData, rowMeta) {
        if (!str) {
          return '';
        }
        _.each(rowData, function(value, key) {
          if (str.indexOf('[' + key + ']') >= 0) {
            var column = rowMeta && _.findWhere(rowMeta, {key: key}),
              replacement = column ? formatRawValue(column, value) : value;
            str = str.replace(new RegExp(_.escapeRegExp('[' + key + ']', 'g')), replacement);
          }
        });
        return str;
      }

      function getUrl(link, rowData) {
        var url = replaceTokens(link, rowData);
        if (url.slice(0, 1) !== '/' && url.slice(0, 4) !== 'http') {
          url = CRM.url(url);
        }
        return url;
      }

      // Returns display value for a single column in a row
      function formatDisplayValue(rowData, key, rowMeta) {
        var column = _.findWhere(rowMeta, {key: key}),
          displayValue = column.rewrite ? replaceTokens(column.rewrite, rowData, rowMeta) : formatRawValue(column, rowData[key]);
        return displayValue;
      }

      // Formats raw field value according to data type
      function formatRawValue(column, value) {
        var type = column && column.dataType,
          result = value;
        if (_.isArray(value)) {
          return _.map(value, function(val) {
            return formatRawValue(column, val);
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
        return result;
      }

      function getApiParams(ctrl, mode) {
        return {
          return: mode || 'page:' + ctrl.page,
          savedSearch: ctrl.search,
          display: ctrl.display,
          sort: ctrl.sort,
          filters: _.assign({}, (ctrl.afFieldset ? ctrl.afFieldset.getFieldData() : {}), ctrl.filters),
          afform: ctrl.afFieldset ? ctrl.afFieldset.getFormName() : null
        };
      }

      function getResults(ctrl) {
        return crmApi4('SearchDisplay', 'run', getApiParams(ctrl)).then(function(results) {
          ctrl.results = results;
          ctrl.editing = false;
          if (!ctrl.rowCount) {
            if (!ctrl.settings.limit || results.length < ctrl.settings.limit) {
              ctrl.rowCount = results.length;
            } else if (ctrl.settings.pager) {
              var params = getApiParams(ctrl, 'row_count');
              crmApi4('SearchDisplay', 'run', params).then(function(result) {
                ctrl.rowCount = result.count;
              });
            }
          }
        });
      }

      return {
        formatDisplayValue: formatDisplayValue,
        getApiParams: getApiParams,
        getResults: getResults,
        replaceTokens: replaceTokens,
        getUrl: getUrl
      };
    });

})(angular, CRM.$, CRM._);
