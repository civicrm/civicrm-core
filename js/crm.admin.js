// https://civicrm.org/licensing
(function($) {
  "use strict";
  $(document)
    .on('crmLoad', function(e) {
      $('.crm-icon-picker', e.target).not('.iconpicker-widget').each(function() {
        var $el = $(this);
        CRM.loadScript(CRM.config.resourceBase + 'js/jquery/jquery.crmIconPicker.js').done(function() {
          $el.crmIconPicker();
        });
        // Hack to get the strings in this lazy-loaded file translated
        ts('None');
      });
    })
})(CRM.$);
