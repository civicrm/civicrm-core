(function(angular, $, _) {
  "use strict";

  // Trait provides base methods and properties common to all search display types
  angular.module('crmSearchDisplay').factory('searchDisplayBaseTrait', function(crmApi4, crmStatus) {

    // Return a base trait shared by all search display controllers
    // Gets mixed in using angular.extend()
    return {
      page: 1,
      rowCount: null,
      // Arrays may contain callback functions for various events
      onChangeFilters: [],
      onPreRun: [],
      onPostRun: [],
      _runCount: 0,

      // Called by the controller's $onInit function
      initializeDisplay: function($scope, $element) {
        var ctrl = this;
        this.limit = this.settings.limit;
        this.sort = this.settings.sort ? _.cloneDeep(this.settings.sort) : [];
        this.seed = Date.now();
        this.placeholders = [];
        var placeholderCount = 'placeholder' in this.settings ? this.settings.placeholder : 5;
        for (var p=0; p < placeholderCount; ++p) {
          this.placeholders.push({});
        }

        this.getResults = _.debounce(function() {
          $scope.$apply(function() {
            ctrl.runSearch();
          });
        }, 800);

        // Update totalCount variable if used.
        // Integrations can pass in `total-count="somevar" to keep track of the number of results returned
        // FIXME: Additional hack to directly update tabHeader for contact summary tab. It would be better to
        // decouple the contactTab code into a separate directive that checks totalCount.
        var contactTab = $element.closest('.crm-contact-page .ui-tabs-panel').attr('id');
        if (contactTab || typeof ctrl.totalCount !== 'undefined') {
          $scope.$watch('$ctrl.rowCount', function(rowCount) {
            // Update totalCount only if no user filters are set
            if (typeof rowCount === 'number' && angular.equals({}, ctrl.getAfformFilters())) {
              ctrl.totalCount = rowCount;
              if (contactTab) {
                CRM.tabHeader.updateCount(contactTab.replace('contact-', '#tab_'), rowCount);
              }
            }
          });
        }

        // Popup forms in this display or surrounding Afform trigger a refresh
        $element.closest('form').on('crmPopupFormSuccess', function() {
          ctrl.rowCount = null;
          ctrl.getResults();
        });

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
          // Add filter title to Afform
          this.onPostRun.push(function(results) {
            if (results.labels && results.labels.length && $scope.$parent.addTitle) {
              $scope.$parent.addTitle(results.labels.join(' '));
            }
          });
        }
        if (this.settings.pager && this.settings.pager.expose_limit) {
          $scope.$watch('$ctrl.limit', onChangePageSize);
        }
        $scope.$watch('$ctrl.filters', onChangeFilters, true);
      },

      hasExtraFirstColumn: function() {
        return this.settings.actions || this.settings.draggable || (this.settings.tally && this.settings.tally.label);
      },

      getAfformFilters: function() {
        return _.pick(this.afFieldset ? this.afFieldset.getFieldData() : {}, function(val) {
          return val !== null && (_.includes(['boolean', 'number'], typeof val) || val.length);
        });
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
          filters: _.assign({}, this.getAfformFilters(), this.filters),
          afform: this.afFieldset ? this.afFieldset.getFormName() : null
        };
      },

      onClickSearchButton: function() {
        this.rowCount = null;
        this.page = 1;
        this.getResults();
      },

      // Call SearchDisplay.run and update ctrl.results and ctrl.rowCount
      runSearch: function(apiCalls, statusParams, editedRow) {
        var ctrl = this,
          requestId = ++this._runCount,
          apiParams = this.getApiParams();
        if (!statusParams) {
          this.loading = true;
        }
        _.each(ctrl.onPreRun, function(callback) {
          callback.call(ctrl, apiParams);
        });
        apiCalls = apiCalls || [];
        apiCalls.push(['SearchDisplay', 'run', apiParams]);
        var apiRequest = crmApi4(apiCalls);
        apiRequest.then(function(apiResults) {
          if (requestId < ctrl._runCount) {
            return; // Another request started after this one
          }
          ctrl.results = _.last(apiResults);
          ctrl.editing = ctrl.loading = false;
          // Update rowCount if running for the first time or during an update op
          if (!ctrl.rowCount || editedRow) {
            if (!ctrl.limit || ctrl.results.length < ctrl.limit) {
              ctrl.rowCount = ctrl.results.length;
            } else if (ctrl.settings.pager) {
              var params = ctrl.getApiParams('row_count');
              crmApi4('SearchDisplay', 'run', params).then(function(result) {
                ctrl.rowCount = result.count;
              });
            }
          }
          _.each(ctrl.onPostRun, function(callback) {
            callback.call(ctrl, ctrl.results, 'success', editedRow);
          });
        }, function(error) {
          if (requestId < ctrl._runCount) {
            return; // Another request started after this one
          }
          ctrl.results = [];
          ctrl.editing = ctrl.loading = false;
          _.each(ctrl.onPostRun, function(callback) {
            callback.call(ctrl, error, 'error', editedRow);
          });
        });
        if (statusParams) {
          crmStatus(statusParams, apiRequest);
        }
        return apiRequest;
      },
      formatFieldValue: function(colData) {
        return angular.isArray(colData.val) ? colData.val.join(', ') : colData.val;
      }
    };
  });

})(angular, CRM.$, CRM._);
