(function () {

  class CiviRiverleaFontSizeControl extends HTMLElement {

     /* jshint ignore:start */
    static MODES = {
      small: {value: '0.875rem', label: ts('Smaller'), next: 'default'},
      default: {value: '1rem', label: ts('Default'), next: 'big'},
      big: {value: '1.125rem', label: ts('Bigger'), next: 'small'},
    };
    /* jshint ignore:end */

    connectedCallback() {
      this.render();
      this.loadMode();
      this.querySelector('button').addEventListener('click', () => this.nextMode());
    }

    render() {
      this.innerHTML = `
        <button type="button" role="switch" class="civi-riverlea-font-size-switch">
          <i class="crm-i fa-font" role="img" aria-disabled="true" style="font-size: 0.75rem" data-font-option="small"></i>
          <i class="crm-i fa-font" role="img" aria-disabled="true" style="font-size: 1rem" data-font-option="default"></i>
          <i class="crm-i fa-font" role="img" aria-disabled="true" style="font-size: 1.5rem" data-font-option="big"></i>
          <span class="sr-only"></span>
        </button>
      `;

      this.querySelector('.sr-only').innerText = ts('Adjust font size');
    }

    nextMode() {
      this.setMode(CiviRiverleaFontSizeControl.MODES[this.mode].next);
    }

    setMode(mode) {
      this.mode = mode;

      const value = CiviRiverleaFontSizeControl.MODES[this.mode].value;
      document.querySelector(':root').style.setProperty('--crm-font-size', value);

      this.renderMode();

      this.saveMode();
    }

    renderMode() {
      const label = CiviRiverleaFontSizeControl.MODES[this.mode].label;
      this.querySelector('button').title = ts('Font-size: %1. Click to adjust.', {1: label});

      this.querySelectorAll('.crm-i').forEach((el) => el.style.color = 'var(--crm-menubar-text-color)');
      this.querySelector(`.crm-i[data-font-option=${this.mode}]`).style.color = 'var(--crm-primary-color)';
    }

    saveMode() {
      window.localStorage.setItem('civi-riverlea-user-controls-font-size', this.mode);
    }

    loadMode() {
      const saved = window.localStorage.getItem('civi-riverlea-user-controls-font-size');
      this.setMode(saved ? saved : 'default');
    }
  }

  customElements.define('civi-riverlea-font-size-control', CiviRiverleaFontSizeControl);

})();
