// http://civicrm.org/licensing

// When on a CiviCRM page the CiviCRM toolbar tab should be active
localStorage.setItem('Drupal.toolbar.activeTabID', JSON.stringify('toolbar-item-civicrm'));

(function($) {
  function adjustToggle() {
    if ($(window).width() < 768) {
      $('#civicrm-menu-nav .crm-menubar-toggle-btn').css({
        left: '' + $('#toolbar-item-civicrm').offset().left + 'px',
        width: '' + $('#toolbar-item-civicrm').innerWidth() + 'px'
      });
    }
  }
  $(window).resize(adjustToggle);
  $(document).on('crmLoad', adjustToggle);

  // Wait for document.ready so Drupal's jQuery is available to this script
  $(function($) {
    // Need Drupal's jQuery to listen to this event
    jQuery(document).on('drupalToolbarTabChange', function(event, tab) {
      if (CRM.menubar && CRM.menubar.position === 'below-cms-menu') {
        var action = jQuery(tab).is('#toolbar-item-civicrm') ? 'show' : 'hide';
        CRM.menubar[action]();
      }
    });
  });

})(CRM.$);
