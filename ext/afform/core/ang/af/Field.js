(function(angular, $, _) {
  var id = 0;
  // Example usage: <div af-fieldset="myModel"><af-field name="do_not_email" /></div>
  angular.module('af').directive('afField', function(crmApi4) {
    return {
      restrict: 'E',
      require: ['^^afForm', '^^afFieldset', '?^^afJoin', '?^^afRepeatItem'],
      templateUrl: '~/af/afField.html',
      scope: {
        fieldName: '@name',
        defn: '='
      },
      link: function($scope, $el, $attr, ctrls) {
        var ts = $scope.ts = CRM.ts('afform'),
          closestController = $($el).closest('[af-fieldset],[af-join],[af-repeat-item]'),
          afForm = ctrls[0];
        $scope.dataProvider = closestController.is('[af-repeat-item]') ? ctrls[3] : ctrls[2] || ctrls[1];
        $scope.fieldId = afForm.getFormMeta().name + '-' + $scope.fieldName + '-' + id++;

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

        // ChainSelect - watch control field & reload options as needed
        if ($scope.defn.input_type === 'ChainSelect') {
          $scope.$watch('dataProvider.getFieldData()[defn.input_attrs.controlField]', function(val) {
            if (val) {
              var params = {
                where: [['name', '=', $scope.fieldName]],
                select: ['options'],
                loadOptions: true,
                values: {}
              };
              params.values[$scope.defn.input_attrs.controlField] = val;
              crmApi4($scope.dataProvider.getEntityType(), 'getFields', params, 0)
                .then(function(data) {
                  $scope.defn.options.length = 0;
                  _.transform(data.options, function(options, label, key) {
                    options.push({key: key, label: label});
                  }, $scope.defn.options);
                });
            }
          });
        }
      }
    };
  });
})(angular, CRM.$, CRM._);
