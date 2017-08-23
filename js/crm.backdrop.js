// http://civicrm.org/licensing
CRM.$(function($) {
  $('#admin-bar').css('display', 'none');
  $('.crm-hidemenu').click(function(e) {
    $('#admin-bar').css('display', 'block');
  });
  $('#crm-notification-container').click(function(e) {
    if ($('#civicrm-menu').css('display') != 'none') {
      $('#admin-bar').css('display', 'none');
    }
  });
});
