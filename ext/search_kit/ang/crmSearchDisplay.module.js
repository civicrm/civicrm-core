(function(angular, $, _) {
  "use strict";

  // Declare module
  angular.module('crmSearchDisplay', CRM.angRequires('crmSearchDisplay'))

    // Provides base methods and properties common to all search display types
    .factory('searchDisplayBaseTrait', function(crmApi4) {
      var ts = CRM.ts('org.civicrm.search_kit');

      // Replace tokens keyed to rowData.
      // If rowMeta is provided, values will be formatted; if omitted, raw values will be provided.
      function replaceTokens(str, rowData, rowMeta, index) {
        if (!str) {
          return '';
        }
        _.each(rowData, function(value, key) {
          if (str.indexOf('[' + key + ']') >= 0) {
            var column = rowMeta && _.findWhere(rowMeta, {key: key}),
              val = column ? formatRawValue(column, value) : value,
              replacement = angular.isArray(val) ? val[index || 0] : val;
            str = str.replace(new RegExp(_.escapeRegExp('[' + key + ']', 'g')), replacement);
          }
        });
        return str;
      }

      function getUrl(link, rowData, index) {
        var url = replaceTokens(link, rowData, null, index);
        if (url.slice(0, 1) !== '/' && url.slice(0, 4) !== 'http') {
          url = CRM.url(url);
        }
        return url;
      }

      // Returns display value for a single column in a row
      function formatDisplayValue(rowData, key, columns) {
        var column = _.findWhere(columns, {key: key}),
          displayValue = column.rewrite ? replaceTokens(column.rewrite, rowData, columns) : formatRawValue(column, rowData[key]);
        return angular.isArray(displayValue) ? displayValue.join(', ') : displayValue;
      }

      // Returns value and url for a column formatted as link(s)
      function formatLinks(rowData, key, columns) {
        var column = _.findWhere(columns, {key: key}),
          value = formatRawValue(column, rowData[key]),
          values = angular.isArray(value) ? value : [value],
          links = [];
        _.each(values, function(value, index) {
          links.push({
            value: value,
            url: getUrl(column.link.path, rowData, index)
          });
        });
        return links;
      }

      // Formats raw field value according to data type
      function formatRawValue(column, value) {
        var type = column && column.dataType,
          result = value;
        if (_.isArray(value)) {
          return _.map(value, function(val) {
            return formatRawValue(column, val);
          });
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

      // Return a base trait shared by all search display controllers
      // Gets mixed in using angular.extend()
      return {
        page: 1,
        rowCount: null,
        getUrl: getUrl,

        // Called by the controller's $onInit function
        initializeDisplay: function($scope, $element) {
          var ctrl = this;
          this.sort = this.settings.sort ? _.cloneDeep(this.settings.sort) : [];

          $scope.getResults = _.debounce(function() {
            ctrl.getResults();
          }, 100);

          // If search is embedded in contact summary tab, display count in tab-header
          var contactTab = $element.closest('.crm-contact-page .ui-tabs-panel').attr('id');
          if (contactTab) {
            var unwatchCount = $scope.$watch('$ctrl.rowCount', function(rowCount) {
              if (typeof rowCount === 'number') {
                unwatchCount();
                CRM.tabHeader.updateCount(contactTab.replace('contact-', '#tab_'), rowCount);
              }
            });
          }

          function onChangeFilters() {
            ctrl.page = 1;
            ctrl.rowCount = null;
            if (ctrl.onChangeFilters) {
              ctrl.onChangeFilters();
            }
            $scope.getResults();
          }

          if (this.afFieldset) {
            $scope.$watch(this.afFieldset.getFieldData, onChangeFilters, true);
          }
          $scope.$watch('$ctrl.filters', onChangeFilters, true);
        },

        // Generate params for the SearchDisplay.run api
        getApiParams: function(mode) {
          return {
            return: mode || 'page:' + this.page,
            savedSearch: this.search,
            display: this.display,
            sort: this.sort,
            filters: _.assign({}, (this.afFieldset ? this.afFieldset.getFieldData() : {}), this.filters),
            afform: this.afFieldset ? this.afFieldset.getFormName() : null
          };
        },

        // Call SearchDisplay.run and update ctrl.results and ctrl.rowCount
        getResults: function() {
          var ctrl = this;
          return crmApi4('SearchDisplay', 'run', ctrl.getApiParams()).then(function(results) {
            ctrl.results = results;
            ctrl.editing = false;
            if (!ctrl.rowCount) {
              if (!ctrl.settings.limit || results.length < ctrl.settings.limit) {
                ctrl.rowCount = results.length;
              } else if (ctrl.settings.pager) {
                var params = ctrl.getApiParams('row_count');
                crmApi4('SearchDisplay', 'run', params).then(function(result) {
                  ctrl.rowCount = result.count;
                });
              }
            }
          });
        },
        replaceTokens: function(value, row) {
          return replaceTokens(value, row, this.settings.columns);
        },
        getLinks: function(rowData, col) {
          rowData._links = rowData._links || {};
          if (!(col.key in rowData._links)) {
            rowData._links[col.key] = formatLinks(rowData, col.key, this.settings.columns);
          }
          return rowData._links[col.key];
        },
        formatFieldValue: function(rowData, col) {
          return formatDisplayValue(rowData, col.key, this.settings.columns);
        }
      };
    });

})(angular, CRM.$, CRM._);
