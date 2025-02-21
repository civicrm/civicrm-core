(function(CRM) {
  /**
   * <civi-riverlea-previewer>
   *
   */
 class CiviRiverleaPreviewer extends HTMLElement {
   constructor() {
     super();

     // bind class methods to the instance
     this.removeDefaultRiver = this.removeDefaultRiver.bind(this);
     this.loadStream = this.loadStream.bind(this);
     this.initSelector = this.initSelector.bind(this);
   }

   connectedCallback() {

    // add our constructed stylesheet
    this.previewSheet = new CSSStyleSheet();
    document.adoptedStyleSheets.push(this.previewSheet);

    this.initSelector();

    this.style.position = 'fixed';
    this.style.bottom = '1rem';
    this.style.right = '1rem';
    this.style.zIndex = 1000;
   }

  initSelector() {

    // create selector element
    this.selector = document.createElement('select');

    // add blank option
    this.blankOption = document.createElement('option');
    this.blankOption.value = '';
    this.blankOption.innerText = ts('- select -');
    this.selector.append(this.blankOption);

    // make initial selection based on session storage
    // NOTE: this will fire the change listener to load
    // the stream
    const sessionSelection = CRM.riverlea.getPreviewSetting();

    if (sessionSelection) {
      this.loadStream(sessionSelection);
    }

    this.selector.addEventListener('change', () => {
      const selected = this.selector.selectedOptions[0];

      if (selected.value === '') {
        // unset the session storage
        CRM.riverlea.savePreviewSetting('');
        window.location.reload();
        return;
      }
      else {
        // update the session storage
        CRM.riverlea.savePreviewSetting(selected.value);

        // trigger the switch
        CRM.alert(ts('Loading stream: ') + selected.innerText, '', 'info');
        this.loadStream(selected.value);
      }

    });

    this.append(this.selector);

    // load available stream options into selector
    CRM.api4('RiverleaStream', 'get', {
      where: [
        ['is_active', '=', true]
      ],
      select: ['name', 'label']
    })
    .then((streams) => streams.forEach((stream) => {
      const option = document.createElement('option');
      option.value = stream.name;
      option.innerText = stream.label;
      option.selected = sessionSelection && (option.value === sessionSelection);
      this.selector.append(option);
    }));

   }

   /**
    * find and remove the preloaded river
    **/
   removeDefaultRiver() {
     const styleSheets = Array.from(document.styleSheets);
     const riverSheets = styleSheets.filter((sheet) =>
       sheet.ownerNode && sheet.ownerNode.href && sheet.ownerNode.href.includes('river.css')
     );

     riverSheets.forEach((sheet) => sheet.ownerNode.remove());

     // once the default river is removed, change the blank option to a "Close"
     this.blankOption.innerText = ts('- close -');
   }

   loadStream(streamName) {
    const bodyFilter = document.querySelector('body').style.filter;
    document.querySelector('body').style.filter = 'blur(4px)';

    this.removeDefaultRiver();

    return CRM.api4('RiverleaStream', 'render', {
      where: [
        ['name', '=', streamName]
      ]
    })
    .then((records) => records[0])
    .then((record) => record.content)
    .then((content) => this.previewSheet.replace(content))
    .then(() => document.querySelector('body').style.filter = bodyFilter)
    .catch((error) => CRM.alert(error));
  }
 }


 // register custom element in our civi namespace
 customElements.define('civi-riverlea-previewer', CiviRiverleaPreviewer);
})(CRM);

(function (CRM) {
  CRM.riverlea = CRM.riverlea || {};

  CRM.riverlea.previewer = () => {
    const existing = document.querySelector('civi-riverlea-previewer');
    if (existing) {
      return existing;
    }
    const previewer = document.createElement('civi-riverlea-previewer');
    document.querySelector('body').prepend(previewer);
    return previewer;
  };

  CRM.riverlea.savePreviewSetting = (value) => {
    sessionStorage.setItem('civi_riverlea_previewer_stream', value);
  }

  CRM.riverlea.getPreviewSetting = () => {
    const value = sessionStorage.getItem('civi_riverlea_previewer_stream');
    if (value === '') {
      return false;
    }
    return value;
  };

  CRM.riverlea.checkSessionSetting = () => {
    // if we find a preview setting in the session
    // then reopen the previewer immediately
    if (CRM.riverlea.getPreviewSetting()) {
      CRM.riverlea.previewer();
    }
  };

  CRM.riverlea.checkSessionSetting();
})(CRM);

