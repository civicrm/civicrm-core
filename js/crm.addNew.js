// http://civicrm.org/licensing
// Opens the "new item" dialog after creating a container/set
CRM.$(function($) {
  var emptyMsg = $('.crm-empty-table');
  if (emptyMsg.length) {
    $('.action-link a.button', '#crm-container').click();
  }
});
