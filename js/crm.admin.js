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
        ts('Normal');
        ts('Rotate right');
        ts('Rotate left');
        ts('Rotate 180');
        ts('Flip horizontal');
        ts('Flip vertical');
      });
    });
})(CRM.$);
