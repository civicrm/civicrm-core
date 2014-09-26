// http://civicrm.org/licensing
CRM.$(function($) {
  $(document).on('crmLoad', function() {
    // This is drupal's old-school way of listening for 'load' type events. It has to be called manually.
    Drupal.attachBehaviors(this);
  });
});
