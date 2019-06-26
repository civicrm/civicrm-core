(function(angular, $, _) {
  // Example usage: <af-model af-name="myModel"><af-field field-name="do_not_email" /></af-model>
  angular.module('afField').directive('afField', function() {
    return {
      restrict: 'E',
      require: ['^afModel', '^afModelList'],
      templateUrl: '~/afField/afField.html',
      scope: {
        fieldName: '@',
        fieldDefn: '='
      },
      link: function($scope, $el, $attr, ctrls) {
        var ts = $scope.ts = CRM.ts('afform');
        $scope.afModel = ctrls[0];
        var modelList = ctrls[1];
        $scope.fieldId = $scope.afModel.getDefn().name + '-' + $scope.fieldName;
        $scope.getData = $scope.afModel.getData;

        $scope.getOptions = function() {
          return _.transform($scope.fieldDefn.options, function(result, val, key) {
            result.push({id: key, text: val});
          }, []);
        };

        $el.addClass('af-field-type-' + _.kebabCase($scope.fieldDefn.input_type));
      }
    };
  });
})(angular, CRM.$, CRM._);
