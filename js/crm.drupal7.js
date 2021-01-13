// https://civicrm.org/licensing
(function($) {
  "use strict";

  $(document).on('crmLoad', '#civicrm-menu', hideMenuToggleButtonForNonAdminUsers);

  /**
   * Hides the Menu Toggle Button when the Admin Menu is not available for the user.
   */
  function hideMenuToggleButtonForNonAdminUsers() {
    $(document).ready(function () {
      setTimeout(function () {
        if (!$('#toolbar').length) {
          // check admin menu with different id present before removing toggle button.
          if (!$('#admin-menu').length) {
            CRM.menubar.removeToggleButton();
          }
        }
    }, 2000);
    });
  }

})(CRM.$);

