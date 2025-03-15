(function(angular, $, _) {
  // Empty module just loads all available modules.
  angular.module('afformStandalone', CRM.angular.modules)

    .controller('AfformStandalonePageCtrl', function($scope) {
      $scope.afformTitle = '';
      $scope.afformDisplayForm = true;
      $scope.afformConfirmationMessage = '';
    });

})(angular, CRM.$, CRM._);
