(function($, crmMenubar) {
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
    var $body = $('body');
    var $menuToggleButton = $('#crm-menubar-toggle-position');
    var hasAdminToolbar = $adminToolbar.length > 0;
    var isCrmMenubarBelowAdminbar = $body.hasClass('crm-menubar-below-cms-menu');

    if (hasAdminToolbar) {
      return;
    }

    $menuToggleButton.hide();

    if (isCrmMenubarBelowAdminbar) {
      crmMenubar.togglePosition();
    }
  }
})(CRM.$, CRM.menubar);
