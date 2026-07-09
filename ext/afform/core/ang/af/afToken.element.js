(function (CRM, angular) {


  class AfToken extends HTMLElement {

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
      this.afForm?.addEventListener('change', () => this.render());
    }

    removeListener() {
      this.afForm?.removeEventListener('change', () => this.render());
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