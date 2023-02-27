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
    controller: function($scope, $element, crmApi4, $timeout, $location) {
      var ts = $scope.ts = CRM.ts('org.civicrm.afform'),
        ctrl = this,
        // Prefix used for SearchKit explicit joins
        namePrefix = '',
        boolOptions = [{id: true, label: ts('Yes')}, {id: false, label: ts('No')}],
        // Used to store chain select options loaded on-the-fly
        chainSelectOptions = null,
        // Only used for is_primary radio button
        noOptions = [{id: true, label: ''}];

      // Attributes for each of the low & high date fields when using search_range
      this.inputAttrs = [];

      this.$onInit = function() {
        var closestController = $($element).closest('[af-fieldset],[af-join],[af-repeat-item]');
        $scope.dataProvider = closestController.is('[af-repeat-item]') ? ctrl.afRepeatItem : ctrl.afJoin || ctrl.afFieldset;
        $scope.fieldId = _.kebabCase(ctrl.fieldName) + '-' + id++;

        $element.addClass('af-field-type-' + _.kebabCase(ctrl.defn.input_type));

        if (this.defn.name !== this.fieldName) {
          namePrefix = this.fieldName.substr(0, this.fieldName.length - this.defn.name.length);
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
          var controlField = namePrefix + ctrl.defn.input_attrs.control_field;
          $scope.$watch('dataProvider.getFieldData()["' + controlField + '"]', function(val) {
            // After switching option list, remove invalid options
            function validateValue() {
              var options = $scope.getOptions(),
                value = $scope.dataProvider.getFieldData()[ctrl.fieldName];
              if (_.isArray(value)) {
                _.remove(value, function(item) {
                  return !_.find(options, function(option) {return option.id == item;});
                });
              } else if (value && !_.find(options, function(option) {return option.id == value;})) {
                $scope.dataProvider.getFieldData()[ctrl.fieldName] = '';
              }
            }
            if (val && (typeof val === 'number' || val.length)) {
              $('input[crm-ui-select]', $element).addClass('loading').prop('disabled', true);
              var params = {
                name: ctrl.afFieldset.getFormName(),
                modelName: ctrl.afFieldset.getName(),
                fieldName: ctrl.fieldName,
                joinEntity: ctrl.afJoin ? ctrl.afJoin.entity : null,
                values: $scope.dataProvider.getFieldData()
              };
              crmApi4('Afform', 'getOptions', params)
                .then(function(data) {
                  $('input[crm-ui-select]', $element).removeClass('loading').prop('disabled', !data.length);
                  chainSelectOptions = data;
                  validateValue();
                });
            } else {
              chainSelectOptions = null;
              validateValue();
            }
          }, true);
        }

        // Wait for parent controllers to initialize
        $timeout(function() {
          // Unique field name = entity_name index . join . field_name
          var entityName = ctrl.afFieldset.getName(),
            joinEntity = ctrl.afJoin ? ctrl.afJoin.entity : null,
            uniquePrefix = '',
            urlArgs = $location.search();
          if (entityName) {
            var index = ctrl.getEntityIndex();
            uniquePrefix = entityName + (index ? index + 1 : '') + (joinEntity ? '.' + joinEntity : '') + '.';
          }
          // Set default value from url with uniquePrefix + fieldName
          if (urlArgs && urlArgs[uniquePrefix + ctrl.fieldName]) {
            setValue(urlArgs[uniquePrefix + ctrl.fieldName]);
          }
          // Set default value from url with fieldName only
          else if (urlArgs && urlArgs[ctrl.fieldName]) {
            $scope.dataProvider.getFieldData()[ctrl.fieldName] = urlArgs[ctrl.fieldName];
          }
          // Set default value based on field defn
          else if (ctrl.defn.afform_default) {
            setValue(ctrl.defn.afform_default);
          }

          if (ctrl.defn.search_range) {
            // Initialize value as object unless using relative date select
            var initialVal = $scope.dataProvider.getFieldData()[ctrl.fieldName];
            if (!_.isArray($scope.dataProvider.getFieldData()[ctrl.fieldName]) &&
              (ctrl.defn.input_type !== 'Select' || !ctrl.defn.is_date || initialVal === '{}')
            ) {
              $scope.dataProvider.getFieldData()[ctrl.fieldName] = {};
            }
            // Initialize inputAttrs (only used for datePickers at the moment)
            if (ctrl.defn.is_date) {
              ctrl.inputAttrs.push(ctrl.defn.input_attrs || {});
              for (var i = 1; i <= 2; ++i) {
                var attrs = _.cloneDeep(ctrl.defn.input_attrs || {});
                attrs.placeholder = attrs['placeholder' + i];
                attrs.timePlaceholder = attrs['timePlaceholder' + i];
                ctrl.inputAttrs.push(attrs);
              }
            }
          }
        });
      };

      // Set default value; ensure data type matches input type
      function setValue(value) {
        if (ctrl.defn.input_type === 'Number' && ctrl.defn.search_range) {
          if (!_.isPlainObject(value)) {
            value = {
              '>=': +(('' + value).split('-')[0] || 0),
              '<=': +(('' + value).split('-')[1] || 0),
            };
          }
        } else if (ctrl.defn.input_type === 'Number') {
          value = +value;
        }
        // Initialze search range unless the field also has options (as in a date search) and
        // the default value is a valid option.
        else if (ctrl.defn.search_range && !_.isPlainObject(value) &&
          !(ctrl.defn.options && _.findWhere(ctrl.defn.options, {id: value}))
        ) {
          value = {
            '>=': ('' + value).split('-')[0],
            '<=': ('' + value).split('-')[1] || '',
          };
        }
        $scope.dataProvider.getFieldData()[ctrl.fieldName] = value;
      }

      // Get the repeat index of the entity fieldset (not the join)
      ctrl.getEntityIndex = function() {
        // If already in a join repeat, look up the outer repeat
        if ('repeatIndex' in $scope.dataProvider && $scope.dataProvider.afRepeat.getRepeatType() === 'join') {
          return $scope.dataProvider.outerRepeatItem ? $scope.dataProvider.outerRepeatItem.repeatIndex : 0;
        } else {
          return ctrl.afRepeatItem ? ctrl.afRepeatItem.repeatIndex : 0;
        }
      };

      ctrl.isReadonly = function() {
        if (ctrl.defn.is_id) {
          return ctrl.afFieldset.getEntity().actions.update === false;
        }
        // TODO: Not actually used, but could be used if we wanted to render displayOnly
        // fields as more than just raw data. I think we probably ought to do so for entityRef fields
        // Since the ids are kind of meaningless. Making that change would require adding a function
        // to get the widget template rather than just concatenating the input_type into an ngInclude.
        return ctrl.defn.input_type === 'DisplayOnly';
      };

      // ngChange callback from Existing entity field
      ctrl.onSelectEntity = function() {
        if (ctrl.defn.is_id) {
          var val = $scope.getSetSelect();
          var entity = ctrl.afFieldset.modelName;
          var index = ctrl.getEntityIndex();
          ctrl.afFieldset.afFormCtrl.loadData(entity, index, val);
        }
      };

      // Params for the Afform.submitFile API when uploading a file field
      ctrl.getFileUploadParams = function() {
        return {
          modelName: ctrl.afFieldset.getName(),
          fieldName: ctrl.fieldName,
          joinEntity: ctrl.afJoin ? ctrl.afJoin.entity : null,
          entityIndex: ctrl.getEntityIndex(),
          joinIndex: ctrl.afJoin && $scope.dataProvider.repeatIndex || null
        };
      };

      ctrl.getAutocompleteFieldName = function() {
        return ctrl.afFieldset.modelName + (ctrl.afJoin ? ('+' + ctrl.afJoin.entity) : '') + ':' + ctrl.fieldName;
      };

      $scope.getOptions = function () {
        return chainSelectOptions || ctrl.defn.options || (ctrl.fieldName === 'is_primary' && ctrl.defn.input_type === 'Radio' ? noOptions : boolOptions);
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
          return ($scope.dataProvider.getFieldData()[ctrl.fieldName] = val);
        }
        // Getter
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
