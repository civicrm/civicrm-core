(function(angular, $, _) {
  "use strict";

  angular.module('afAdmin').controller('afAdminGui', function($scope, $routeParams) {
    var ts = $scope.ts = CRM.ts(),
      ctrl = $scope.$ctrl = this;

    // Edit mode
    this.name = $routeParams.name;
    // Create mode
    this.type = $routeParams.type;

  });

})(angular, CRM.$, CRM._);
