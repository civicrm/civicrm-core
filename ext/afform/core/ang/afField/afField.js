(function(angular, $, _) {
  // Example usage: <af-model af-name="myModel"><af-field field-name="do_not_email" /></af-model>
  angular.module('afField').directive('afField', function() {
    return {
      restrict: 'AE',
      require: ['^afModel'],
      templateUrl: '~/afField/afField.html',
      scope: {
        fieldName: '@'
      },
      link: function($scope, $el, $attr, ctrls) {
        var ts = $scope.ts = CRM.ts('afform');
        $scope.afModel = ctrls[0];
      }
    };
  });
})(angular, CRM.$, CRM._);
