(function(angular, $, _) {
  // Example usage: <af-fieldset af-name="myModel"><af-field field-name="do_not_email" /></af-fieldset>
  angular.module('afField').directive('afField', function() {
    return {
      restrict: 'E',
      require: ['^afFieldset', '^afForm'],
      templateUrl: '~/afField/afField.html',
      scope: {
        fieldName: '@',
        defn: '='
      },
      link: function($scope, $el, $attr, ctrls) {
        var ts = $scope.ts = CRM.ts('afform');
        $scope.afFieldset = ctrls[0];
        var modelList = ctrls[1];
        $scope.fieldId = $scope.afFieldset.getDefn().afName + '-' + $scope.fieldName;
        $scope.getData = $scope.afFieldset.getData;

        $scope.getOptions = function() {
          return _.transform($scope.defn.options, function(result, val, key) {
            result.push({id: key, text: val});
          }, []);
        };

        $el.addClass('af-field-type-' + _.kebabCase($scope.defn.input_type));
      }
    };
  });
})(angular, CRM.$, CRM._);
