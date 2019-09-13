(function(angular, $, _) {
  // Example usage: <af-fieldset model="myModel"><af-block-contact-name /></af-fieldset>
  angular.module('afBlock').directive('afBlockContactName', function() {
    return {
      restrict: 'AE',
      require: ['^afFieldset'],
      templateUrl: '~/afBlock/ContactName.html',
      scope: {},
      link: function($scope, $el, $attr, ctrls) {
        var ts = $scope.ts = CRM.ts('afform');
        $scope.afFieldset = ctrls[0];
      }
    };
  });
})(angular, CRM.$, CRM._);
