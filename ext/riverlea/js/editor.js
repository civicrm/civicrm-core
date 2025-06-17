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
      // initialise basic internal structure
      this.innerHTML = `
        <div class="crm-flex-box">
          <div class="civi-riverlea-stream-editor-input-container">
          </div>

          <div class="civi-riverlea-stream-editor-preview-container">
          </div>
        </div>
        <div class="civi-riverlea-stream-editor-buttons">
        </div>
      `;

      // add preview iframe
      this.previewFrame = document.createElement('iframe');
      this.previewFrame.src = CRM.url('');
      this.querySelector('.civi-riverlea-stream-editor-preview-container').append(this.previewFrame);

      this.previewFrame.addEventListener('load', () => this.refreshPreview());

      // render the buttons
      this.renderButtons();
      this.renderInputs();
    }

    disconnectedCallback() {
      delete this.previewFrame;
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
      this.renderInputs();
      this.refreshPreview();
      this.dispatchEvent(new Event('reset'));
    }

    refreshPreview() {
      const previewDocument = this.previewFrame.contentDocument;
      if (!previewDocument) {
        // this can happen, it's usually not a problem
        console.debug('Editor preview frame is not loaded');
        return;
      }

      // remove any session preview selector
      Array.from(previewDocument.querySelectorAll('civi-riverlea-preview-selector')).forEach((el) => el.remove());

      let framePreview = previewDocument.querySelector('civi-riverlea-stream-preview');

      if (!framePreview) {
        framePreview = previewDocument.createElement('civi-riverlea-stream-preview');
        previewDocument.querySelector('body').append(framePreview);
      }

      // this ensures any css_file content for the stream is loaded in the frame
      framePreview.render(this.unsavedData);
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

    renderInputs() {
      const inputContainer = this.querySelector('.civi-riverlea-stream-editor-input-container');

      inputContainer.innerHTML = `
        <h2>${this.data.label}</h2>

        <div class="civi-riverlea-stream-dark-toggle">
          <label>${ts('Edit dark mode')}</label>
        </div>

        <fieldset class="civi-riverlea-stream-meta-inputs"></fieldset>

        <fieldset class="panel-body civi-riverlea-stream-color-inputs ">
          <h3>${this.darkMode ? ts('Dark-mode colors') : ts('Colors')}</h3>

        </fieldset>


        <fieldset class="civi-riverlea-stream-size-inputs"></fieldset>
        <fieldset class="civi-riverlea-stream-custom-inputs"></fieldset>
      `;

      const createVariableInput = (label, streamField, name, type, unit = null) => {
        const el = document.createElement('civi-riverlea-stream-variable');
        el.setAttribute('stream-field', streamField);
        el.setAttribute('name', name);
        el.setAttribute('type', type);
        el.setAttribute('label', label);
        if (unit) {
          el.setAttribute('unit', unit);
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


      inputContainer.querySelector('.civi-riverlea-stream-meta-inputs').append(
        createTextInput(ts('Stream Name'), 'label'),
        createTextInput(ts('Description'), 'description', true)
      );

      const darkSwitch = document.createElement('input');
      darkSwitch.type = 'checkbox';
      darkSwitch.checked = this.darkMode;
      darkSwitch.onchange = () => {
        this.darkMode = darkSwitch.checked;
        this.renderInputs();
      };
      inputContainer.querySelector('.civi-riverlea-stream-dark-toggle').append(darkSwitch);

      const colorSchemeVarsTarget = this.darkMode ? 'vars_dark' : 'vars';

      inputContainer.querySelector('.civi-riverlea-stream-color-inputs').append(
        createVariableInput(ts('Background'), colorSchemeVarsTarget, '--crm-c-background', 'color'),
        createVariableInput(ts('Page Background'), colorSchemeVarsTarget, '--crm-c-page-background', 'color'),
        createVariableInput(ts('Text'), colorSchemeVarsTarget, '--crm-c-text', 'color'),
        createVariableInput(ts('Primary background'), colorSchemeVarsTarget, '--crm-c-primary', 'color'),
        createVariableInput(ts('Primary text'), colorSchemeVarsTarget, '--crm-c-primary-text', 'color')
      );

      inputContainer.querySelector('.civi-riverlea-stream-size-inputs').append(
        createVariableInput(ts('Font size'), 'vars', '--crm-font-size', 'number', 'rem'),
        createVariableInput(ts('Roundness'), 'vars', '--crm-roundness', 'number', 'rem')
      );

      inputContainer.querySelector('.civi-riverlea-stream-custom-inputs').append(
        createTextInput(ts('Custom CSS'), 'custom_css', true),
        createTextInput(ts('Darkmode Custom CSS'), 'custom_css_dark', true)
      );
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
        console.log(val);
      }

      // do the assignment
      this.editor.unsavedData[this.streamField][this.name] = val;

      // special handling for null values
      if (!val && val !== 0) {
        delete this.editor.unsavedData[this.streamField][this.name];
      }
      this.editor.refreshPreview();
//      else {
//        // update preview sheet
//        this.previewStyles.insertRule(`:root { ${this.name}: ${val} }`, this.previewStyles.cssRules.length);
//      }

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

      const input = document.createElement('input');
      input.type = this.type;
      input.name = `${this.editor.streamName}_${this.streamField}_${this.name}`;
      input.value = this.value;
      input.onchange = () => {
        this.value = input.value;
      };

      input.step = '0.1';

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


