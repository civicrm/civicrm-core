(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchTasks').controller('crmSearchTaskUpdate', function ($scope, $timeout, crmApi4, dialogService) {
    var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
      model = $scope.model,
      ctrl = this;

    this.entityTitle = model.ids.length === 1 ? model.entityInfo.title : model.entityInfo.title_plural;
    this.values = [];
    this.add = null;
    this.fields = null;

    crmApi4(model.entity, 'getFields', {
      action: 'update',
      select: ['name', 'label', 'description', 'data_type', 'serialize', 'options', 'fk_entity'],
      loadOptions: ['id', 'name', 'label', 'description', 'color', 'icon'],
      where: [["readonly", "=", false]],
    }).then(function(fields) {
        ctrl.fields = fields;
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
          if (field.serialize) {
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

    this.cancel = function() {
      dialogService.cancel('crmSearchTask');
    };

    this.save = function() {
      $('.ui-dialog-titlebar button').hide();
      ctrl.run = {
        values: _.zipObject(ctrl.values)
      };
    };

    this.onSuccess = function() {
      CRM.alert(ts('Successfully updated %1 %2.', {1: model.ids.length, 2: ctrl.entityTitle}), ts('Saved'), 'success');
      dialogService.close('crmSearchTask');
    };

    this.onError = function() {
      CRM.alert(ts('An error occurred while attempting to update %1 %2.', {1: model.ids.length, 2: ctrl.entityTitle}), ts('Error'), 'error');
      dialogService.close('crmSearchTask');
    };

  });
})(angular, CRM.$, CRM._);
