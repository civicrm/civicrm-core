(function (CRM, angular) {


  class AfMarkup extends HTMLElement {

    /* jshint ignore:start */
    static observedAttributes = ['markup'];
    /* jshint ignore:end */

    connectedCallback() {
      this.afForm = this.closest('af-form');
      // setTimeout ensures child elements can access parent af-form during render
      setTimeout(() => this.render());
    }

    attributeChangedCallback() {
      this.render();
    }

    render() {
      let markup = this.markup;
      if (this.afForm) {
        // at time of writing, token replacement is only supported
        // inside af-form (*not* search forms)
        markup = this.replaceTokensWithElements(markup);
      }
      this.innerHTML = markup;
    }

    get markup() {
      return this.getAttribute('markup');
    }

    set markup(content) {
      this.setAttribute('markup', content);
    }

    replaceTokensWithElements(markup) {
      // @see afForm identifyTokens
      const tokens = new Set(markup.match(/\[[a-zA-Z0-9_]+\.[0-9]+\.[^\]]+\]/g));
      tokens?.forEach((token) => markup = markup.replaceAll(token, `<af-token expression="${token}"></af-token>`));
      return markup;
    }
  }

  customElements.define('af-markup', AfMarkup);

})(CRM, angular);
