(function(angular, $, _) {
  var id = 0;
  // Example usage: <div af-fieldset="myModel"><af-field name="do_not_email" /></div>
  angular.module('af').component('afField', {
    require: {
      afFieldset: '^^afFieldset',
      afJoin: '?^^afJoin',
      afRepeatItem: '?^^afRepeatItem'
    },
    templateUrl: '~/af/afField.html',
    bindings: {
      fieldName: '@name',
      defn: '='
    },
    controller: function($scope, $element, crmApi4) {
      var ts = $scope.ts = CRM.ts('afform'),
        ctrl = this,
        boolOptions = [{id: true, label: ts('Yes')}, {id: false, label: ts('No')}],
        // Only used for is_primary radio button
        noOptions = [{id: true, label: ''}];

      this.$onInit = function() {
        var closestController = $($element).closest('[af-fieldset],[af-join],[af-repeat-item]');
        $scope.dataProvider = closestController.is('[af-repeat-item]') ? ctrl.afRepeatItem : ctrl.afJoin || ctrl.afFieldset;
        $scope.fieldId = ctrl.fieldName + '-' + id++;

        $element.addClass('af-field-type-' + _.kebabCase(ctrl.defn.input_type));

        // is_primary field - watch others in this afRepeat block to ensure only one is selected
        if (ctrl.fieldName === 'is_primary' && 'repeatIndex' in $scope.dataProvider) {
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
        if (ctrl.defn.input_type === 'ChainSelect') {
          $scope.$watch('dataProvider.getFieldData()[defn.input_attrs.controlField]', function(val) {
            if (val) {
              var params = {
                where: [['name', '=', ctrl.fieldName]],
                select: ['options'],
                loadOptions: ['id', 'label'],
                values: {}
              };
              params.values[ctrl.defn.input_attrs.controlField] = val;
              crmApi4($scope.dataProvider.getEntityType(), 'getFields', params, 0)
                .then(function(data) {
                  ctrl.defn.options = data.options;
                });
            }
          });
        }

      };

      $scope.getOptions = function () {
        return ctrl.defn.options || (ctrl.fieldName === 'is_primary' && ctrl.defn.input_type === 'Radio' ? noOptions : boolOptions);
      };

      $scope.select2Options = function() {
        return {
          results: _.transform($scope.getOptions(), function(result, opt) {
            result.push({id: opt.id, text: opt.label});
          }, [])
        };
      };

    }
  });
})(angular, CRM.$, CRM._);
