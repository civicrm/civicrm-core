// http://civicrm.org/licensing
CRM.$(function($) {
  $(document)
    .on('crmLoad', function(e) {
      // This is drupal's old-school way of listening for 'load' type events. It has to be called manually.
      Drupal.attachBehaviors(e.target);
    })
    .on('crmUnload', function(e) {
      // This function doesn't exist in D6 so call conditionally
      if (typeof Drupal.detachBehaviors === 'function') {
        Drupal.detachBehaviors(e.target);
      }
    })
    .on('dialogopen', function(e) {
      // D7 hack to get the toolbar out of the way (CRM-15341)
      $('#toolbar').css('z-index', '100');
    })
    .on('dialogclose', function(e) {
      if ($('.ui-dialog-content:visible').not(e.target).length < 1) {
        // D7 hack, restore toolbar position (CRM-15341)
        $('#toolbar').css('z-index', '');
      }
    })
});
