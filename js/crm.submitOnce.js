(function($, CRM, undefined) {
  $('div.crm-submit-buttons input').click(function() {
    $('div.crm-submit-buttons').css('visibility', 'hidden');
  });

  $('.action-link button, .action-link .button, div.ui-dialog-buttonset button, div.ui-dialog-buttonset .button').click(function() {
    console.log('boo');
    $('div.ui-dialog-buttonset').css('visibility', 'hidden');
  });
}(jQuery, CRM));
