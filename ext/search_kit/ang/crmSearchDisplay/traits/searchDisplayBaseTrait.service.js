(function(angular, $, _) {
  "use strict";

  // Trait provides base methods and properties common to all search display types
  angular.module('crmSearchDisplay').factory('searchDisplayBaseTrait', function($timeout, $interval, crmApi4, crmStatus) {

    // Return a base trait shared by all search display controllers
    // Gets mixed in using angular.extend()
    return {
      page: 1,
      rowCount: null,
      // Arrays may contain callback functions for various events
      onInitialize: [],
      onChangeFilters: [],
      onPreRun: [],
      onPostRun: [],
      _runCount: 0,
      isArray: Array.isArray,

      // Called by the controller's $onInit function
      initializeDisplay: function($scope, $element) {
        var ctrl = this;
        this.$element = $element;
        this.limit = this.settings.limit;
        this.sort = this.settings.sort ? _.cloneDeep(this.settings.sort) : [];
        this.seed = Date.now();
        this.placeholders = [];
        var placeholderCount = 'placeholder' in this.settings ? this.settings.placeholder : 5;
        for (var p=0; p < placeholderCount; ++p) {
          this.placeholders.push({});
        }
        _.each(ctrl.onInitialize, function(callback) {
          callback.call(ctrl, $scope, $element);
        });

        // _.debounce used here to trigger the initial search immediately but prevent subsequent launches within 300ms
        this.getResultsPronto = _.debounce(ctrl.runSearch, 300, {leading: true, trailing: false});
        // _.debounce used here to schedule a search if nothing else happens for 600ms: useful for auto-searching on typing
        this.getResultsSoon = _.debounce(function() {
          $scope.$apply(function() {
            ctrl.runSearch();
          });
        }, 600);

        // Update totalCount variable if used.
        // Integrations can pass in `total-count="somevar" to keep track of the number of results returned
        // FIXME: Additional hack to directly update tabHeader for contact summary tab. It would be better to
        // decouple the contactTab code into a separate directive that checks totalCount.
        let contactTab = $element.closest('.crm-contact-page .ui-tabs-panel').attr('id');
        // Only the first display in a tab gets to control the count
        if (contactTab && !$element.is($('#' + contactTab + ' [search][display]').first())) {
          contactTab = null;
        }
        let hasCounter = contactTab || ctrl.hasOwnProperty('totalCount');
        if (hasCounter) {
          $scope.$watch('$ctrl.rowCount', function(rowCount) {
            // Update totalCount only if no user filters are set
            if (typeof rowCount === 'number' && angular.equals({}, ctrl.getAfformFilters())) {
              setTotalCount(rowCount);
            }
          });
        }

        function setTotalCount(rowCount) {
          if (ctrl.hasOwnProperty('totalCount')) {
            ctrl.totalCount = rowCount;
          }
          if (contactTab) {
            CRM.tabHeader.updateCount(contactTab.replace('contact-', '#tab_'), rowCount);
          }
        }

        // Popup forms in this display or surrounding Afform trigger a refresh
        $element.closest('form').on('crmPopupFormSuccess crmFormSuccess', function() {
          ctrl.rowCount = null;
          ctrl.getResultsPronto();
        });

        // When filters are changed, trigger callbacks and refresh search (if there's no search button)
        function onChangeFilters() {
          ctrl.page = 1;
          ctrl.rowCount = null;
          _.each(ctrl.onChangeFilters, function(callback) {
            callback.call(ctrl);
          });
          if (!ctrl.settings.button) {
            ctrl.getResultsSoon();
          }
        }

        function onChangePageSize() {
          ctrl.page = 1;
          // Only refresh if search has already been run
          if (ctrl.results) {
            ctrl.getResultsSoon();
          }
        }

        // Process toolbar after run
        if (this.settings.toolbar) {
          this.onPostRun.push(function (apiResults) {
            if (apiResults.run.toolbar) {
              ctrl.toolbar = apiResults.run.toolbar;
              // If there are no results on initial load, open an "autoOpen" toolbar link
              ctrl.toolbar.forEach((link) => {
                if (link.autoOpen && requestId === 1 && !ctrl.results.length) {
                  CRM.loadForm(link.url)
                    .on('crmFormSuccess', (e, data) => {
                      ctrl.rowCount = null;
                      ctrl.getResultsPronto();
                    });
                }
              });
            }
          });
        }

        if (this.afFieldset) {
          // Add filter title to Afform
          this.onPostRun.push(function (apiResults) {
            if (apiResults.run.labels && apiResults.run.labels.length && $scope.$parent.addTitle) {
              $scope.$parent.addTitle(apiResults.run.labels.join(' '));
            }
          });
        }

        // Set up watches to refresh search results when needed.
        // Because `angular.$watch` runs immediately as well as on subsequent changes,
        // this also kicks off the first run of the search (if there's no search button).
        function setUpWatches() {
          if (ctrl.afFieldset) {
            $scope.$watch(ctrl.afFieldset.getFieldData, onChangeFilters, true);
          }
          if (ctrl.settings.pager && ctrl.settings.pager.expose_limit) {
            $scope.$watch('$ctrl.limit', onChangePageSize);
          }
          $scope.$watch('$ctrl.filters', onChangeFilters, true);
        }

        // If the search display is visible, go ahead & run it
        if ($element.is(':visible')) {
          setUpWatches();
        }
        // Wait until display is visible
        else {
          let checkVisibility = $interval(() => {
            if ($element.is(':visible')) {
              $interval.cancel(checkVisibility);
              setUpWatches();
            }
          }, 250);
        }

        // Manually fetch total count if:
        // - there is a counter (e.g. a contact summary tab)
        // - and the search is hidden or not set to auto-run
        // - or afform filters are present which would interfere with an accurate total
        // (wait a brief timeout to allow more important things to happen first)
        $timeout(function() {
          if (hasCounter && (!(ctrl.loading || ctrl.results) || !angular.equals({}, ctrl.getAfformFilters()))) {
            var params = ctrl.getApiParams('row_count');
            // Exclude afform filters
            params.filters = ctrl.filters;
            crmApi4('SearchDisplay', 'run', params).then(function(result) {
              setTotalCount(result.count);
            });
          }
        }, 900);
      },

      hasExtraFirstColumn: function() {
        return this.settings.actions || this.settings.draggable || this.settings.collapsible || this.settings.editableRow || (this.settings.tally && this.settings.tally.label);
      },

      getFilters: function() {
        return _.assign({}, this.getAfformFilters(), this.filters);
      },

      getAfformFilters: function() {
        return _.pick(this.afFieldset ? this.afFieldset.getFieldData() : {}, function(val) {
          return typeof val !== 'undefined' && val !== null && (_.includes(['boolean', 'number', 'object'], typeof val) || val.length);
        });
      },

      // Generate params for the SearchDisplay.run api
      getApiParams: function(mode) {
        return {
          return: arguments.length ? mode : 'page:' + this.page,
          savedSearch: this.search,
          display: this.display,
          sort: this.sort,
          limit: this.limit,
          seed: this.seed,
          filters: this.getFilters(),
          afform: this.afFieldset ? this.afFieldset.getFormName() : null
        };
      },

      onClickSearchButton: function() {
        this.rowCount = null;
        this.page = 1;
        this.getResultsPronto();
      },

      // Call SearchDisplay.run and update ctrl.results and ctrl.rowCount
      runSearch: function(apiCalls, statusParams, editedRow) {
        var ctrl = this,
          requestId = ++this._runCount,
          apiParams = this.getApiParams();
        if (!statusParams) {
          this.loading = true;
        }
        apiCalls = apiCalls || {};
        apiCalls.run = ['SearchDisplay', 'run', apiParams];
        _.each(ctrl.onPreRun, function(callback) {
          callback.call(ctrl, apiCalls);
        });
        const apiRequest = crmApi4(apiCalls);
        apiRequest.then(function(apiResults) {
          if (requestId < ctrl._runCount) {
            return; // Another request started after this one
          }
          ctrl.results = apiResults.run;
          ctrl.loading = false;
          // Update rowCount if running for the first time or during an update op
          if (!ctrl.rowCount || editedRow) {
            // No need to fetch count if on page 1 and result count is under the limit
            if (!ctrl.limit || (ctrl.results.length < ctrl.limit && ctrl.page === 1)) {
              ctrl.rowCount = ctrl.results.length;
            } else if (ctrl.settings.pager || ctrl.settings.headerCount) {
              var params = ctrl.getApiParams('row_count');
              crmApi4('SearchDisplay', apiCalls.run[1], params).then(function(result) {
                ctrl.rowCount = result.count;
              });
            }
          }
          _.each(ctrl.onPostRun, function(callback) {
            callback.call(ctrl, apiResults, 'success', editedRow);
          });
        }, function(error) {
          if (requestId < ctrl._runCount) {
            return; // Another request started after this one
          }
          ctrl.results = [];
          ctrl.loading = false;
          _.each(ctrl.onPostRun, function(callback) {
            callback.call(ctrl, error, 'error', editedRow);
          });
        });
        if (statusParams) {
          crmStatus(statusParams, apiRequest);
        }
        return apiRequest;
      },

      getFieldClass: function(colIndex, colData) {
        return (colData.cssClass || '') + ' crm-search-col-type-' + this.settings.columns[colIndex].type + (this.settings.columns[colIndex].break ? '' : ' crm-inline-block');
      },

      getFieldTemplate: function(colIndex, colData) {
        let colType = this.settings.columns[colIndex].type;
        if (colType === 'include') {
          return this.settings.columns[colIndex].path;
        }
        if (colType === 'field') {
          if (colData.edit) {
            colType = 'editable';
          } else if (colData.links) {
            colType = 'link';
          }
        }
        return '~/crmSearchDisplay/colType/' + colType + '.html';
      },
    };
  });

})(angular, CRM.$, CRM._);
