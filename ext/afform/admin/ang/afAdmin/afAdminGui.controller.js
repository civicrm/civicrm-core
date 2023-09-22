(function(angular, $, _) {
  "use strict";

  angular.module('afAdmin').controller('afAdminGui', function($scope, $route, data) {
    $scope.$ctrl = this;
    this.entity = $route.current.params.entity;
    // Pass through result from api Afform.loadAdminData
    this.data = data;
  });

})(angular, CRM.$, CRM._);
