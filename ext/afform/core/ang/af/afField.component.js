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
      var ts = $scope.ts = CRM.ts('org.civicrm.afform'),
        ctrl = this,
        boolOptions = [{id: true, label: ts('Yes')}, {id: false, label: ts('No')}],
        // Only used for is_primary radio button
        noOptions = [{id: true, label: ''}];

      // Attributes for each of the low & high date fields when using search_range
      this.inputAttrs = [];

      this.$onInit = function() {
        var closestController = $($element).closest('[af-fieldset],[af-join],[af-repeat-item]');
        $scope.dataProvider = closestController.is('[af-repeat-item]') ? ctrl.afRepeatItem : ctrl.afJoin || ctrl.afFieldset;
        $scope.fieldId = ctrl.fieldName + '-' + id++;

        $element.addClass('af-field-type-' + _.kebabCase(ctrl.defn.input_type));


        if (ctrl.defn.search_range) {
          // Initialize value as object unless using relative date select
          var initialVal = $scope.dataProvider.getFieldData()[ctrl.fieldName];
          if (!_.isArray($scope.dataProvider.getFieldData()[ctrl.fieldName]) &&
            (ctrl.defn.input_type !== 'Select' || !ctrl.defn.is_date || initialVal !== '{}')
          ) {
            $scope.dataProvider.getFieldData()[ctrl.fieldName] = {};
          }
          // Initialize inputAttrs (only used for datePickers at the moment)
          if (ctrl.defn.is_date) {
            this.inputAttrs.push(ctrl.defn.input_attrs || {});
            for (var i = 1; i <= 2; ++i) {
              var attrs = _.cloneDeep(ctrl.defn.input_attrs || {});
              attrs.placeholder = attrs['placeholder' + i];
              attrs.timePlaceholder = attrs['timePlaceholder' + i];
              ctrl.inputAttrs.push(attrs);
            }
          }
        }

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
          $scope.$watch('dataProvider.getFieldData()[defn.input_attrs.control_field]', function(val) {
            if (val) {
              var params = {
                where: [['name', '=', ctrl.fieldName]],
                select: ['options'],
                loadOptions: ['id', 'label'],
                values: {}
              };
              params.values[ctrl.defn.input_attrs.control_field] = val;
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

      // Getter/Setter function for fields of type select or entityRef.
      $scope.getSetSelect = function(val) {
        var currentVal = $scope.dataProvider.getFieldData()[ctrl.fieldName];
        // Setter
        if (arguments.length) {
          if (ctrl.defn.is_date) {
            // The '{}' string is a placeholder for "choose date range"
            if (val === '{}') {
              val = !_.isPlainObject(currentVal) ? {} : currentVal;
            }
          }
          // If search_range, this select is the "low" value (the high value uses ng-model without a getterSetter fn)
          else if (ctrl.defn.search_range) {
            return ($scope.dataProvider.getFieldData()[ctrl.fieldName]['>='] = val);
          }
          // A multi-select needs to split string value into an array
          if (ctrl.defn.input_attrs && ctrl.defn.input_attrs.multiple) {
            val = val ? val.split(',') : [];
          }
          return ($scope.dataProvider.getFieldData()[ctrl.fieldName] = val);
        }
        // Getter
        if (_.isArray(currentVal)) {
          return currentVal.join(',');
        }
        if (ctrl.defn.is_date) {
          return _.isPlainObject(currentVal) ? '{}' : currentVal;
        }
        // If search_range, this select is the "low" value (the high value uses ng-model without a getterSetter fn)
        else if (ctrl.defn.search_range) {
          return currentVal['>='];
        }
        return currentVal;
      };

    }
  });
})(angular, CRM.$, CRM._);
