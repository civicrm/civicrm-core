(function(angular, $, _) {
  // example: <div crm-mailing-block-preview crm-mailing="myMailing" on-preview="openPreview(myMailing, preview.mode)" on-send="sendEmail(myMailing,preview.recipient)">
  // note: the directive defines a variable called "preview" with any inputs supplied by the user (e.g. the target recipient for an example mailing)

  angular.module('crmMailing').directive('crmMailingBlockPreview', function(crmUiHelp) {
    return {
      templateUrl: '~/crmMailing/BlockPreview.html',
      link: function(scope, elm, attr) {
        scope.$watch(attr.crmMailing, function(newValue) {
          scope.mailing = newValue;
        });
        scope.crmMailingConst = CRM.crmMailing;
        scope.ts = CRM.ts(null);
        scope.hs = crmUiHelp({file: 'CRM/Mailing/MailingUI'});
        scope.testContact = {email: CRM.crmMailing.defaultTestEmail};
        scope.testGroup = {gid: null};

        scope.doPreview = function(mode) {
          scope.$eval(attr.onPreview, {
            preview: {mode: mode}
          });
        };
        scope.doSend = function doSend(recipient) {
          scope.$eval(attr.onSend, {
            preview: {recipient: recipient}
          });
        };

        scope.previewTestGroup = function(e) {
          var $dialog = $(this);
          $dialog.html('<div class="crm-loading-element"></div>').parent().find('button[data-op=yes]').prop('disabled', true);
          $dialog.dialog('option', 'title', ts('Send to %1', {1: _.pluck(_.where(scope.crmMailingConst.groupNames, {id: scope.testGroup.gid}), 'title')[0]}));
          CRM.api3('contact', 'get', {
            group: scope.testGroup.gid,
            options: {limit: 0},
            return: 'display_name,email'
          }).done(function(data) {
            var count = 0,
            // Fixme: should this be in a template?
              markup = '<ol>';
            _.each(data.values, function(row) {
              // Fixme: contact api doesn't seem capable of filtering out contacts with no email, so we're doing it client-side
              if (row.email) {
                count++;
                markup += '<li>' + row.display_name + ' - ' + row.email + '</li>';
              }
            });
            markup += '</ol>';
            markup = '<h4>' + ts('A test message will be sent to %1 people:', {1: count}) + '</h4>' + markup;
            if (!count) {
              markup = '<div class="messages status"><i class="crm-i fa-exclamation-triangle"></i> ' +
              (data.count ? ts('None of the contacts in this group have an email address.') : ts('Group is empty.')) +
              '</div>';
            }
            $dialog
              .html(markup)
              .trigger('crmLoad')
              .parent().find('button[data-op=yes]').prop('disabled', !count);
          });
        };
      }
    };
  });

})(angular, CRM.$, CRM._);
