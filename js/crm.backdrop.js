// http://civicrm.org/licensing
CRM.$(function($) {
  $('#admin-bar').css('display', 'none');
  $('.crm-hidemenu').click(function(e) {
    $('#admin-bar').css('display', 'block');
  });
  $('#crm-notification-container').on('click', '#crm-restore-menu', function() {
    $('#admin-bar').css('display', 'none');
  });
});
