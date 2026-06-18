(function(angular, $, _) {
  "use strict";

  angular.module('afAdmin').controller('afAdminList', function($scope) {
    const ts = $scope.ts = CRM.ts('org.civicrm.afform_admin');
  });

})(angular, CRM.$, CRM._);
