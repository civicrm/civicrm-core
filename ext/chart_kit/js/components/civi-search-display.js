(function (api4, $, _) {

  CRM.components = CRM.components || {};

  CRM.components.civi_search_display = class CiviSearchDisplay extends HTMLElement {

    /* jshint ignore:start */
    static observedAttributes = ['filters'];
    /* jshint ignore:end */

    constructor() {
      super();
      this.page = 1;
      this.rowCount = null;
      // Arrays may contain callback functions for various events
      this.onInitialize = [];
      this.onChangeFilters = [];
      this.onPreRun = [];
      this.onPostRun = [];
      this._runCount = 0;
    }

    connectedCallback() {
      this.initializeDisplay();
    }

    disconnectedCallback() {
      // this removes listeners added in initializeDisplay
      if (this.formContainer) {
        this.formContainer.removeEventListener('crmFormChangeFilters', () => this._onChangeFilters());
      }
    }

    get formContainer() {
      return this.closest('form');
    }

    get afFieldset() {
      return this.closest('[af-fieldset]');
    }

    get afFieldsetCtrl() {
      return this.afFieldset ? angular.element(this.afFieldset).controller('afFieldset') : null;
    }

    attributeChangedCallback(name, oldValue, newValue) {
      if (name === 'filters') {
        this._onChangeFilters();
      }
    }

    get apiEntity() {
      return this.getAttribute('api-entity');
    }

    get search() {
      return this.getJsonAttribute('search');
    }

    getJsonAttribute(attributeName, defaultValue = {}) {
      const value = this.getAttribute(attributeName);
      if (!value || value === '') {
        return defaultValue;
      }
      // catch runs before the angular digest
      if (value === `{{ $ctrl.${attributeName} }}`) {
        return defaultValue;
      }
      if (!value.includes('{')) {
        return value;
      }
      return JSON.parse(value);
    }

    get display() {
      return this.getJsonAttribute('display');
    }

    get apiParams() {
      return JSON.parse(this.getAttribute('api-params'));
    }

    get settings() {
      return this.getJsonAttribute('settings');
    }

    get filters() {
      return this.getJsonAttribute('filters');
    }

    get totalCount() {
      return this.getAttribute('total-count');
    }

    getResultsPronto() {
      if (this.justRun) {
        // if just run, dont run again
        return;
      }
      this.runSearch();
      this.justRun = true;
      setTimeout(() => this.justRun = false, 300);
    }

    getResultsSoon() {
      clearTimeout(this.nextRun);
      this.nextRun = setTimeout(() => this.runSearch(), 600);
    }

    // common functionality for the controller's $onInit function
    // TODO: move to connectedCallback and use super
    initializeDisplay() {
      this.limit = this.settings.limit;
      this.sort = this.settings.sort ? _.cloneDeep(this.settings.sort) : [];
      this.uniqueId = Math.floor(Math.random() * 10e10);
      this.placeholders = [];
      const placeholderCount = 'placeholder' in this.settings ? this.settings.placeholder : 5;
      for (let p=0; p < placeholderCount; ++p) {
        this.placeholders.push({});
      }

      // Update totalCount variable if used.
      // Integrations can pass in `total-count="somevar" to keep track of the number of results returned
      // FIXME: Additional hack to directly update tabHeader for contact summary tab. It would be better to
      // decouple the contactTab code into a separate directive that checks totalCount.
      let contactTab = this.closest('.crm-contact-page .ui-tabs-panel');
      // Only the first display in a tab gets to control the count
      if (contactTab && (this === document.querySelector(contactTab.getAttribute('id') + ' [search][display]'))) {
        contactTab = null;
      }
      let hasCounter = contactTab || this.hasOwnProperty('totalCount');

  //  if (hasCounter) {
  //    $scope.$watch('$ctrl.rowCount', function(rowCount) {
  //      // Update totalCount only if no user filters are set
  //      if (typeof rowCount === 'number' && angular.equals({}, this.getAfformFilters())) {
  //        setTotalCount(rowCount);
  //      }
  //    });
  //  }

      const setTotalCount = (rowCount) => {
        if (this.hasOwnProperty('totalCount')) {
          this.totalCount = rowCount;
        }
        if (contactTab) {
          CRM.tabHeader.updateCount(contactTab.replace('contact-', '#tab_'), rowCount);
        }
      };


      // Popup forms in this display or surrounding Afform trigger a refresh
      $(this).closest('form').on('crmPopupFormSuccess crmFormSuccess', () => {
        this.rowCount = null;
        this.getResultsPronto();
      });


      // Process toolbar after run
      if (this.settings.toolbar) {
        this.onPostRun.push((apiResults) => {
          if (apiResults.run.toolbar) {
            this.toolbar = apiResults.run.toolbar;
            // If there are no results on initial load, open an "autoOpen" toolbar link
            this.toolbar.forEach((link) => {
              if (link.autoOpen && this._runCount === 1 && !this.results.length) {
                CRM.loadForm(link.url)
                  .on('crmFormSuccess', (e, data) => {
                    this.rowCount = null;
                    this.getResultsPronto();
                  });
              }
            });
          }
        });
      }

      if (this.afFieldset) {
        // Add filter title to Afform
        this.onPostRun.push((apiResults) => {
          if (apiResults.run.labels && apiResults.run.labels.length && $scope.$parent.addTitle) {
            console.log("$scope.$parent.addTitle(apiResults.run.labels.join(' '));");
          }
        });
      }

      // NOTE: @see searchDisplayBaseTrait setUpWatches
      // here we reimplement with events

      // When filters are changed, trigger callbacks and refresh search (if there's no search button)
      if (this.afFieldset) {
        this.afFieldset.addEventListener('crmFormChangeFilters', () => this._onChangeFilters(), true);
      }

      // TODO: implement pager reload? this could be moved to a trait - not relevant for some displays
      // const onChangePageSize = () => {
      //   this.page = 1;
      //   // Only refresh if search has already been run
      //   if (this.results) {
      //     this.getResultsSoon();
      //   }
      // }

      // // If the search display is visible, go ahead & run it
      // if ($element.is(':visible')) {
      //   setUpWatches();
      // }
      // // Wait until display is visible
      // else {
      //   let checkVisibility = $interval(() => {
      //     if ($element.is(':visible')) {
      //       $interval.cancel(checkVisibility);
      //       setUpWatches();
      //     }
      //   }, 250);
      // }

      // Manually fetch total count if:
      // - there is a counter (e.g. a contact summary tab)
      // - and the search is hidden or not set to auto-run
      // - or afform filters are present which would interfere with an accurate total
      // (wait a brief timeout to allow more important things to happen first)
      setTimeout(() => {
        if (hasCounter && (!(this.loading || this.results) || !Object.keys(this.getAfformFilters()).length)) {
          const params = this.getApiParams('row_count');
          // Exclude afform filters
          params.filters = this.filters;
          api4('SearchDisplay', 'run', params).then(function(result) {
            setTotalCount(result.count);
          });
        }
      }, 900);
    }

    _onChangeFilters() {
      clearTimeout(this.filterChangeTimeout);

      this.filterChangeTimeout = setTimeout(() => {
        this.page = 1;
        this.rowCount = null;
        this.onChangeFilters.forEach((callback) => callback());
        if (!this.settings.button) {
          this.getResultsSoon();
        }
      }, 1000);
    }

    hasExtraFirstColumn() {
      return this.settings.actions || this.settings.draggable || this.settings.collapsible || this.settings.editableRow || (this.settings.tally && this.settings.tally.label);
    }

    getFilters() {
      return Object.assign({}, this.getAfformFilters(), this.filters);
    }

    getAfformFilters() {
      return this.afFieldsetCtrl ? this.afFieldsetCtrl.getFilterValues() : {};
    }

    // Generate params for the SearchDisplay.run api
    getApiParams(mode) {
      return {
        return: arguments.length ? mode : 'page:' + this.page,
        savedSearch: this.search,
        display: this.display,
        sort: this.sort,
        limit: this.limit,
        seed: this.uniqueId,
        filters: this.getFilters(),
        afform: this.afFieldsetCtrl ? this.afFieldsetCtrl.getFormName() : null
      };
    }

    onClickSearchButton() {
      this.rowCount = null;
      this.page = 1;
      this.getResultsPronto();
    }

    // Call SearchDisplay.run and update this.results and this.rowCount
    runSearch(apiCalls, statusParams, editedRow) {
      // TODO: unwind use of ctrl
      const ctrl = this;
      const requestId = ++this._runCount;
      const apiParams = this.getApiParams();
      if (!statusParams) {
        this.loading = true;
      }
      apiCalls = apiCalls || {};
      apiCalls.run = ['SearchDisplay', 'run', apiParams];
      this.onPreRun.forEach((callback) => callback.call(this, apiCalls));
      const apiRequest = api4(apiCalls);
      apiRequest.then((apiResults) => {
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
            const params = ctrl.getApiParams('row_count');
            api4('SearchDisplay', apiCalls.run[1], params).then(function(result) {
              if (requestId < ctrl._runCount) {
                return; // Another request started after this one
              }

              ctrl.rowCount = result.count;
            });
          }
        }
        this.onPostRun.forEach((callback) => callback.call(ctrl, apiResults, 'success', editedRow));
      }, (error) => {
        if (requestId < ctrl._runCount) {
          return; // Another request started after this one
        }
        ctrl.results = [];
        ctrl.loading = false;
        this.onPostRun.forEach((callback) => callback.call(this, error, 'error', editedRow));
      });
      if (statusParams) {
        CRM.status(statusParams, apiRequest);
      }
      return apiRequest;
    }

    getFieldClass(colIndex, colData) {
      return (colData.cssClass || '') + ' crm-search-col-type-' + this.settings.columns[colIndex].type + (this.settings.columns[colIndex].break ? '' : ' crm-inline-block');
    }

    getFieldTemplate(colIndex, colData) {
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
    }
  };

})(CRM.api4, CRM.$, CRM._);
