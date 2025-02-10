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
      this.refresh = this.refresh.bind(this);
    }

    connectedCallback() {
      // initialise the nav header element
      if (!this.isLazy) {
        this.refresh();
      }
    }

    get isLazy() {
      return !!this.getAttribute('lazy-load');
    }

    refresh() {
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
  }

  // register custom element in our civi namespace
  customElements.define('civi-snippet', CiviSnippet);
})();
