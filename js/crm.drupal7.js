// https://civicrm.org/licensing
(function($) {
  "use strict";

  $(document).on('crmLoad', '#civicrm-menu', hideMenuToggleButtonForNonAdminUsers);

  /**
   * Hides the Menu Toggle Button when the Admin Menu is not available for the user.
   */
  function hideMenuToggleButtonForNonAdminUsers() {
    $(document).ready(function() {
      if (!$('#toolbar').length) {
        CRM.menubar.removeToggleButton();
      }
    });
  }

})(CRM.$);
