(function(angular, $, _) {
  // Example usage: <div af-fieldset="myModel"><af-field name="do_not_email" /></div>
  angular.module('af').directive('afField', function() {
    return {
      restrict: 'E',
      require: ['^^afFieldset', '?^afBlockItem'],
      templateUrl: '~/af/afField.html',
      scope: {
        fieldName: '@name',
        defn: '='
      },
      link: function($scope, $el, $attr, ctrls) {
        var ts = $scope.ts = CRM.ts('afform'),
          afFieldset = ctrls[0],
          blockItem = ctrls[1];
        $scope.fieldId = afFieldset.getDefn().modelName + '-' + $scope.fieldName;
        $scope.getData = blockItem ? blockItem.getData : afFieldset.getData;

        $el.addClass('af-field-type-' + _.kebabCase($scope.defn.input_type));

        $scope.getOptions = function() {
          return $scope.defn.options || [{key: '1', label: ts('Yes')}, {key: '0', label: ts('No')}];
        };

        $scope.select2Options = function() {
          return {
            results: _.transform($scope.getOptions(), function(result, opt) {
              result.push({id: opt.key, text: opt.label});
            }, [])
          };
        };
      }
    };
  });
})(angular, CRM.$, CRM._);
