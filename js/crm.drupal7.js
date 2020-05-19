(function($) {
  var menuReadyEvents = {
    adminMenu: $.Deferred(),
    crmMenu: $.Deferred()
  };

  $.when(menuReadyEvents.adminMenu, menuReadyEvents.crmMenu)
    .done(hideMenuToggleButtonForNonAdminUsers);
  $(document).ready(menuReadyEvents.adminMenu.resolve);
  $(document).on('crmLoad', '#civicrm-menu', menuReadyEvents.crmMenu.resolve);

  /**
   * Hides the Menu Toggle Button when the Admin Menu is not available for the user.
   * It also positions the CiviCRM Menu in the right position in case it was displayed
   * under where the Admin Menu would have been. This avoids displaying an empty gap.
   */
  function hideMenuToggleButtonForNonAdminUsers() {
    var $adminToolbar = $('#toolbar');
    var $menuToggleButton = $('#crm-menubar-toggle-position');
    var hasAdminToolbar = $adminToolbar.length > 0;

    if (hasAdminToolbar) {
      return;
    }

    $menuToggleButton.hide();

    if (CRM.menubar.position === 'below-cms-menu') {
      CRM.menubar.togglePosition();
    }
  }
})(CRM.$);
