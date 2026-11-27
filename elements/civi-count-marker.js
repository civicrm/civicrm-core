(function () {

  /**
   * Usage:
   *
   *   <civi-count-marker count="12"></civi-count-marker>
   *     will show count of 123
   *   <civi-count-marker count="0"></civi-count-marker>
   *     will show count of 0
   *   <civi-count-marker count="?"></civi-count-marker>
   *     will show an explicit ?
   *   <civi-count-marker count="any-other-non-int-val"></civi-count-marker>
   *     will show a loading indicator
   */

  class CiviCountMarker extends HTMLElement {

    /* jshint ignore:start */
    static observedAttributes = ['count'];
    /* jshint ignore:end */

    connectedCallback() {
      this.render();
    }

    attributeChangedCallback() {
      this.render();
    }

    render() {
      const count = this.getAttribute('count');

      if (count === '?') {
        this.classList.remove('crm-count-loading');
        this.innerHTML = '<i class="crm-i fa-question"></i>';
      }
      else if (isNaN(parseInt(count))) {
        this.classList.add('crm-count-loading');
        if (!this.innerText.length) {
          this.innerHTML = '<i class="crm-i fa-question"></i>';
        }
      }
      else {
        this.classList.remove('crm-count-loading');
        this.innerText = count;
      }
    }
  }

  customElements.define('civi-count-marker', CiviCountMarker);

})();
