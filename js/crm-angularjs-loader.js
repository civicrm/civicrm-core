// http://civicrm.org/licensing
(function($, _) {
  "use strict";

  $(document).on('crmLoad', function(e) {
    $('crm-angular-js', e.target).not('.ng-scope').each(function() {
      angular.bootstrap(this, $(this).attr('modules').split());
    });
  });

})(CRM.$, CRM._);
