(function(angular, $, _) {
  "use strict";

  // Trait provides base methods and properties common to all search display types
  angular.module('crmSearchDisplay').factory('searchDisplayBaseTrait', function(crmApi4) {
    var ts = CRM.ts('org.civicrm.search_kit'),
      runCount = 0,
      seed = Date.now();

    // Replace tokens keyed to rowData.
    // Pass view=true to replace with view value, otherwise raw value is used.
    function replaceTokens(str, rowData, view, index) {
      if (!str) {
        return '';
      }
      _.each(rowData, function(value, key) {
        if (str.indexOf('[' + key + ']') >= 0) {
          var val = view ? value.view : value.raw,
            replacement = angular.isArray(val) ? val[index || 0] : val;
          str = str.replace(new RegExp(_.escapeRegExp('[' + key + ']', 'g')), replacement);
        }
      });
      return str;
    }

    function getUrl(link, rowData, index) {
      var url = replaceTokens(link, rowData, false, index);
      if (url.slice(0, 1) !== '/' && url.slice(0, 4) !== 'http') {
        url = CRM.url(url);
      }
      return url;
    }

    // Returns display value for a single column in a row
    function formatDisplayValue(rowData, key, columns) {
      var column = _.findWhere(columns, {key: key}),
        displayValue = column.rewrite ? replaceTokens(column.rewrite, rowData, columns) : getValue(rowData[key], 'view');
      return angular.isArray(displayValue) ? displayValue.join(', ') : displayValue;
    }

    // Returns value and url for a column formatted as link(s)
    function formatLinks(rowData, key, columns) {
      var column = _.findWhere(columns, {key: key}),
        value = column.image ? '' : getValue(rowData[key], 'view'),
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

    // Get value from column data, specify either 'raw' or 'view'
    function getValue(data, ret) {
      return (data || {})[ret];
    }

    // Return a base trait shared by all search display controllers
    // Gets mixed in using angular.extend()
    return {
      page: 1,
      rowCount: null,
      getUrl: getUrl,
      // Arrays may contain callback functions for various events
      onChangeFilters: [],
      onPreRun: [],
      onPostRun: [],

      // Called by the controller's $onInit function
      initializeDisplay: function($scope, $element) {
        var ctrl = this;
        this.limit = this.settings.limit;
        this.sort = this.settings.sort ? _.cloneDeep(this.settings.sort) : [];
        this.seed = Date.now();

        this.getResults = _.debounce(function() {
          $scope.$apply(function() {
            ctrl.runSearch();
          });
        }, 800);

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

        $element.on('crmPopupFormSuccess', this.getResults);

        function onChangeFilters() {
          ctrl.page = 1;
          ctrl.rowCount = null;
          _.each(ctrl.onChangeFilters, function(callback) {
            callback.call(ctrl);
          });
          if (!ctrl.settings.button) {
            ctrl.getResults();
          }
        }

        function onChangePageSize() {
          ctrl.page = 1;
          // Only refresh if search has already been run
          if (ctrl.results) {
            ctrl.getResults();
          }
        }

        if (this.afFieldset) {
          $scope.$watch(this.afFieldset.getFieldData, onChangeFilters, true);
        }
        if (this.settings.pager && this.settings.pager.expose_limit) {
          $scope.$watch('$ctrl.limit', onChangePageSize);
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
          limit: this.limit,
          seed: this.seed,
          filters: _.assign({}, (this.afFieldset ? this.afFieldset.getFieldData() : {}), this.filters),
          afform: this.afFieldset ? this.afFieldset.getFormName() : null
        };
      },

      onClickSearchButton: function() {
        this.rowCount = null;
        this.page = 1;
        this.getResults();
      },

      // Call SearchDisplay.run and update ctrl.results and ctrl.rowCount
      runSearch: function(editedRow) {
        var ctrl = this,
          requestId = ++runCount,
          apiParams = this.getApiParams();
        this.loading = true;
        _.each(ctrl.onPreRun, function(callback) {
          callback.call(ctrl, apiParams);
        });
        return crmApi4('SearchDisplay', 'run', apiParams).then(function(results) {
          if (requestId < runCount) {
            return; // Another request started after this one
          }
          ctrl.results = results;
          ctrl.editing = ctrl.loading = false;
          if (!ctrl.rowCount) {
            if (!ctrl.limit || results.length < ctrl.limit) {
              ctrl.rowCount = results.length;
            } else if (ctrl.settings.pager) {
              var params = ctrl.getApiParams('row_count');
              crmApi4('SearchDisplay', 'run', params).then(function(result) {
                ctrl.rowCount = result.count;
              });
            }
          }
          _.each(ctrl.onPostRun, function(callback) {
            callback.call(ctrl, results, 'success', editedRow);
          });
        }, function(error) {
          if (requestId < runCount) {
            return; // Another request started after this one
          }
          ctrl.results = [];
          ctrl.editing = ctrl.loading = false;
          _.each(ctrl.onPostRun, function(callback) {
            callback.call(ctrl, error, 'error', editedRow);
          });
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
