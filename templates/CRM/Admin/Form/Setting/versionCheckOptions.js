// https://civicrm.org/licensing
CRM.$(function($) {
  'use strict';
  $(document)
    .off('.crmVersionCheckOptions')
    .on('click.crmVersionCheckOptions', 'a.crm-setVersionCheckIgnoreDate', function(e) {
      var isSecurity = !($(this).closest('.ui-notify-message').hasClass('info'));
      var msg = '<p>' + ts('This will suppress notifications about all currently available updates.') + ' ';
      if (isSecurity) {
        msg += ts('Notifications will resume when a new security advisory is published.') +
          '</p><p>' +
          ts('Warning: Do this only if you have already taken alternate steps to ensure your site is secure.');
      } else {
        msg += ts('Notifications will resume when a new release is published.');
      }
      msg += '</p>';
      CRM.confirm({message: msg, title: $(this).text()})
        .on('crmConfirm:yes', function() {
          CRM.api3('setting', 'create', {versionCheckIgnoreDate: new Date().toISOString().slice(0,10)}, true);
          CRM.closeAlertByChild('a.crm-setVersionCheckIgnoreDate');
        });
      e.preventDefault();
    });
});
