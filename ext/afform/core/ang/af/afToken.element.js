(function (CRM, angular) {


  class AfToken extends HTMLElement {

    constructor() {
      super();
      // Bound once: the listener runs on afForm, so a bare
      // render loses `this`, and .bind() breaks removal.
      this.onFormChange = () => this.render();
    }

    connectedCallback() {
      this.afForm = this.closest('af-form');
      if (!this.afForm) {
        throw new Error('af-token should be placed within an af-form');
      }

      this.render();
      this.registerListener();
    }

    disconnectedCallback() {
      this.removeListener();
    }

    render() {
      this.innerText = this.evaluate(this.expression);
    }

    registerListener() {
      this.afForm?.addEventListener('change', this.onFormChange);
    }

    removeListener() {
      this.afForm?.removeEventListener('change', this.onFormChange);
    }

    get expression() {
      return this.getAttribute('expression');
    }

    get afFormCtrl() {
      return angular.element(this.afForm).controller('afForm');
    }

    evaluate(expression) {
      // TODO: support evaluation using Symfony Expression Language
      return this.afFormCtrl ? this.afFormCtrl.replaceTokens(expression) : '';
    }

  }

  customElements.define('af-token', AfToken);

})(CRM, angular);