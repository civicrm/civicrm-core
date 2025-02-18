(function(api4) {
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
   }

  initSelector() {
    // initialise theme selector
    this.selector = document.createElement('select');

    // add blank option
    const blank = document.createElement('option');
    blank.innerText = ts('- select -');
    this.selector.append(blank);

    this.selector.addEventListener('change', () => {
      const selected = this.selector.selectedOptions[0];
      if (!selected.value) {
        return;
      }
      CRM.alert(ts('Loading stream: ') + selected.innerText, '', 'info');
      this.loadStream(selected.value);
    });

    this.append(this.selector);

    // load available stream options into selector
    api4('RiverleaStream', 'get', {
      where: [
        ['is_active', '=', true]
      ],
      select: ['name', 'label']
    })
    .then((streams) => streams.forEach((stream) => {
      const option = document.createElement('option');
      option.value = stream.name;
      option.innerText = stream.label;
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
   }

   loadStream(streamName) {
    this.removeDefaultRiver();

    return api4('RiverleaStream', 'render', {
      where: [
        ['name', '=', streamName]
      ]
    })
    .then((records) => records[0])
    .then((record) => record.content)
    .then((content) => this.previewSheet.replace(content))
    .catch((error) => CRM.alert(error));
  }
 }

 // register custom element in our civi namespace
 customElements.define('civi-riverlea-previewer', CiviRiverleaPreviewer);
})(CRM.api4);

(function (CRM) {
  CRM.riverlea = CRM.riverlea || {};

  CRM.riverlea.previewer = () => {
    const previewer = document.createElement('civi-riverlea-previewer');
    document.querySelector('.crm-container').append(previewer);
  };
})(CRM);

