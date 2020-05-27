(function(angular, $, _) {

  // Controller for the in-place msg-template management
  angular.module('crmMailing').controller('MsgTemplateCtrl', function MsgTemplateCtrl($scope, crmMsgTemplates, dialogService) {
    var ts = $scope.ts = CRM.ts(null);
    $scope.crmMsgTemplates = crmMsgTemplates;
    $scope.checkPerm = CRM.checkPerm;
    // @return Promise MessageTemplate (per APIv3)
    $scope.saveTemplate = function saveTemplate(mailing) {
      var model = {
        selected_id: mailing.msg_template_id,
        tpl: {
          msg_title: '',
          msg_subject: mailing.subject,
          msg_text: mailing.body_text,
          msg_html: mailing.body_html
        }
      };
      var options = CRM.utils.adjustDialogDefaults({
        autoOpen: false,
        height: 'auto',
        width: '40%',
        title: ts('Save Template')
      });
      return dialogService.open('saveTemplateDialog', '~/crmMailing/SaveMsgTemplateDialogCtrl.html', model, options)
        .then(function(item) {
          mailing.msg_template_id = item.id;
          return item;
        });
    };

    // @param int id
    // @return Promise
    $scope.loadTemplate = function loadTemplate(mailing, id) {
      return crmMsgTemplates.get(id).then(function(tpl) {
        mailing.msg_template_id = tpl.id;
        mailing.subject = tpl.msg_subject;
        mailing.body_text = tpl.msg_text;
        mailing.body_html = tpl.msg_html;
      });
    };
  });

})(angular, CRM.$, CRM._);
