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
     this.initSelector = this.initSelector.bind(this);
     this.setSelected = this.setSelected.bind(this);
     this.render = this.render.bind(this);
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

    this.selector.addEventListener('change', () => {
      const selectedOption = this.selector.selectedOptions[0];

      // update stored selection
      this.setSelected(selectedOption.value);
    });

    this.append(this.selector);

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
      option.selected = (option.value === this.selected);
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

    this.defaultRemoved = true;

    // once the default river is removed, change the blank option to a "Close"
    this.blankOption.innerText = ts('- close -');
   }

   setSelected(selection) {
    if (this.selected === selection) {
      return;
    }

    this.selected = selection;
    // export to session storage
    CRM.riverlea.sessionPreviewSetting(selection);

    if (selection === '') {
      // '' = close => reload the page to close the previewer
      CRM.alert(ts('Closing previewer'), '', 'info');
      window.location.reload();
      return;
    }

    const selectedOption = Array.from(this.selector.options).find((option) => (option.value === this.selected));
    if (selectedOption) {
      CRM.alert(ts('Previewing stream: ') + selectedOption.innerText, '', 'info');
    }
    this.render();
   }

   render() {
    if (this.rendering) {
      return;
    }
    this.rendering = true;

    const selected = this.selected;
    // ensure the UI selector matches local storage
    Array.from(this.selector.options).forEach((option) => option.selected = (option.value === selected));

    // if nothing selected we are done
    if (!selected || selected === '') {
      return;
    }

    // add a whole-page loading filter
    const previousFilter = document.querySelector('body').style.filter;
    const restorePageFilter = () => document.querySelector('body').style.filter = previousFilter;
    document.querySelector('body').style.filter = 'blur(4px)';

    // ensure default stream sheet is gone
    this.removeDefaultRiver();

    return CRM.api4('RiverleaStream', 'render', {
      where: [
        ['name', '=', selected]
      ]
    })
    .then((records) => {
      if (!records.length) {
        throw new Error('Stream preview render returned no result');
      }
      return records[0];
    })
    .then((record) => record.content)
    .then((content) => this.previewSheet.replace(content))
    .catch((error) => CRM.alert(error ?? 'Error loading stream'))
    .finally(() => {
      restorePageFilter();
      this.rendering = false;
    });
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

  CRM.riverlea.sessionPreviewSetting = (newValue) => {
    if (newValue !== undefined) {
      sessionStorage.setItem('civi_riverlea_previewer_stream', newValue);
      return newValue;
    }

    const storedValue = sessionStorage.getItem('civi_riverlea_previewer_stream');
    if (storedValue === '') {
      return false;
    }
    return storedValue;
  };

  CRM.riverlea.checkSessionSetting = () => {
    // if we find a preview setting in the session
    // then reopen the previewer immediately
    const sessionSetting = CRM.riverlea.sessionPreviewSetting();
    if (sessionSetting) {
      CRM.riverlea.previewer().setSelected(sessionSetting);
    }
  };

  CRM.riverlea.checkSessionSetting();
})(CRM);

