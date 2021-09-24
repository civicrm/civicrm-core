(function(angular, $, _) {

  angular.module('crmMsgadm').controller('MsgtpluiGenericCtrl', function($scope, crmUiHelp) {
    var ts = $scope.ts = CRM.ts('crmMsgadm');
    var hs = $scope.hs = crmUiHelp({file: 'CRM/MessageAdmin/crmMsgadm'}); // See: templates/CRM/MessageAdmin/crmMsgadm.hlp
  });

})(angular, CRM.$, CRM._);
