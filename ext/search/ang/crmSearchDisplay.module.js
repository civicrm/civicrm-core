(function(angular, $, _) {
  "use strict";

  // Declare module
  angular.module('crmSearchDisplay', CRM.angRequires('crmSearchDisplay'))

    .factory('formatSearchValue', function() {
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

      return function formatSearchValue(row, col, value) {
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
      };
    })

    .factory('searchDisplayFieldCanAggregate', function() {
      return function searchDisplayFieldCanAggregate(fieldName, prefix, apiParams) {
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
      };
    });

})(angular, CRM.$, CRM._);
