(function (CRM) {
  /**
   * <civi-riverlea-stream-preview>
   *
   */
  class CiviRiverleaStreamPreview extends HTMLElement {
    constructor() {
      super();

      this.streams = {};
    }

    connectedCallback() {
      // add our constructed stylesheet
      this.previewStyles = new CSSStyleSheet();
      document.adoptedStyleSheets.push(this.previewStyles);

      this.riverCssUrl = CRM.vars.riverlea.river_url;
      this.darkModeSetting = CRM.vars.riverlea.dark_mode;
    }

    disconnectedCallback() {
      delete this.previewStyles;
    }

    /**
     * remove the default river.css
     * and clear any styles we've added
     **/
    clearSlate() {
      if (!this.riverCssUrl) {
        throw new Error('river.css should be passed from the server. Do you have a Riverlea theme active?');
      }
      const styleSheets = Array.from(document.styleSheets);
      const riverSheet = styleSheets.find((sheet) =>
        // TODO: this match may be too exacting if e.g. proxy gets in the way. could just check location?
        sheet.ownerNode && sheet.ownerNode.href && (sheet.ownerNode.href === this.riverCssUrl)
      );
      if (riverSheet) {
        riverSheet.ownerNode.remove();
      }
    }

    render(streamData) {
      const styles = this.renderCss(streamData);

      this.clearSlate();

      // add a whole-page loading filter
      this.previewStyles.replaceSync(`
      body {
        filter: blur(4px)!important;
      }
    `);

      this.previewStyles.replaceSync(styles);
    }

    renderCss(streamData) {
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
        ${fileRulesDark}

        :root {
          ${varRulesDark.join("\n")}
        }

        ${customCssDark}
      `;

      switch (this.darkModeSetting) {
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
      this.renderer = this.getOrCreateRenderer();
      this.append(this.renderer);

      // create selector element
      this.selector = document.createElement('select');
      this.selector.addEventListener('change', () => {
        const selectedOption = this.selector.selectedOptions[0];

        // update stored selection
        this.setSelected(selectedOption.value);
      });

      this.append(this.selector);

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

