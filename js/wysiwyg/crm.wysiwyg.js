// https://civicrm.org/licensing
(function($, _) {
  // This defines an interface which by default only handles plain textareas
  // A wysiwyg implementation can extend this by overriding as many of these functions as needed

  let richTextInputId = 0;

  /**
   * A rich text input with a preview mode
   *   <civi-rich-text-input></civi-rich-text-input>
   *
   */
  class CiviRichTextInput extends HTMLElement {

    constructor() {
      super();

      // initialise child input
      // NOTE: we need to do this here rather than render in order to persist
      // the input value across connection/disconnection
      this.input = document.createElement('textarea');
      // generate a unique id for the element as required by ckeditor etc
      this.input.id = 'civiRichTextInput' + richTextInputId++;
      this.input.style.display = 'none';
    }

    connectedCallback() {
      this.render();
    }

    render() {
      this.innerHTML = `
        <div class="rich-text-toolbar crm-buttons" style="display: none;">
          <button type="button" class="crm-button rich-text-cancel">
            <i class="crm-i fa-cancel" role="img" aria-disabled="true"></i>
          </button>
          <button type="button" class="crm-button rich-text-save">
            <i class="crm-i fa-check" role="img" aria-disabled="true"></i>
          </button>
        </div>
        <div class="rich-text-preview" tabindex="0"></div>
      `;

      // reappend the child input
      this.append(this.input);

      this.toolbar = this.querySelector('.rich-text-toolbar');
      this.cancelButton = this.querySelector('.rich-text-cancel');
      this.saveButton = this.querySelector('.rich-text-save');
      this.preview = this.querySelector('.rich-text-preview');

      // add translated text
      this.cancelButton.append(ts('Cancel'));
      this.saveButton.append(ts('Save'));
      this.preview.title = ts('Click to edit');

      // (re)load any content
      this.renderPreview();

      // open the editor when click/type on preview
      this.preview.onclick = (e) => {
        e.preventDefault();
        this.openEditor();
      };
      this.preview.onkeystroke = () => {
        e.preventDefault();
        this.openEditor();
      };
      // close the editor with the buttons
      this.cancelButton.onclick = () => this.closeEditor();
      this.saveButton.onclick = () => this.saveAndCloseEditor();

      // load token picker if requested
      if (this.hasAttribute('token-picker')) {
        this.loadTokenPicker();
      }
    }

    renderPreview() {
      // if no content set, show an edit icon instead
      this.preview.innerHTML = this.value.length ? this.value : '<i class="crm-i fa-pencil" role="img" aria-label="Edit"></i>';
    }

    loadTokenPicker() {
      this.tokenPicker = document.createElement('input');
      this.tokenPicker.classList.add('rich-text-token-picker', 'form-control', 'crm-auto-width', 'crm-action-menu', 'fa-code', 'collapsible-optgroups');

      this.toolbar.prepend(this.tokenPicker);

      this.tokenPicker.onchange = () => {
        const token = `[${this.tokenPicker.value}]`;
        CRM.wysiwyg.insert(this.input, token);
        CRM.$(this.tokenPicker).select2('val', '');
      };

      CRM.$(this.tokenPicker).crmSelect2({
        data: () => this.getTokens(),
        placeholder: ts('Tokens')
      });
    }

    openEditor() {
      this.setAttribute('editing', true);
      CRM.wysiwyg.create(this.input);
      this.preview.style.display = 'none';
      this.toolbar.style.display = null;
      this.dispatchEvent(new Event('load'));
    }

    closeEditor() {
      CRM.wysiwyg.destroy(this.input);
      this.input.style.display = 'none';
      this.toolbar.style.display = 'none';
      this.preview.style.display = null;
      this.removeAttribute('editing');
    }

    saveAndCloseEditor() {
      this.value = CRM.wysiwyg.getVal(this.input);
      this.closeEditor();
      this.dispatchEvent(new Event('change'));
    }

    getTokens() {
      if (this.closest('af-gui-editor')) {
        const afGuiEditor = angular.element(this.closest('af-gui-editor')).controller('afGuiEditor');
        return {
          results: afGuiEditor.getTokens(this.hasAttribute('include-submission-tokens'))
        };
      }
      else {
        throw new Error('civi-rich-text-input[token-picker] doesn\'t know how to get available tokens outside of af-gui-editor context yet');
      }
    }

    get value() {
      return this.input.value;
    }

    set value(v) {
      if (!v) {
        v = '';
      }
      this.input.value = v;
      CRM.wysiwyg.setVal(this.input, v);
      if (this.preview) {
        this.renderPreview();
      }
    }
  }

  customElements.define('civi-rich-text-input', CiviRichTextInput);


  CRM.wysiwyg = {
    supportsFileUploads: !!CRM.config.wysisygScriptLocation,
    create: function(item) {
      var ret = $.Deferred();
      // Lazy-load the wysiwyg js
      if (CRM.config.wysisygScriptLocation) {
        CRM.loadScript(CRM.config.wysisygScriptLocation).done(function() {
          CRM.wysiwyg._create(item).done(function() {
            ret.resolve();
          });
        });
      } else {
        ret.resolve();
      }
      return ret;
    },
    destroy: _.noop,
    updateElement: _.noop,
    getVal: function(item) {
      return $(item).val();
    },
    setVal: function(item, val) {
      return $(item).val(val);
    },
    insert: function(item, text) {
      CRM.wysiwyg._insertIntoTextarea(item, text);
    },
    focus: function(item) {
      $(item).focus();
    },
    // Fallback function to use when a wysiwyg has not been initialized
    _insertIntoTextarea: function(item, text) {
      var itemObj = $(item);
      var origVal = itemObj.val();
      var origStart = itemObj[0].selectionStart;
      var origEnd = itemObj[0].selectionEnd;
      var newVal = origVal.substring(0, origStart) + text + origVal.substring(origEnd);
      itemObj.val(newVal);
      var newPos = (origStart + text.length);
      itemObj[0].selectionStart = newPos;
      itemObj[0].selectionEnd = newPos;
      itemObj.triggerHandler('change');
      CRM.wysiwyg.focus(item);
    },
    // Create a "collapsed" textarea that expands into a wysiwyg when clicked
    createCollapsed: (item) => {
      const customInput = document.createElement('civi-rich-text-input');
      // use the pre-existing textarea (preserve name etc) for the custom element
      customInput.input = item;
      customInput.input.style.display = 'none';
      // replace the pre-existing textarea with our custom element
      item.replaceWith(customInput);
    }
  };

})(CRM.$, CRM._);
