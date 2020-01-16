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
          afForm = ctrls[0],
          boolOptions = [{key: true, label: ts('Yes')}, {key: false, label: ts('No')}],
          // Only used for is_primary radio button
          noOptions = [{key: true, label: ''}];
        $scope.dataProvider = closestController.is('[af-repeat-item]') ? ctrls[3] : ctrls[2] || ctrls[1];
        $scope.fieldId = afForm.getFormMeta().name + '-' + $scope.fieldName + '-' + id++;

        $el.addClass('af-field-type-' + _.kebabCase($scope.defn.input_type));

        $scope.getOptions = function() {
          return $scope.defn.options || ($scope.fieldName === 'is_primary' && $scope.defn.input_type === 'Radio' ? noOptions : boolOptions);
        };

        $scope.select2Options = function() {
          return {
            results: _.transform($scope.getOptions(), function(result, opt) {
              result.push({id: opt.key, text: opt.label});
            }, [])
          };
        };

        // is_primary field - watch others in this afRepeat block to ensure only one is selected
        if ($scope.fieldName === 'is_primary' && 'repeatIndex' in $scope.dataProvider) {
          $scope.$watch('dataProvider.afRepeat.getEntityController().getData()', function (items, prev) {
            var index = $scope.dataProvider.repeatIndex;
            // Set first item to primary if there isn't a primary
            if (items && !index && !_.find(items, 'is_primary')) {
              $scope.dataProvider.getFieldData().is_primary = true;
            }
            // Set this item to not primary if another has been selected
            if (items && prev && items.length === prev.length && items[index].is_primary && prev[index].is_primary &&
              _.filter(items, 'is_primary').length > 1
            ) {
              $scope.dataProvider.getFieldData().is_primary = false;
            }
          }, true);
        }

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
