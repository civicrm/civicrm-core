// source: paypal.initiator.js
(function($, CRM, _, undefined) {

  CRM.initiatePayPalMinibrowser = function(resp) {
    console.log('TODO: paypal_minibrowser', resp);
    // window.location = resp.redirect_uri + (resp.redirect_uri.indexOf('?') < 0 ? '?' : '&') + $.param(e.data);
  };

}(CRM.$, CRM, CRM._));
