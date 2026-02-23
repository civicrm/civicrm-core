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

}(CRM.$, CRM, CRM._));
