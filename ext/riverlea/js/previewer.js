(function (CRM) {
  /**
   * <civi-riverlea-stream-preview>
   *
   */
  class CiviRiverleaStreamPreview extends HTMLElement {
    constructor() {
      super();
    }

    connectedCallback() {
      // add our constructed stylesheet
      this.previewStyles = new CSSStyleSheet();
      document.adoptedStyleSheets.push(this.previewStyles);
      this.fetchCoreDarkRules();
    }

    disconnectedCallback() {
      delete this.previewStyles;
    }

    isDefaultRiver(href) {
      if (href.includes('river.css')) {
        return true;
      }
      if (href.includes('dyn/river')) {
        return true;
      }
      return false;
    }

    removeDefaultRiver() {
      const river = Array.from(document.styleSheets).find((sheet) =>
        sheet.ownerNode && sheet.ownerNode.href && this.isDefaultRiver(sheet.ownerNode.href)
      );
      if (river) {
        river.ownerNode.remove();
      }
    }

    async fetchCoreDarkRules() {
      if (this.coreDarkRules) return;
      return fetch(CRM.resourceUrls.civicrm + '/ext/riverlea/core/css/_dark.css')
        .then((response) => response.text())
        .then((content) => this.coreDarkRules = content);
    }

    loadStyles(styles) {
      this.removeDefaultRiver();

      // add a whole-page loading filter
      this.previewStyles.replaceSync(`
        body {
          filter: blur(4px)!important;
        }
      `);

      return this.previewStyles.replace(styles);
    }

    loadStreamById(id, darkMode = 'inherit') {
      return CRM.api4('RiverleaStream', 'render', {
        where: [['id', '=', id]],
        darkMode: darkMode,
      })
      .then((result) => result[0].content)
      .then((content) => this.loadStyles(content));
    }

    async render(streamData, darkMode = 'inherit') {
      await this.fetchCoreDarkRules();
      const styles = this.renderCss(streamData, darkMode);

      this.loadStyles(styles);
    }

    renderCss(streamData, darkMode = 'inherit') {
      if (!streamData) {
        return '';
      }

      const fileRules = streamData.css_file_content ?? '';
      const fileRulesDark = streamData.css_file_dark_content ?? '';

      const varRules = Object.entries(streamData.vars ?? {}).map((entry) => `${entry[0]}: ${entry[1]};`);
      const varRulesDark = Object.entries(streamData.vars_dark ?? {}).map((entry) => `${entry[0]}: ${entry[1]};`);

      const customCss = streamData.custom_css ?? '';
      const customCssDark = streamData.custom_css_dark ?? '';

      const lightRules = `
        ${fileRules}

        :root {
          ${varRules.join("\n")}
        }

        ${customCss}
      `;

      const darkRules = `
        ${this.coreDarkRules}

        ${fileRulesDark}

        :root {
          ${varRulesDark.join("\n")}
        }

        ${customCssDark}
      `;

      switch (darkMode) {
        case 'light':
          return lightRules;

        case 'dark':
          return `
            ${lightRules}

            ${darkRules}
          `;

        case 'inherit':
        default:
          return `
            ${lightRules}

            @media (prefers-color-scheme: dark) {
              ${darkRules}
            }
          `;
      }
    }

  }

  // register custom element in our civi namespace
  customElements.define('civi-riverlea-stream-preview', CiviRiverleaStreamPreview);
  /**
   * <civi-riverlea-preview-selector>
   *
   */
  class CiviRiverleaPreviewSelector extends HTMLElement {
    constructor() {
      super();

      this.streams = {};
    }

    connectedCallback() {
      this.style.backgroundColor = 'white';
      this.style.padding = '0.5rem';
      this.renderer = this.getOrCreateRenderer();
      this.append(this.renderer);

      const label = document.createElement('label');
      label.innerText = ts('Theme Preview');
      this.append(label);

      // create selector element
      this.selector = document.createElement('select');
      this.selector.addEventListener('change', () => {
        const selectedOption = this.selector.selectedOptions[0];

        // update stored selection
        this.setSelected(selectedOption.value);
      });
      this.selector.style.marginLeft = '1rem';

      label.append(this.selector);

      this.load();
    }

    getOrCreateRenderer() {
      const existing = document.querySelector('civi-riverlea-stream-preview');
      return existing ? existing : document.createElement('civi-riverlea-stream-preview');
    }

    fetchAllStreams() {
      return CRM.api4('RiverleaStream', 'getWithFileContent', {
        where: [['id', '>', 0]]
      })
      .then((streams) => streams.forEach((stream) => this.streams[stream.name] = stream));
    }

    renderSelector() {
      Object.assign(this.style, {
        position: 'fixed',
        bottom: '1rem',
        right: '1rem',
        zIndex: 1000,
      });

      this.selector.innerHTML = '';

      // add blank option
      this.blankOption = document.createElement('option');
      this.blankOption.value = '';
      this.blankOption.innerText = this.selected ? ts('- end preview -') : ts('- select -');
      this.selector.append(this.blankOption);

      Object.values(this.streams).forEach((stream) => {
        const option = document.createElement('option');
        option.value = stream.name;
        option.innerText = stream.label;
        option.selected = (option.value === this.selected);
        this.selector.append(option);
      });
    }

    load() {
      const sessionData = CRM.riverlea.previewSession();

      if (sessionData && sessionData.selected) {
        this.streams = sessionData.streams;
        this.renderSelector();
        this.setSelected(sessionData.selected, false);
        return Promise.resolve();
      }
      else {
        return this.fetchAllStreams()
          .then(() => this.renderSelector());
      }
    }

    saveSession() {
      CRM.riverlea.previewSession({
        streams: this.streams,
        selected: this.selected,
      });
    }

    endSession() {
      CRM.riverlea.previewSession(false);
      window.location.reload();
    }

    setSelected(value, notify = true) {
      if (!value || value === '') {
        this.endSession();
        return;
      }

      if (!this.streams[value]) {
        console.warn('Stream not available');
        return;
      }

      // notify if the the stream is changing
      if (notify && (this.selected !== value)) {
        CRM.alert(ts('Previewing stream: ') + this.streams[value].label, '', 'info');
      }

      this.selected = value;

      Array.from(this.selector.options).forEach((option) => option.selected = (option.value === this.selected));
      this.blankOption.innerText = ts('- end preview -');

      this.saveSession();

      this.renderPreview();
    }

    renderPreview() {
      const streamData = this.streams[this.selected];

      this.renderer.render(streamData);
    }
  }

  // register custom element in our civi namespace
  customElements.define('civi-riverlea-preview-selector', CiviRiverleaPreviewSelector);
})(CRM);


/**
 *
* CRM.riverlea has global functions for the preview session state
 */
(function (CRM) {
  CRM.riverlea = CRM.riverlea || {};

  CRM.riverlea.previewer = (init = true) => {
    const existing = document.querySelector('civi-riverlea-preview-selector');
    if (existing) {
      return existing;
    }
    if (!init) {
      return null;
    }
    const previewer = document.createElement('civi-riverlea-preview-selector');
    document.querySelector('body').append(previewer);
    return previewer;
  };

  CRM.riverlea.previewSession = (value) => {
    if (value === undefined) {
      return JSON.parse(sessionStorage.getItem('civi_riverlea_preview_session'));
    }
    if (!value) {
      sessionStorage.removeItem('civi_riverlea_preview_session');
    }
    sessionStorage.setItem('civi_riverlea_preview_session', JSON.stringify(value));
  };

  CRM.riverlea.checkForPreviewSession = () => {
    // if we find a preview setting in the session
    // then reopen the previewer immediately
    const previewSession = CRM.riverlea.previewSession();
    if (previewSession && previewSession.selected) {
      CRM.riverlea.previewer();
    }
  };

  document.addEventListener('DOMContentLoaded', () => CRM.riverlea.checkForPreviewSession());
})(CRM);

