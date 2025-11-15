// source: paypal.initiator.js
(function ($, CRM, _, undefined) {

  CRM.ppcp = CRM.ppcp || [];
  CRM.ppcp.onboard = function(request) {
    window.location = CRM.url('civicrm/oauth-client/return', request);
  };

}(CRM.$, CRM, CRM._));
