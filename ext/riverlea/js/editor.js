(function (CRM) {

  /**
   * <civi-riverlea-stream-editor>
   *
   */
  class CiviRiverleaStreamEditor extends HTMLElement {
    constructor() {
      super();

      this.data = {};
    }

    connectedCallback() {
      this.render();

      // switch between editing light/dark mode
      this.editDarkMode = false;
    }


    fetchStreamData() {
      // initialise store for unsaved values
      return CRM.api4('RiverleaStream', 'getWithFileContent', {
        where: [['name', '=', this.streamName]]
      })
      .then((result) => {
        if (result && result.length) {
          return result[0];
        }
        throw new Error('Unable to load editor for ' + streamName);
      })
      .then((stream) => this.setStreamData(stream));
    }

    setStreamData(streamData) {
      this.data = streamData;
      // initialise copy for unsaved edits
      this.unsavedData = {...this.data};
    }

    save() {
      return CRM.api4('RiverleaStream', 'update', {
        where: [['name', '=', this.streamName]],
        values: this.unsavedData
      })
      .then(() => CRM.alert(ts('Stream edits saved'), '', 'success'))
      .then(() => this.dispatchEvent(new Event('save')));
    }

    reset() {
      this.unsavedData = {...this.data};
      this.renderEditPane();
      this.refreshPreview();
      this.dispatchEvent(new Event('reset'));
    }

    refreshPreview() {
      const previewDocument = this.previewFrame.contentDocument;
      const previewBody = previewDocument?.querySelector('body');

      if (!previewBody) {
        // this can happen, it's usually not a problem
        console.debug('Editor preview frame is not loaded yet');
        return;
      }

      // remove any session preview selector
      previewBody.querySelectorAll('civi-riverlea-preview-selector').forEach((el) => el.remove());

      let framePreview = previewBody.querySelector('civi-riverlea-stream-preview');

      if (!framePreview) {
        framePreview = previewDocument.createElement('civi-riverlea-stream-preview');
        previewBody.append(framePreview);
      }

      // this ensures any css_file content for the stream is loaded in the frame
      framePreview.render(this.unsavedData, this.editDarkMode ? 'dark' : 'light');
    }

    render() {
      // initialise basic internal structure
      this.innerHTML = `
        <div class="crm-flex-box">

          <div class="civi-riverlea-stream-editor-edit-pane"></div>

          <div class="civi-riverlea-stream-editor-preview-pane">
            <iframe></iframe>
          </div>

        </div>

        <div class="civi-riverlea-stream-editor-buttons crm-buttons"></div>
      `;

      this.previewFrame = this.querySelector('iframe');
      this.previewFrame.src = CRM.url('');
      this.previewFrame.addEventListener('load', () => this.refreshPreview());

      this.editPane = this.querySelector('.civi-riverlea-stream-editor-edit-pane');
      this.renderEditPane();

      this.renderButtons();
    }

    renderEditPane() {
      this.editPane.innerHTML = `
        <h2></h2>

        <fieldset class="civi-riverlea-stream-meta-inputs"></fieldset>

        <div class="civi-riverlea-stream-colors-header">
          <h3></h3>
          <label class="civi-riverlea-stream-dark-toggle crm-form-toggle-container">
            <span></span>
            <input type="checkbox" class="crm-form-toggle">
          </label>
        </div>

        <fieldset class="civi-riverlea-stream-color-inputs-light"></fieldset>
        <fieldset class="civi-riverlea-stream-color-inputs-dark"></fieldset>

        <fieldset class="civi-riverlea-stream-size-inputs"></fieldset>
        <fieldset class="civi-riverlea-stream-custom-inputs"></fieldset>
      `;

      this.editPane.querySelector('h2').innerText = this.data.label;

      this.editPane.querySelector('.civi-riverlea-stream-colors-header h3').innerText = ts('Colors');

      // render dark mode switcher
      const darkModeToggle = this.editPane.querySelector('.civi-riverlea-stream-dark-toggle input');
      darkModeToggle.checked = this.editDarkMode;
      darkModeToggle.onchange = () => {
        this.editDarkMode = darkModeToggle.checked;
        this.toggleDarkMode();
        this.refreshPreview();
      };

      // ensure label is rendered and only one color fieldset is displayed before they are populated
      this.toggleDarkMode();
      this.renderInputs();
    }

    toggleDarkMode() {
      this.editPane.querySelector('.civi-riverlea-stream-dark-toggle span').innerText = this.editDarkMode ? ts('Dark mode') : ts('Light mode');
      this.editPane.querySelector('.civi-riverlea-stream-color-inputs-light').style.display = this.editDarkMode ? 'none' : null;
      this.editPane.querySelector('.civi-riverlea-stream-color-inputs-dark').style.display = this.editDarkMode ? null : 'none';
    }

    renderInputs() {
      const createVariableInput = (label, streamField, name, type, unit = null, options = null) => {
        const el = document.createElement('civi-riverlea-stream-variable');
        el.setAttribute('stream-field', streamField);
        el.setAttribute('name', name);
        el.setAttribute('type', type);
        el.setAttribute('label', label);
        if (unit) {
          el.setAttribute('unit', unit);
        }
        if (options) {
          el.options = options;
        }

        return el;
      };

      const createTextInput = (labelText, streamField, textArea = false) => {
        const group = document.createElement('div');

        const input = textArea ? document.createElement('textarea') : document.createElement('input');
        if (!textArea) {
          input.type = 'text';
        }

        input.value = this.unsavedData[streamField] ?? '';
        input.onchange = () => {
          this.unsavedData[streamField] = input.value;
          this.refreshPreview();
        };

        const label = document.createElement('label');
        label.innerText = labelText;

        group.append(label, input);

        return group;
      };

      this.editPane.querySelector('.civi-riverlea-stream-meta-inputs').append(
        createTextInput(ts('Stream Name'), 'label'),
        createTextInput(ts('Description'), 'description', true)
      );

      // create parallel sets of inptus for light and dark mode
      [
        {selector: '.civi-riverlea-stream-color-inputs-light', varsTarget: 'vars'},
        {selector: '.civi-riverlea-stream-color-inputs-dark', varsTarget: 'vars_dark'}
      ].forEach((colorSet) =>
        this.editPane.querySelector(colorSet.selector).append(
          createVariableInput(ts('Text'), colorSet.varsTarget, '--crm-text-color', 'color'),
          createVariableInput(ts('Background'), colorSet.varsTarget, '--crm-container-bg-color', 'color'),
          createVariableInput(ts('Page Background'), colorSet.varsTarget, '--crm-page-bg-color', 'color'),
          createVariableInput(ts('Primary Highlight'), colorSet.varsTarget, '--crm-primary-color', 'color'),
          createVariableInput(ts('Primary Text'), colorSet.varsTarget, '--crm-primary-text-color', 'color'),
          createVariableInput(ts('Secondary Highlight'), colorSet.varsTarget, '--crm-secondary-text', 'color'),
          createVariableInput(ts('Secondary Text'), colorSet.varsTarget, '--crm-secondary-text-color', 'color'),
      ));

      this.editPane.querySelector('.civi-riverlea-stream-size-inputs').append(
        createVariableInput(ts('Font Size'), 'vars', '--crm-font-size', 'select', 'rem', [
          {value: '0.75', label: ts('Smallest')},
          {value: '0.875', label: ts('Small')},
          {value: '1', label: ts('Default')},
          {value: '1.125', label: ts('Big')},
          {value: '1.5', label: ts('Biggest')},
        ]),
        createVariableInput(ts('Roundness'), 'vars', '--crm-l-radius', 'number', 'rem')
      );

      this.editPane.querySelector('.civi-riverlea-stream-custom-inputs').append(
        createTextInput(ts('Custom CSS'), 'custom_css', true),
        createTextInput(ts('Darkmode Custom CSS'), 'custom_css_dark', true)
      );
    }

    renderButtons() {
      const createButton = CRM.riverlea.createButton;

      const saveButton = createButton(ts('Save'), 'btn-primary', 'save', () => this.save());
      saveButton.type = 'submit';
      const cancelButton = createButton(ts('Cancel'), 'btn-secondary', 'xmark', () => this.reset());
      cancelButton.type = 'submit';

      this.querySelector('.civi-riverlea-stream-editor-buttons')
        .append(saveButton, cancelButton);
    }

  }

  customElements.define('civi-riverlea-stream-editor', CiviRiverleaStreamEditor);

  /**
   * <civi-riverlea-stream-variable stream-field="vars" name="--crm-c-primary" type="color" label="Primary text">
   *
   */
  class CiviRiverleaStreamVariable extends HTMLElement {
    constructor() {
      super();
    }

    connectedCallback() {

      this.editor = this.closest('civi-riverlea-stream-editor');
      if (!this.editor) {
        console.warn('civi-riverlea-stream-variable must be placed within civi-riverlea-stream-editor');
        return;
      }

      this.render();
    }

    get streamField() {
      return this.getAttribute('stream-field');
    }

    get name() {
      return this.getAttribute('name');
    }

    get type() {
      return this.getAttribute('type');
    }

    get unit() {
      return this.getAttribute('unit');
    }

    get label() {
      return this.getAttribute('label');
    }

    get value() {
      if (!this.editor.unsavedData[this.streamField]) {
        return null;
      }
      const val = this.editor.unsavedData[this.streamField][this.name] ?? null;
      if (val && this.unit) {
        return val.replace(this.unit, '');
      }
      return val;
    }

    set value(val) {
      // ensure the target field is an assignable object
      this.editor.unsavedData[this.streamField] = Object.assign({}, this.editor.unsavedData[this.streamField]);

      if (this.unit) {
        val = `${val}${this.unit}`;
      }

      // do the assignment
      this.editor.unsavedData[this.streamField][this.name] = val;

      // special handling for null values
      if (!val && val !== 0) {
        delete this.editor.unsavedData[this.streamField][this.name];
      }
      this.editor.refreshPreview();
    }

    get previewStyles() {
      return this.editor.previewStyles;
    }

    render() {
      const createButton = CRM.riverlea.createButton;

      this.innerHTML = '';

      const label = document.createElement('label');
      label.innerText = this.label;
      this.append(label);

      if (this.type === 'color' && !this.value) {
        const addColor = createButton(ts('Add'), 'btn-add', 'palette', () => {
          // set to a non blank color and then rerender
          this.value = '#ffffff';
          this.render();
        });
        this.append(addColor);
        return;
      }

      let input = null;

      if (this.type === 'select') {
        input = document.createElement('select');
        this.options.forEach((o) => {
          const option = document.createElement('option');
          option.value = o.value;
          option.innerText = o.label;
          option.selected = (o.value === this.value);
          input.append(option);
        });
      }
      else {
        input = document.createElement('input');
        input.type = this.type;
        input.value = this.value;
        input.step = '0.1';
      }

      input.name = `${this.editor.streamName}_${this.streamField}_${this.name}`;
      input.onchange = () => {
        this.value = input.value;
      };

      const clear = createButton(ts('Clear'), 'btn-clear', 'xmark', () => {
        this.value = null;
        this.render();
      });

      const inputAndClear = document.createElement('div');
      inputAndClear.classList.add('input-group');
      inputAndClear.append(input, clear);
      this.append(inputAndClear);
    }
  }

  customElements.define('civi-riverlea-stream-variable', CiviRiverleaStreamVariable);

})(CRM);


