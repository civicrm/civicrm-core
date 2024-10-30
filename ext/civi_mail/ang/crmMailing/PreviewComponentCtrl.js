(function(angular, $, _) {

  // Controller for the "Preview Mailing Component" segment
  // which displays header/footer/auto-responder
  angular.module('crmMailing').controller('PreviewComponentCtrl', function PreviewComponentCtrl($scope, dialogService) {
    var ts = $scope.ts = CRM.ts('civi_mail');

    $scope.previewComponent = function previewComponent(title, componentId) {
      var component = _.where(CRM.crmMailing.headerfooterList, {id: "" + componentId});
      if (!component || !component[0]) {
        CRM.alert(ts('Invalid component ID (%1)', {
          1: componentId
        }));
        return;
      }
      var options = CRM.utils.adjustDialogDefaults({
        autoOpen: false,
        title: title // component[0].name
      });
      dialogService.open('previewComponentDialog', '~/crmMailing/PreviewComponentDialogCtrl.html', component[0], options);
    };
  });

})(angular, CRM.$, CRM._);
