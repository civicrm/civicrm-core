(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchTasks').controller('crmSearchTaskUpdate', function ($scope, $timeout, crmApi4, searchTaskBaseTrait) {
    var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
      // Combine this controller with model properties (ids, entity, entityInfo) and searchTaskBaseTrait
      ctrl = angular.extend(this, $scope.model, searchTaskBaseTrait);

    this.entityTitle = this.getEntityTitle();
    this.values = [];
    this.add = null;
    this.fields = null;

    crmApi4({
      getFields: [this.entity, 'getFields', {
        action: 'update',
        select: ['name', 'label', 'description', 'input_type', 'data_type', 'serialize', 'options', 'fk_entity', 'nullable'],
        loadOptions: ['id', 'name', 'label', 'description', 'color', 'icon'],
        where: [['deprecated', '=', false], ["readonly", "=", false]],
      }],
    }).then(function(results) {
        ctrl.fields = results.getFields;
      });

    this.updateField = function(index) {
      // Debounce the onchange event using timeout
      $timeout(function() {
        if (!ctrl.values[index][0]) {
          ctrl.values.splice(index, 1);
        }
      });
    };

    this.addField = function() {
      // Debounce the onchange event using timeout
      $timeout(function() {
        if (ctrl.add) {
          var field = ctrl.getField(ctrl.add),
            value = '';
          if (field.serialize || field.data_type === 'Array') {
            value = [];
          } else if (field.data_type === 'Boolean') {
            value = true;
          } else if (field.options && field.options.length) {
            value = field.options[0].id;
          }
          ctrl.values.push([ctrl.add, value]);
        }
        ctrl.add = null;
      });
    };

    this.getField = function(fieldName) {
      return _.where(ctrl.fields, {name: fieldName})[0];
    };

    function fieldInUse(fieldName) {
      return _.includes(_.collect(ctrl.values, 0), fieldName);
    }

    this.availableFields = function() {
      var results = _.transform(ctrl.fields, function(result, item) {
        var formatted = {id: item.name, text: item.label, description: item.description};
        if (fieldInUse(item.name)) {
          formatted.disabled = true;
        }
        result.push(formatted);
      }, []);
      return {results: results};
    };

    this.save = function() {
      ctrl.start({
        values: _.zipObject(ctrl.values)
      });
    };

    this.onSuccess = function() {
      CRM.alert(ts('Successfully updated %1 %2.', {1: ctrl.ids.length, 2: ctrl.entityTitle}), ts('Saved'), 'success');
      this.close();
    };

    this.onError = function() {
      CRM.alert(ts('An error occurred while attempting to update %1 %2.', {1: ctrl.ids.length, 2: ctrl.entityTitle}), ts('Error'), 'error');
      this.cancel();
    };

  });
})(angular, CRM.$, CRM._);
