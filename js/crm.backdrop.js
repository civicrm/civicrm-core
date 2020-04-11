// http://civicrm.org/licensing
(function($) {
  $(document).on('crmLoad', '#civicrm-menu', function() {
    if (Backdrop.settings.admin_bar && !Backdrop.settings.admin_bar.position_fixed) {
      $('body').addClass('backdrop-admin-bar-position-absolute');
    }
  });
})(CRM.$);
