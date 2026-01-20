(function(angular, $, _) {
  let afFieldId = 0;
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
    controller: function($scope, $element, crmApi4, $timeout) {
      var ts = $scope.ts = CRM.ts('org.civicrm.afform'),
        ctrl = this,
        // Prefix used for SearchKit explicit joins
        namePrefix = '',
        // Either defn.options or chain select options loaded on-the-fly
        fieldOptions = null;

      // Attributes for each of the low & high date fields when using search_range
      this.inputAttrs = [];

      this.$onInit = function() {
        var closestController = $($element).closest('[af-fieldset],[af-join],[af-repeat-item]');
        $scope.dataProvider = closestController.is('[af-repeat-item]') ? ctrl.afRepeatItem : ctrl.afJoin || ctrl.afFieldset;
        $scope.fieldId = _.kebabCase(ctrl.fieldName) + '-' + afFieldId++;

        $element.addClass('af-field-type-' + _.kebabCase(ctrl.defn.input_type));

        if (this.defn.input_attrs && this.defn.input_attrs.multiple) {
          $element.addClass('af-field-type-multiple');
        }

        this.fkEntity = this.defn.fk_entity || null;

        if (this.defn.name !== this.fieldName) {
          if (!this.defn.name) {
            console.error('Missing field definition for: ' + this.fieldName);
            return;
          }
          namePrefix = this.fieldName.substr(0, this.fieldName.length - this.defn.name.length);
        }

        if (this.defn.search_operator) {
          this.search_operator = this.defn.search_operator;
        }

        fieldOptions = this.defn.options || null;

        // Ensure boolean options are truly boolean
        if (this.defn.data_type === 'Boolean') {
          if (fieldOptions) {
            fieldOptions.forEach((option) => option.id = !!option.id);
          } else {
            fieldOptions = [{id: true, label: ts('Yes')}, {id: false, label: ts('No')}];
          }
        }

        // is_primary field - watch others in this afRepeat block to ensure only one is selected
        if (ctrl.fieldName === 'is_primary' && 'repeatIndex' in $scope.dataProvider) {
          fieldOptions = [{id: true, label: ''}];
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
        if (ctrl.defn.input_type === 'ChainSelect' && ctrl.defn.input_attrs.control_field) {
          const controlField = namePrefix + ctrl.defn.input_attrs.control_field;
          $scope.$watch('dataProvider.getFieldData()["' + controlField + '"]', function(val) {
            // After switching option list, remove invalid options
            function validateValue() {
              var options = $scope.getOptions(),
                value = $scope.dataProvider.getFieldData()[ctrl.fieldName];
              if (_.isArray(value)) {
                _.remove(value, function(item) {
                  return !_.find(options, (option) => option.id == item);
                });
              } else {
                if (value && !_.find(options, (option) => option.id == value)) {
                  value = '';
                }
                // Hack: Because the option list changed, Select2 sometimes fails to update the value.
                // Manual updates like this shouldn't be necessary with ngModel binding, but can't find a better fix yet:
                // See https://lab.civicrm.org/dev/core/-/issues/5415
                $('input[crm-ui-select]', $element).val(value).change();
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
                  fieldOptions = data;
                  validateValue();
                });
            } else {
              fieldOptions = null;
              validateValue();
            }
          }, true);
        }

        // Dynamic foreign key
        if (ctrl.defn.input_type === 'EntityRef' && ctrl.defn.dfk_entities && ctrl.defn.input_attrs.control_field) {
          const controlField = namePrefix + ctrl.defn.input_attrs.control_field;
          $scope.$watch('dataProvider.getFieldData()["' + controlField + '"]', function(val) {
            if (val && val.length) {
              if (Array.isArray(val)) {
                ctrl.fkEntity = ctrl.defn.dfk_entities[val[0]];
              } else {
                ctrl.fkEntity = ctrl.defn.dfk_entities[val];
              }
            } else {
              ctrl.fkEntity = null;
            }
          });
        }

        // Wait for parent controllers to initialize
        $timeout(function() {
          initializeValue(true);
        });

        function initializeValue(firstLoad) {
          // Unique field name = entity_name index . join . field_name
          var entityName = ctrl.afFieldset.getName(),
            joinEntity = ctrl.afJoin ? ctrl.afJoin.entity : null,
            uniquePrefix = '',
            urlArgs = $scope.$parent.routeParams;
          if (entityName) {
            var index = ctrl.getEntityIndex();
            uniquePrefix = entityName + (index ? index + 1 : '') + (joinEntity ? '.' + joinEntity : '') + '.';
          }
          // Set default value from url with uniquePrefix + fieldName
          if (urlArgs && ((uniquePrefix + ctrl.fieldName) in urlArgs)) {
            setValue(urlArgs[uniquePrefix + ctrl.fieldName]);
          }
          // Set default value from url with fieldName only
          else if (urlArgs && (ctrl.fieldName in urlArgs)) {
            setValue(urlArgs[ctrl.fieldName]);
          }
          else if (urlArgs && urlArgs._s) {
            setValue(ctrl.afFieldset.getSearchParamSetFieldValue(ctrl.fieldName));
          }
          else if (firstLoad && ctrl.afFieldset.getStoredValue(ctrl.fieldName) !== undefined) {
            setValue(ctrl.afFieldset.getStoredValue(ctrl.fieldName));
          }
          // Set default value based on field defn
          else if ('afform_default' in ctrl.defn) {
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
        }

        // Reinitialize value when resetting form
        $scope.$on('afFormReset', function() {
          delete $scope.dataProvider.getFieldData()[ctrl.fieldName];
          initializeValue(false);
        });
      };

      // When this field is removed by afIf, also remove its value from the data model.
      $scope.$on('afIfDestroy', function() {
        if (ctrl.defn.input_type !== 'DisplayOnly') {
          delete $scope.dataProvider.getFieldData()[ctrl.fieldName];
        }
      });

      // correct the type for the value, make sure numbers are numbers and not string
      function correctValueType(value, dataType) {
        // let's skip type correction for null values
        if (value === null) {
          return value;
        }

        // if value is a number than change it to number
        if (Array.isArray(value)) {
          return value.map((val) => correctValueType(val, dataType));
        } else if (dataType === 'Integer' || dataType === 'Float') {
          return Number(value);
        } else if (dataType === 'Boolean') {
          return (value == 1);
        }
        return value;
      }

      this.isMultiple = function() {
        return (
          (['Select', 'EntityRef', 'ChainSelect'].includes(ctrl.defn.input_type) && ctrl.defn.input_attrs.multiple) ||
          ((ctrl.defn.input_type === 'CheckBox' || ctrl.defn.input_type === 'Toggle') && ctrl.defn.data_type !== 'Boolean') ||
          ((ctrl.defn.input_type === 'Hidden' || ctrl.defn.input_type === 'DisplayOnly') && (ctrl.defn.serialize || ctrl.defn.data_type === 'Array'))
        );
      };

      // Set default value; ensure data type matches input type
      function setValue(value) {
        // For values passed from the url, split
        if (typeof value === 'string' && ctrl.isMultiple()) {
          value = value.split(',');
        }
        // When reloading values for fields with operators, the stored value is an object "operator"
        if (typeof value === 'object' && value !== null && ctrl.search_operator) {
          // if the operator is a user select, load from the passed value
          // (we expect the value to be an Object with a single key)
          if (ctrl.defn.expose_operator) {
            ctrl.search_operator = Object.keys(value)[0];
          }
          value = value[ctrl.search_operator] ? value[ctrl.search_operator] : null;
        }
        // Support "Select Current User" default
        if (ctrl.defn.input_type === 'EntityRef' && ['Contact', 'Individual'].includes(ctrl.fkEntity) && value === 'user_contact_id') {
          value = CRM.config.cid;
        }
        // correct the value type
        if (ctrl.defn.input_type !== 'DisplayOnly') {
          value = correctValueType(value, ctrl.defn.data_type);
        }

        if (ctrl.defn.input_type === 'Date' && typeof value === 'string' && value.startsWith('now')) {
          value = getRelativeDate(value, ctrl.defn.input_attrs.time);
        }
        if (ctrl.defn.input_type === 'Number' && ctrl.defn.search_range) {
          if (!_.isPlainObject(value)) {
            value = {
              '>=': +(('' + value).split('-')[0] || 0),
              '<=': +(('' + value).split('-')[1] || 0),
            };
          }
        } else if (ctrl.defn.input_type === 'Number') {
          value = Number(value);
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
        $scope.getSetValue(value);
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
        if (ctrl.defn.input_attrs && ctrl.defn.input_attrs.autofill && !ctrl.afJoin) {
          return ctrl.afFieldset.getEntity().actions[ctrl.defn.input_attrs.autofill] === false;
        }
        // TODO: Not actually used, but could be used if we wanted to render displayOnly
        // fields as more than just raw data. I think we probably ought to do so for entityRef fields
        // Since the ids are kind of meaningless. Making that change would require adding a function
        // to get the widget template rather than just concatenating the input_type into an ngInclude.
        return ctrl.defn.input_type === 'DisplayOnly';
      };

      ctrl.isDisabled = function() {
        if (ctrl.isReadonly()) {
          return true;
        }
        if (ctrl.defn.input_type === 'EntityRef' && !ctrl.fkEntity) {
          return true;
        }
        return false;
      };

      ctrl.getDisplayValue = function(value) {
        if (value === undefined || value === null || value === '' || (Array.isArray(value) && !value.length)) {
          return '';
        }
        if (fieldOptions) {
          let keys = Array.isArray(value) ? value : [value];
          let options = fieldOptions.filter((option) => keys.includes(option.id));
          return options.map((option) => option.label).join(', ');
        }
        if (ctrl.defn.data_type === 'Date' || ctrl.defn.data_type === 'Timestamp') {
          try {
            return CRM.utils.formatDate(value, null, ctrl.defn.data_type === 'Timestamp');
          } catch (e) {
            return '';
          }
        }
        if (ctrl.fkEntity) {
          // EntityRef fields: fetch label via API if not already present
          // This is async, so we return a placeholder and update later
          const ids = Array.isArray(value) ? value : [value];
          if (!ctrl._entityLabels) {
            ctrl._entityLabels = {};
          }
          // Call autocomplete api
          if (!(ids.join() in ctrl._entityLabels)) {
            ctrl._entityLabels[ids.join()] = null;
            const params = ctrl.getAutocompleteParams();
            params.ids = ids;
            crmApi4(ctrl.fkEntity, 'autocomplete', params)
              .then(function(result) {
                // Join all labels
                ctrl._entityLabels[ids.join()] = result.map((item) => item.label).join(', ');
              });
          }
          return ctrl._entityLabels[ids.join()] || ts('Loading...');
        }
        return value;
      };

      // ngChange callback from Existing entity field
      ctrl.onSelectEntity = function() {
        if (ctrl.defn.input_attrs && ctrl.defn.input_attrs.autofill) {
          const val = $scope.getSetSelect();
          const entity = ctrl.afFieldset.modelName;
          const entityIndex = ctrl.getEntityIndex();
          const joinEntity = ctrl.afJoin ? ctrl.afJoin.entity : null;
          const joinIndex = ctrl.afJoin && $scope.dataProvider.repeatIndex || 0;
          ctrl.afFieldset.afFormCtrl.loadData(entity, entityIndex, val, ctrl.defn.name, joinEntity, joinIndex);
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

      ctrl.getAutocompleteParams = function() {
        let fieldName = ctrl.afFieldset.getName();
        // Append join name which will be unpacked by AfformAutocompleteSubscriber::processAfformAutocomplete
        if (ctrl.afJoin) {
          fieldName += '+' + ctrl.afJoin.entity;
        }
        fieldName += ':' + ctrl.fieldName;
        return {
          formName: 'afform:' + ctrl.afFieldset.getFormName(),
          fieldName: fieldName,
          values: $scope.dataProvider.getFieldData()
        };
      };

      $scope.getOptions = function () {
        return fieldOptions;
      };

      $scope.select2Options = function() {
        return {
          results: _.transform($scope.getOptions(), function(result, opt) {
            result.push({id: opt.id, text: opt.label});
          }, [])
        };
      };

      this.onChangeOperator = function() {
        $scope.dataProvider.getFieldData()[ctrl.fieldName] = {};
      };

      // Getter/Setter function for most fields (except select & entityRef)
      $scope.getSetValue = function(val) {
        var currentVal = $scope.dataProvider.getFieldData()[ctrl.fieldName];
        // Setter
        if (arguments.length) {
          if (ctrl.search_operator) {
            if (typeof currentVal !== 'object') {
              $scope.dataProvider.getFieldData()[ctrl.fieldName] = {};
            }
            return ($scope.dataProvider.getFieldData()[ctrl.fieldName][ctrl.search_operator] = val);
          }
          return ($scope.dataProvider.getFieldData()[ctrl.fieldName] = val);
        }
        // Getter
        if (ctrl.search_operator) {
          return (currentVal || {})[ctrl.search_operator];
        }
        return currentVal;
      };

      // Getter/Setter function for fields of type select or entityRef.
      $scope.getSetSelect = function(val) {
        var currentVal = $scope.dataProvider.getFieldData()[ctrl.fieldName];
        // Setter - transform raw string/array from Select2 into correct data type
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
          else if (ctrl.search_operator) {
            if (typeof currentVal !== 'object') {
              $scope.dataProvider.getFieldData()[ctrl.fieldName] = {};
            }
            return ($scope.dataProvider.getFieldData()[ctrl.fieldName][ctrl.search_operator] = val);
          }
          if (ctrl.defn.data_type === 'Boolean') {
            return ($scope.dataProvider.getFieldData()[ctrl.fieldName] = (val === 'true'));
          }
          if (ctrl.defn.data_type === 'Integer' || ctrl.defn.data_type === 'Float') {
            if (typeof val === 'string') {
              return ($scope.dataProvider.getFieldData()[ctrl.fieldName] = val.length ? Number(val) : null);
            } else if (Array.isArray(val)) {
              return ($scope.dataProvider.getFieldData()[ctrl.fieldName] = val.map(Number));
            }
          }
          return ($scope.dataProvider.getFieldData()[ctrl.fieldName] = val);
        }
        // Getter - transform data into a simple string or array for Select2
        if (ctrl.defn.is_date) {
          return _.isPlainObject(currentVal) ? '{}' : currentVal;
        }
        // If search_range, this select is the "low" value (the high value uses ng-model without a getterSetter fn)
        else if (ctrl.defn.search_range) {
          return currentVal['>='];
        }
        else if (ctrl.search_operator) {
          return (currentVal || {})[ctrl.search_operator];
        }
        // Convert false to "false" and 0 to "0"
        else if (!ctrl.isMultiple() && (typeof currentVal === 'boolean' || typeof currentVal === 'number')) {
          return JSON.stringify(currentVal);
        }
        return currentVal;
      };

      function getRelativeDate(dateString, includeTime) {
        const parts = dateString.split(' ');
        const baseDate = new Date();
        let unit = parts[2] || 'day';
        let offset = parseInt(parts[1] || '0', 10);

        switch (unit) {
          case 'week':
            offset *= 7;
            break;

          case 'year':
            offset *= 365;
        }
        let newDate = new Date(baseDate.getTime() + offset * 24 * 60 * 60 * 1000);
        let localYear = newDate.getFullYear();
        let localMonth = String(newDate.getMonth() + 1).padStart(2, '0'); // Months are 0-indexed
        let localDay = String(newDate.getDate()).padStart(2, '0');
        let defaultDate = `${localYear}-${localMonth}-${localDay}`; // Format YYYY-MM-DD

        if (includeTime) {
          defaultDate += ' ' + newDate.toTimeString().slice(0,8);
        }
        return defaultDate;
      }

    }
  });
})(angular, CRM.$, CRM._);
