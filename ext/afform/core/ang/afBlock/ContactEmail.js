(function(angular, $, _) {
  // Example usage: <div af-block-contact-email="{foo: 1, bar: 2}"></div>
  angular.module('afBlock').directive('afBlockContactEmail', function() {
    return {
      restrict: 'AE',
      require: ['^afModel'],
      templateUrl: '~/afBlock/ContactEmail.html',
      scope: {},
      link: function($scope, $el, $attr, ctrls) {
        var ts = $scope.ts = CRM.ts('afform');
        $scope.afModel = ctrls[0];
      }
    };
  });
})(angular, CRM.$, CRM._);
