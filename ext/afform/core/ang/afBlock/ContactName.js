(function(angular, $, _) {
  // Example usage: <af-model af-name="myModel"><af-block-contact-name /></af-model>
  angular.module('afBlock').directive('afBlockContactName', function() {
    return {
      restrict: 'AE',
      require: ['^afModel'],
      templateUrl: '~/afBlock/ContactName.html',
      scope: {},
      link: function($scope, $el, $attr, ctrls) {
        var ts = $scope.ts = CRM.ts('afform');
        $scope.afModel = ctrls[0];
      }
    };
  });
})(angular, CRM.$, CRM._);
