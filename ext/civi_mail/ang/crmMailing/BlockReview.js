(function(angular, $, _) {

  angular.module('crmMailing').directive('crmMailingBlockReview', function (crmMailingPreviewMgr) {
    return {
      scope: {
        crmMailing: '@',
        crmMailingAttachments: '@'
      },
      templateUrl: '~/crmMailing/BlockReview.html',
      link: function (scope, elm, attr) {
        scope.$parent.$watch(attr.crmMailing, function(newValue){
          scope.mailing = newValue;
        });
        scope.$parent.$watch(attr.crmMailingAttachments, function(newValue){
          scope.attachments = newValue;
        });
        scope.crmMailingConst = CRM.crmMailing;
        scope.ts = CRM.ts('civi_mail');
        scope.previewMailing = function previewMailing(mailing, mode) {
          return crmMailingPreviewMgr.preview(mailing, mode);
        };
      }
    };
  });

})(angular, CRM.$, CRM._);
