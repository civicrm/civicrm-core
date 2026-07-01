(function () {

  class CiviRiverleaUserControls extends HTMLElement {

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
        <span class="civi-riverlea-user-controls-wrap">
          <button type="button" role="switch" class="civi-riverlea-color-scheme-switch">
            <i class="crm-i" role="img" aria-disabled="true"></i>
          </button>
        </span>
      `;
    }

    nextMode() {
      this.setMode(CiviRiverleaUserControls.MODES[this.mode].next);
    }

    setMode(mode) {
      this.mode = mode;

      document.querySelector(':root').dataset.civiColorScheme = this.mode;

      this.renderMode();

      this.saveMode();
    }

    renderMode() {
      const details = CiviRiverleaUserControls.MODES[this.mode];

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
      window.localStorage.setItem('civi-riverlea-user-controls-color-scheme', this.mode);
    }

    loadMode() {
      const saved = window.localStorage.getItem('civi-riverlea-user-controls-color-scheme');
      this.setMode(saved ? saved : 'auto');
    }
  }

  customElements.define('civi-riverlea-user-controls', CiviRiverleaUserControls);

  function appendWrappedControl(container) {
    const wrapper = document.createElement('li');
    wrapper.className = 'civi-riverlea-user-controls-item';

    const controls = document.createElement('civi-riverlea-user-controls');
    wrapper.append(controls);

    container.append(wrapper);
  }

  document.addEventListener('DOMContentLoaded', () => {
    const menu = document.getElementById('civicrm-menu');
    if (menu) {
      appendWrappedControl(menu);
      return;
    }

    const observer = new MutationObserver(() => {
      const menu = document.getElementById('civicrm-menu');
      if (menu) {
    appendWrappedControl(menu);

        observer.disconnect();
      }
    });
    observer.observe(document.body, {childList: true, subtree: true});
  });

})();
