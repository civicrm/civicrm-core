// https://civicrm.org/licensing
(function($, CRM, _, undefined) {

  // Block access to the initators panel.
  // usage: $('.foo').obscureInitiator({message: 'Foo Bar'});
  // Note: This is similar to $.block(), but that's used around AJAX operations and shows a spinner -- the spinner is very confusing in this context.
  $.fn.obscureInitiator = function(options) {
    const $el = this;
    if ($el.css('position') === 'static') {
      $el.css('position', 'relative');
    }

    if ($el.children('.crm-initiator-obscure-content').length === 0) {
      $el.wrapInner('<div class="crm-initiator-obscure-content"></div>');
    }

    $el.find('.crm-initiator-obscure-content').css({
      filter: 'blur(2px) grayscale(30%)',
      pointerEvents: 'none'
    });

    // Create overlay text (not blurred)
    const $overlay = $('<div class="crm-initiator-overlay-message">')
      .text(options.message)
      .css({
        position: 'absolute',
        top: 0,
        left: 0,
        width: '100%',
        height: '100%',
        background: 'rgba(255, 255, 255, 0.6)',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        textAlign: 'center',
        fontSize: '1.1em',
        color: '#222',
        zIndex: 9999,
        padding: '1em',
        boxSizing: 'border-box',
        pointerEvents: 'auto',
        cursor: 'not-allowed'
      });

    if ($el.find('.crm-initiator-overlay-message').length === 0) {
      $el.append($overlay);
    }
  };

  // CRM.initiator = function initiator(params) {
  //   const initiatorUrl = params.url;
  //   const unsavedChanges = CRM.utils.initialValueChanged($('form[data-warn-changes=true]:visible'));
  //
  //   if (unsavedChanges) {
  //     CRM.alert(
  //       '<p>' + ts('Please save changes first.') + '</p>',
  //       ts('Unsaved Changes')
  //     );
  //   }
  //   else {
  //     $.get(initiatorUrl).then(function onInitiate(resp) {
  //       var region = $('.initiator-body');
  //       if (region.empty()) {
  //         region = $('body').append('<div class="initiator-body" style="display: none;">');
  //       }
  //       region.append(resp);
  //     });
  //   }
  //
  //   return false;
  // };

  // class CrmInitiators extends HTMLElement {
  //   constructor() {
  //     super();
  //   }
  //
  //   // Called when the element is added to the document's DOM
  //   connectedCallback() {
  //     this.innerHTML = '';
  //
  //     // Get the options attribute value
  //     const optionsAttr = this.getAttribute('options');
  //
  //     if (!optionsAttr) {
  //       this.textContent = 'Error: "options" attribute is missing.';
  //       return;
  //     }
  //
  //     let records;
  //     try {
  //       records = JSON.parse(optionsAttr);
  //     } catch (e) {
  //       this.textContent = `Error: Invalid JSON in "options" attribute. ${e.message}`;
  //       return;
  //     }
  //
  //     if (!Array.isArray(records)) {
  //       this.textContent = 'Error: "options" must be a JSON array.';
  //       return;
  //     }
  //
  //     const ts = CRM.ts();
  //     records.forEach(record => {
  //       if (record.title && record.url) {
  //         const button = document.createElement('a');
  //
  //         // button.textContent = record.title;
  //
  //         button.textContent = ts('Connect to %1', {1: record.title});
  //         button.classList.add('btn', 'btn-primary', 'btn-xs');
  //         button.addEventListener('click', (event) => {
  //           event.stopPropagation();
  //           CRM.initiator(record);
  //           return false;
  //         });
  //
  //         this.appendChild(button);
  //         this.appendChild(document.createElement('br'));
  //       }
  //     });
  //   }
  //
  //   // Optional: Clean up when the element is removed
  //   disconnectedCallback() {
  //     // For simplicity, we skip manually removing listeners since the component is removed
  //     // and the browser's garbage collector will handle the cleanup.
  //   }
  // }

  // // Define the custom element name and link it to the class
  // customElements.define('crm-initiators', CrmInitiators);

}(CRM.$, CRM, CRM._));
