(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchTasks').factory('searchTaskLazyFieldsTrait', function(crmApi4, searchTaskFieldsTrait) {
    return angular.extend({}, searchTaskFieldsTrait, {
      _reloading: false,
      _pendingReload: false,
      _lastLoadedDepValue: null,

      // Extract scalar ID from autocomplete {id, text} objects
      getFkId: function(value) {
        return value && _.isObject(value) ? value.id : value;
      },

      // Set up reactive field loading: watches a FK field (e.g. event_id) and optionally
      // a dependent field (e.g. role_id). Fields are lazy-loaded scoped by the FK value,
      // so custom groups matching the context appear.
      setupLazyFields: function($scope, config) {
        const ctrl = this;

        ctrl._normalizeDepField(config);

        // FK changed — load fields scoped by the new value, or clear if deselected
        $scope.$watch('values.' + config.fkField, function() {
          var id = ctrl.getFkId($scope.values[config.fkField]);
          if (id) {
            ctrl._loadFields(id, config);
          } else {
            ctrl._clearLazyFields(config);
          }
        });

        // Deep watch dep field: reload fields when user changes the dependent value (e.g. role_id).
        // Comparison against last loaded value prevents reload on programmatic updates.
        // pendingReload cascade guard prevents duplicate calls during an active load.
        if (config.depField) {
          $scope.$watch(function() {
            return ctrl.getFieldValue(config.depField);
          }, function(newVal) {
            var id = ctrl.getFkId($scope.values[config.fkField]);
            if (id && Array.isArray(newVal) && newVal.length && !_.isEqual(newVal, ctrl._lastLoadedDepValue)) {
              if (ctrl._reloading) {
                ctrl._pendingReload = true;
              } else {
                ctrl._loadFields(id, config);
              }
            }
          }, true);
        }

        return ctrl;
      },

      // Fetch fields scoped by FK + dep values. The "values" param filters custom groups
      // via SpecGatherer::getCustomGroupFilters.
      _loadFields: function(fkValue, config) {
        this._pendingReload = false;
        this._reloading = true;
        if (this.fields.length) {
          this.refreshing = true;
        }
        var getFieldsValues = {};
        getFieldsValues[config.fkField] = fkValue;
        // Pass current dep field selection so role-scoped (or dep-scoped) custom groups are included
        if (config.depField) {
          var depVal = this.getFieldValue(config.depField);
          if (depVal && depVal.length) {
            getFieldsValues[config.depField] = depVal;
          }
        }

        var excluded = ['contact_id', config.fkField];
        if (config.getFieldsExclude) {
          excluded = excluded.concat(config.getFieldsExclude);
        }

        var apiCalls = {
          getFields: [config.entityName, 'getFields', {
            action: 'create',
            select: ['name', 'label', 'description', 'input_type', 'data_type', 'serialize', 'options', 'fk_entity', 'nullable', 'required', 'default_value'],
            loadOptions: ['id', 'name', 'label', 'description', 'color', 'icon'],
            where: [
              ['name', 'NOT IN', excluded],
              ['deprecated', '=', false],
              ['readonly', '=', false],
            ],
            values: getFieldsValues
          }]
        };
        if (config.getAdditionalApiCalls) {
          Object.assign(apiCalls, config.getAdditionalApiCalls(fkValue));
        }

        var ctrl = this;
        crmApi4(apiCalls).then(function(results) {
          // Mutate in place (length = 0, push, splice) to preserve shared array references
          // that searchTaskFieldsTrait closures (getField, fieldInUse) hold via ctrl.fields / ctrl.values
          ctrl.fields.length = 0;
          var keepFields = {};
          results.getFields.forEach(function(f) { keepFields[f.name] = true; });
          for (var i = ctrl.values.length - 1; i >= 0; i--) {
            if (!keepFields[ctrl.values[i][0]]) {
              ctrl.values.splice(i, 1);
            }
          }
          ctrl.fields.push(...results.getFields);
          results.getFields.forEach(function(field) {
            if (field.required && !field.default_value) {
              ctrl.addField(field.name);
            }
          });
          if (config.presetFields) {
            config.presetFields.forEach(function(fieldName) {
              if (ctrl.values.every(function(p) { return p[0] !== fieldName; })) {
                ctrl.addField(fieldName);
              }
            });
          }
          ctrl._normalizeDepField(config);

          // Fire callback BEFORE tracking _lastLoadedDepValue so the callback can modify field values first
          if (config.onFieldsLoaded) {
            config.onFieldsLoaded(results);
          }

          if (config.depField) {
            ctrl._lastLoadedDepValue = angular.copy(ctrl.getFieldValue(config.depField));
          }

          ctrl._reloading = false;
          ctrl.refreshing = false;
          if (ctrl._pendingReload) {
            ctrl._pendingReload = false;
            ctrl._loadFields(fkValue, config);
          }
        }).catch(function(error) {
          ctrl.refreshing = false;
          ctrl._reloading = false;
          ctrl._lastLoadedDepValue = null;
          console.error('Failed to load fields:', error);
          if (config.onError) {
            config.onError(error);
          }
        });
      },

      // Ensure dep field value is array (required by deep-watch comparison and getFields values)
      _normalizeDepField: function(config) {
        if (config.depField) {
          var depVal = this.getFieldValue(config.depField);
          if (depVal !== null && !Array.isArray(depVal)) {
            this.setFieldValue(config.depField, [depVal]);
          }
        }
      },

      getFieldValue: function(fieldName) {
        var pair = this.values.find(function(p) { return p[0] === fieldName; });
        return pair ? pair[1] : null;
      },

      setFieldValue: function(fieldName, value) {
        var pair = this.values.find(function(p) { return p[0] === fieldName; });
        if (pair) { pair[1] = value; }
      },

      // Reset all state when FK field is cleared
      _clearLazyFields: function(config) {
        this.fields.length = 0;
        this.values.length = 0;
        this.refreshing = false;
        this._reloading = false;
        this._pendingReload = false;
        this._lastLoadedDepValue = null;
        if (config.onClear) {
          config.onClear();
        }
      }
    });
  });
})(angular, CRM.$, CRM._);
