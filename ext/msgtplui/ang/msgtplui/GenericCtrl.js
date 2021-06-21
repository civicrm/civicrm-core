(function(angular, $, _) {

  angular.module('msgtplui').controller('MsgtpluiGenericCtrl', function($scope, crmUiHelp) {
    var ts = $scope.ts = CRM.ts('msgtplui');
    var hs = $scope.hs = crmUiHelp({file: 'CRM/Msgtplui/msgtplui'}); // See: templates/CRM/Msgtplui/msgtplui.hlp
  });

})(angular, CRM.$, CRM._);
