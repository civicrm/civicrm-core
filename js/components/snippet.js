(function() {
   /**
    * <civi-snippet>
    *
    *
    * Expected markup is:
    *
    * ```
    * <civi-snippet src="/civicrm/contact/view?reset=1" />
    * ```
    *
    */
  class CiviSnippet extends HTMLElement {
    constructor() {
      super();

      // bind class methods to the instance
      this.load = this.load.bind(this);
      this.loadIfNotLoaded = this.loadIfNotLoaded.bind(this);
    }

    connectedCallback() {
      // track whether we have ever loaded
      this.isLazy = !!this.getAttribute('lazy-load');

      this.hasLoaded = false;

      if (!this.isLazy) {
        this.load();
      }
    }

    load() {
      // once we've loaded
      this.hasLoaded = true;

      // add a loading spinner
      this.innerHTML = '<div class="crm-i fa-spinner fa-spin"></div>';

      const src = this.getAttribute('src');
      const url = URL.parse(src, document.location);

      if (!url) {
        this.innerHTML = '';
        return;
      }

      url.searchParams.set('snippet', 'ajax');

      fetch(url.toString())
      .then((response) => response.text())
      .then((text) => this.innerHTML = text);
    }

    loadIfNotLoaded() {
      if (!this.hasLoaded) {
        this.load();
      }
    }
  }

  // register custom element in our civi namespace
  customElements.define('civi-snippet', CiviSnippet);
})();
