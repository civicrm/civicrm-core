(function () {

  class CiviRiverleaDarkModeControl extends HTMLElement {

     /* jshint ignore:start */
    static MODES = {
      light: {
        label: ts('Light'),
        icon: 'fa-sun',
        next: 'dark',
      },
      dark: {
        label: ts('Dark'),
        icon: 'fa-moon',
        next: 'auto',
      },
      auto: {
        label: ts('Auto'),
        icon: 'fa-circle-half-stroke',
        next: 'light',
      }
    };
    /* jshint ignore:end */

    connectedCallback() {
      this.render();
      this.loadMode();
      this.querySelector('button').addEventListener('click', () => this.nextMode());
    }

    render() {
      this.innerHTML = `
        <span class="civi-riverlea-dark-mode-control-wrap">
          <button type="button" role="switch" class="civi-riverlea-dark-mode-switch">
            <i class="crm-i" role="img" aria-disabled="true"></i>
          </button>
        </span>
      `;
    }

    nextMode() {
      this.setMode(CiviRiverleaDarkModeControl.MODES[this.mode].next);
    }

    setMode(mode) {
      this.mode = mode;

      document.querySelector(':root').dataset.civiColorScheme = this.mode;

      this.renderMode();

      this.saveMode();
    }

    renderMode() {
      const details = CiviRiverleaDarkModeControl.MODES[this.mode];

      // swap the icon class
      this.querySelector('.crm-i').classList.remove('fa-sun', 'fa-moon', 'fa-circle-half-stroke');
      this.querySelector('.crm-i').classList.add(details.icon);

      const button = this.querySelector('button');
      button.dataset.mode = this.mode;
      const description = ts('Backend theme mode: %1. Click to switch', {1: details.label});
      button.title = description;
      //redundant?
      button.setAttribute('aria-label', description);

    }

    saveMode() {
      window.localStorage.setItem('civi-riverlea-user-controls-dark-mode', this.mode);
    }

    loadMode() {
      const saved = window.localStorage.getItem('civi-riverlea-user-controls-dark-mode');
      this.setMode(saved ? saved : 'auto');
    }
  }

  customElements.define('civi-riverlea-dark-mode-control', CiviRiverleaDarkModeControl);

})();
