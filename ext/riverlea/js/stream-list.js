(function (CRM) {

  /**
   * <civi-riverlea-stream-list>
   *
   */
  class CiviRiverleaStreamList extends HTMLElement {
    constructor() {
      super();

      this.streamData = {};
    }

    connectedCallback() {
      this.innerHTML = `
        <div class="civi-theme-selections">
          <div class="civi-backend">
            <h3></h3>
            <div></div>
          </div>
          <div class="civi-frontend">
            <h3></h3>
            <div></div>
          </div>
        </div>
        <div class="civi-themes-available">
          <h3></h3>
          <ul></ul>
        </div>
      `;
      this.querySelector('.civi-backend h3').innerText = ts('Current Backend Theme');
      this.backendSlot = this.querySelector('.civi-backend div');

      this.querySelector('.civi-frontend h3').innerText = ts('Current Frontend Theme');
      this.frontendSlot = this.querySelector('.civi-frontend div');

      // create list for other streams
      this.querySelector('.civi-themes-available h3').innerText = ts('Other Available Themes');
      this.ul = this.querySelector('.civi-themes-available ul');

      // button to create a new stream FIXME only allow cloning for now
      // const addButton = CRM.riverlea.createButton(ts('Add new stream'), 'btn-primary', 'plus', () => this.createNew().then(() => this.fetchAndRender()));
      // this.querySelector('.civi-other-themes').append(addButton);

      // create editor dialog
      this.editorDialog = document.createElement('dialog');
      this.editorDialog.classList.add('crm-dialog', 'civi-riverlea-stream-edit-dialog');
      this.append(this.editorDialog);

      this.fetchAndRender();
    }

    async fetchAndRender() {
      this.ul.innerHTML = '<div class="crm-loading-element"></div>';

      return Promise.all([this.fetchRecords(), this.fetchSettingState()])
        .then(() => this.render());
    }


    async fetchRecords() {
      this.streams = {};

      return CRM.api4('RiverleaStream', 'getWithFileContent', {
        where: [['id', '!=', 0]]
      })
      .then((streams) => streams.forEach((stream) => this.streams[stream.name] = stream));
    }

    fetchSettingState() {
      this.settingState = {};

      if (CRM?.riverlea.previewSession) {
        const previewSession = CRM.riverlea.previewSession();
        this.settingState.preview = previewSession ? previewSession.selected : null;
      }

      return CRM.api4('Setting', 'get', { select: ['theme_backend', 'theme_frontend'] })
        .then((results) => results.forEach((record) => {
          const settingNameWithoutPrefix = record.name.split('_')[1];
          this.settingState[settingNameWithoutPrefix] = record.value;
        }));
    }

    createNew() {
      return CRM.api4('RiverleaStream', 'create', {
        values: {
          label: 'New stream' ,
          name: 'custom_' + window.crypto.getRandomValues(new Uint32Array(1)).join('')
        }
      });
    }

    clone(streamName) {
      const sourceData = this.streams[streamName] ?? null;
      if (!sourceData) {
        console.warn('Missing stream to clone');
        return;
      }
      const cloneData = {...sourceData};

      // remove id
      delete cloneData.id;
      // allow editing clones of reserved themes
      cloneData.is_reserved = false;

      // get a unique name
      let i = 0;
      let nameClash = true;
      while (nameClash) {
        i += 1;
        cloneData.name = `${sourceData.name}_${i}`;
        nameClash = Object.keys(this.streams).includes(cloneData.name);
      }

      // set a label based on the name index
      cloneData.label += ts(' (Copy %1)', {1: i});

      // set a description based on the copy
      cloneData.description = ts('Copied from ') + sourceData.label;

      return CRM.api4('RiverleaStream', 'create', {
        values: cloneData
      })
      .then(() => this.fetchAndRender())
      .then(() => this.openEditorDialog(cloneData.name));
    }

    delete(streamName) {
      // check if the stream to delete is in use
      for (const [key, value] of Object.entries(this.settingState)) {
        if (value !== streamName) {
          continue;
        }
        if (key === 'preview') {
          this.updateSetting('preview', null);
          continue;
        }
        // this stream is set for
        CRM.alert(ts('Cannot delete stream as currently set for ') + key);
        return;
      }

      // always preview the stream we are editing
      return CRM.api4('RiverleaStream', 'delete', {
        where: [['name', '=', streamName]]
      })
      .then(() => CRM.alert(ts('Stream deleted')))
      .then(() => delete this.streams[streamName])
      .then(() => this.render());
    }

    render() {
      this.backendSlot.innerHTML = '';
      this.frontendSlot.innerHTML = '';
      this.ul.innerHTML = '';

      Object.values(this.streams).forEach((stream) => {
        const card = document.createElement('civi-riverlea-stream-card');
        card.setData(stream);
        card.setState('is_preview', (stream.name === this.settingState.preview));
        card.setState('is_backend', (stream.name === this.settingState.backend));
        card.setState('is_frontend', (stream.name === this.settingState.frontend));

        if (card.state.is_backend && card.state.is_frontend) {
          this.backendSlot.append(card);
          this.querySelector('.civi-backend h3').innerText = ts('Current Theme (Backend + Frontend)');
          this.querySelector('.civi-frontend').hidden = true;
        }
        else if (card.state.is_backend) {
          this.backendSlot.append(card);
          this.querySelector('.civi-backend h3').innerText = ts('Current Backend Theme');
        }
        else if (card.state.is_frontend) {
          this.frontendSlot.append(card);
          this.querySelector('.civi-frontend').hidden = false;
        }
        else {
          // add to the list of other streams
          const li = document.createElement('li');
          li.append(card);
          this.ul.append(li);
        }
      });

      if (!this.backendSlot.hasChildNodes()) {
        this.backendSlot.innerText = ts('Backend theme is currently set to non-Riverlea theme: %1', {1: this.settingState.backend});
      }
      if (!this.frontendSlot.hasChildNodes()) {
        this.frontendSlot.innerText = ts('Frontend theme is currently set to non-Riverlea theme: %1', {1: this.settingState.frontend});
      }
    }

    openEditorDialog(streamName) {
      // clear the dialog before opening
      this.editorDialog.innerHTML = '';

      const editor = document.createElement('civi-riverlea-stream-editor');
      editor.streamName = streamName;
      editor.setStreamData(this.streams[streamName]);

      this.editorDialog.append(editor);

      // listen to editor events - close the dialog and refresh if needed
      editor.addEventListener('reset', () => this.editorDialog.close());
      editor.addEventListener('save', () => this.editorDialog.close() || this.fetchAndRender());

      this.editorDialog.showModal();
    }

    updateSetting(targetSetting, streamName) {
      if (!['preview', 'backend', 'frontend'].includes(targetSetting)) {
        console.warn('Unknown theme setting key to activate: ' + targetSetting);
        return;
      }

      this.settingState[targetSetting] = streamName;

      // update session for preview setting
      if (targetSetting === 'preview') {
        this.querySelectorAll('civi-riverlea-stream-card').forEach((card) => {
          card.setState('is_preview', (card.streamName === streamName));
        });

        CRM.riverlea.previewSession({
          streams: this.streams,
          selected: streamName
        });
        CRM.riverlea.previewer().load();
      }

      // send site setting to the server and update the card positions
      if (targetSetting === 'backend' || targetSetting === 'frontend') {
        return CRM.api4('RiverleaStream', 'activate', {
          where: [['name', '=', streamName]],
          backOrFront: targetSetting
        })
        .then(() => this.render());
      }

      return Promise.resolve();
    }

    confirmThenUpdate(targetSetting, streamName, streamLabel) {
      return CRM.confirm({
        message: ts(`Are you sure you want to set %1 as the %2 theme? This affects all site users.`, {
          1: streamLabel,
          2: targetSetting
        })
      })
      .on('crmConfirm:yes', () => this.updateSetting(targetSetting, streamName));
    }

  }

  customElements.define('civi-riverlea-stream-list', CiviRiverleaStreamList);

  /**
   * <civi-riverlea-stream-card >
   *
   */
  class CiviRiverleaStreamCard extends HTMLElement {
    constructor() {
      super();

      // initialise state array
      this.state = {};
    }

    connectedCallback() {

      this.streamList = this.closest('civi-riverlea-stream-list');

      if (!this.streamList) {
        console.warn('Please add civi-riverlea-stream-card inside a civi-riverlea-stream-list element.');
        return;
      }

      if (!this.data) {
        this.fetchAndRender();
      }
      else {
        this.render();
      }

    }

    setData(data) {
      this.data = data;
    }

    get streamName() {
      return this.data.name;
    }

    render() {
      this.innerHTML = `
      <div class="panel panel-info">
        <div class="panel-heading">
          <h3>${this.data.label}</h3>
          <div class="civi-riverlea-stream-header-buttons crm-buttons"></div>
        </div>

        <div class="panel-body">
          <p>
            ${ this.data.description ? this.data.description : '' }
          </p>

          <details class="civi-riverlea-stream-details crm-accordion-settings">
            <summary>${ ts('More info') }
          </details>
        </div>
        <div class="panel-footer">
          <div class="civi-riverlea-stream-panel-buttons crm-buttons"></div>
        </div>
      </div>
      `;

      this.renderDetailsArea(this.querySelector('.civi-riverlea-stream-details'));

      this.renderHeaderButtons(this.querySelector('.civi-riverlea-stream-header-buttons'));
      this.renderPanelButtons(this.querySelector('.civi-riverlea-stream-panel-buttons'));

      this.rendered = true;

      this.renderState();
    }

    renderDetailsArea(container) {
      const detailsFields = [
        { key: 'extension', label: ts('Extension') },
        { key: 'file_prefix', label: ts('File Prefix') },
        { key: 'css_file', label: ts('CSS File') },
        { key: 'css_file_dark', label: ts('Dark-mode CSS File') },
        { key: 'vars', label: ts('Variables') },
        { key: 'vars_dark', label: ts('Dark-mode Variables') },
        { key: 'custom_css', label: ts('Custom CSS') },
        { key: 'custom_css_dark', label: ts('Dark-mode Custom CSS') }
      ];

      detailsFields.forEach((field) => {
        const value = this.data[field.key] ?? null;

        if (value) {

          const renderedValue = (typeof value === 'string') ? value : JSON.stringify(value);

          const detailItem = document.createElement('div');
          detailItem.innerHTML = `
            <label>${field.label}</label>
            <code>${renderedValue}</code>
          `;
          container.append(detailItem);
        }
      });

      // hide the details area if empty
      container.hidden = !container.children.length;
    }

    renderPanelButtons(container) {
      const createButton = CRM.riverlea.createButton;

      // note: we stash these buttons as instance properties so they can be
      // updated in renderState
      this.setPreview = createButton('Preview', 'btn-set-preview', 'eye', () => this.streamList.updateSetting('preview', this.streamName));
      this.setBackend = createButton('Set for Backend', 'btn-set-backend', 'briefcase', () => this.streamList.confirmThenUpdate('backend', this.streamName, this.data.label));
      this.setFrontend = createButton('Set for Frontend', 'btn-set-frontend', 'shop', () => this.streamList.confirmThenUpdate('frontend', this.streamName, this.data.label));

      container.append(this.setPreview, this.setBackend, this.setFrontend);
    }

    renderHeaderButtons(container) {
      const createButton = CRM.riverlea.createButton;

      const cloneBtn = createButton('Clone', 'btn-clone', 'copy', () => this.streamList.clone(this.streamName).then(() => CRM.alert(ts('Stream cloned'), '', 'success')));
      container.append(cloneBtn);

      if (!this.data.is_reserved) {
        const editBtn = createButton('Edit', 'btn-update', 'pen', () => this.streamList.openEditorDialog(this.streamName, this.data));

        const deleteBtn = createButton('Delete', 'btn-delete', 'trash',
          () => CRM.confirm({
              message: ts(`Are you sure you want to delete %1?`, {1: this.data.label})
            })
            .on('crmConfirm:yes', () => this.streamList.delete(this.streamName))
        );

        container.append(editBtn, deleteBtn);
      }

    }

    setState(prop, value) {
      this.state[prop] = value;
      if (this.rendered) {
        this.renderState();
      }
    }

    renderState() {
      const updateButtonState = (button, is_disabled) => {
        button.disabled = is_disabled;
        button.classList.toggle('btn-stream-selected', is_disabled);
      };

      updateButtonState(this.setPreview, this.state.is_preview);
      updateButtonState(this.setBackend, this.state.is_backend);
      updateButtonState(this.setFrontend, this.state.is_frontend);

      this.classList.toggle('is-set-preview', this.state.is_preview);
      this.classList.toggle('is-set-backend', this.state.is_backend);
      this.classList.toggle('is-set-frontend', this.state.is_frontend);
    }

    fetchAndRender() {
      CRM.api4('RiverleaStream', 'get', {
        where: [['name', '=', this.streamName]],
      })
      .then((records) => {
        if (!records.length) {
          throw new Error('Failed to refetch stream data for ' + this.streamName);
        }
        return records[0];
      })
      .then((record) => this.data = record)
      .then(() => this.render());
    }
  }

  customElements.define('civi-riverlea-stream-card', CiviRiverleaStreamCard);

})(CRM);


