const generateUniqueId = (length) => {
  const chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
  let result = "";
  for (let i = 0; i < length; i++) {
    result += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  return result;
}

class CiviSearchDisplay extends HTMLElement {

  static observedAttributes = ['filters'];

  constructor() {
    super();
    this.page = 1;
    this.rowCount = null;
    // Arrays may contain callback functions for various event;
    this.onInitialize = [];
    this.onChangeFilters = [];
    this.onPreRun = [];
    this.onPostRun = [];
    this._runCount = 0;
  }

  connectedCallback() {
    this.closest('form').addEventListener('crmFormChangeFilters', () => this._onChangeFilters(), true);
  }

  disconnectedCallback() {
    this.closest('form').removeEventListener('crmFormChangeFilters', () => this._onChangeFilters());
  }

  get afFieldset() {
    const element = this.closest('[af-fieldset]');
    return element ? angular.element(element).controller('afFieldset') : null;
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

  // _.debounce used here to trigger the initial search immediately but prevent subsequent launches within 300ms
  getResultsPronto() {
    clearTimeout(this.nextRun);
    // {leading: true, trailing: false}
    this.nextRun = setTimeout(() => this.runSearch(), 300);
  }
  // _.debounce used here to schedule a search if nothing else happens for 600ms: useful for auto-searching on typing
  getResultsSoon() {
    clearTimeout(this.nextRun);
    this.nextRun = setTimeout(() => this.runSearch(), 600);
  }

  // Called by the controller's $onInit function
  initializeDisplay() {
    this.limit = this.settings.limit;
    this.sort = this.settings.sort ? CRM._.cloneDeep(this.settings.sort) : [];
    this.seed = Date.now();
    this.uniqueId = generateUniqueId(20);
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

//    if (hasCounter) {
//      $scope.$watch('$ctrl.rowCount', function(rowCount) {
//        // Update totalCount only if no user filters are set
//        if (typeof rowCount === 'number' && angular.equals({}, this.getAfformFilters())) {
//          setTotalCount(rowCount);
//        }
//      });
//    }

    const setTotalCount = (rowCount) => {
      if (this.hasOwnProperty('totalCount')) {
        this.totalCount = rowCount;
      }
      if (contactTab) {
        CRM.tabHeader.updateCount(contactTab.replace('contact-', '#tab_'), rowCount);
      }
    }


    // Popup forms in this display or surrounding Afform trigger a refresh
    CRM.$(this).closest('form').on('crmPopupFormSuccess crmFormSuccess', () => {
      this.rowCount = null;
      this.getResultsPronto();
    });

    // When filters are changed, trigger callbacks and refresh search (if there's no search button)

    const onChangePageSize = () => {
      this.page = 1;
      // Only refresh if search has already been run
      if (this.results) {
        this.getResultsSoon();
      }
    }

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

//    // Set up watches to refresh search results when needed.
//    // Because `angular.$watch` runs immediately as well as on subsequent changes,
//    // this also kicks off the first run of the search (if there's no search button).
//    function setUpWatches() {
//      if (ctrl.afFieldset) {
//        $scope.$watch(ctrl.afFieldset.getFieldData, onChangeFilters, true);
//      }
//      if (ctrl.settings.pager && ctrl.settings.pager.expose_limit) {
//        $scope.$watch('$ctrl.limit', onChangePageSize);
//      }
//      $scope.$watch('$ctrl.filters', onChangeFilters, true);
//    }

//    // If the search display is visible, go ahead & run it
//    if ($element.is(':visible')) {
//      setUpWatches();
//    }
//    // Wait until display is visible
//    else {
//      let checkVisibility = $interval(() => {
//        if ($element.is(':visible')) {
//          $interval.cancel(checkVisibility);
//          setUpWatches();
//        }
//      }, 250);
//    }

    // Manually fetch total count if:
    // - there is a counter (e.g. a contact summary tab)
    // - and the search is hidden or not set to auto-run
    // - or afform filters are present which would interfere with an accurate total
    // (wait a brief timeout to allow more important things to happen first)
    setTimeout(() => {
      if (hasCounter && (!(this.loading || this.results) || !angular.equals({}, this.getAfformFilters()))) {
        const params = this.getApiParams('row_count');
        // Exclude afform filters
        params.filters = this.filters;
        CRM.api4('SearchDisplay', 'run', params).then(function(result) {
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
    }, 1000)
  }

  hasExtraFirstColumn() {
    return this.settings.actions || this.settings.draggable || this.settings.collapsible || this.settings.editableRow || (this.settings.tally && this.settings.tally.label);
  }

  getFilters() {
    return Object.assign({}, this.getAfformFilters(), this.filters);
  }

  getAfformFilters() {
    return CRM._.pick(this.afFieldset ? this.afFieldset.getFieldData() : {}, function(val) {
      return typeof val !== 'undefined' && val !== null && (CRM._.includes(['boolean', 'number', 'object'], typeof val) || val.length);
    });
  }

  // WARNING: Only to be used with trusted/sanitized markup.
  // This is safe to use on html columns because `AbstractRunAction::formatColumn` already runs it through `CRM_Utils_String::purifyHTML()`.
  getRawHtml(html) {
    CRM.alert('Set innerHtml rather than innerText');
  }

  // Generate params for the SearchDisplay.run api
  getApiParams(mode) {
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
  }

  onClickSearchButton() {
    this.rowCount = null;
    this.page = 1;
    this.getResultsPronto();
  }

  // Call SearchDisplay.run and update ctrl.results and ctrl.rowCount
  runSearch(apiCalls, statusParams, editedRow) {
    const ctrl = this;
    const requestId = ++this._runCount;
    const apiParams = this.getApiParams();
    if (!statusParams) {
      this.loading = true;
    }
    apiCalls = apiCalls || {};
    apiCalls.run = ['SearchDisplay', 'run', apiParams];
    this.onPreRun.forEach((callback) => callback.call(this, apiCalls));
    const apiRequest = CRM.api4(apiCalls);
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
          CRM.api4('SearchDisplay', apiCalls.run[1], params).then(function(result) {
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
      crmStatus(statusParams, apiRequest);
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
}
