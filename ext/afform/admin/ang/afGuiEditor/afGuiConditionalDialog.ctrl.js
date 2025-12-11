// https://civicrm.org/licensing
(function(angular, $, _) {
  "use strict";

  angular.module('afGuiEditor').controller('AfGuiConditionalDialog', function($scope, $parse, afGui, dialogService) {
    const ts = $scope.ts = CRM.ts('org.civicrm.afform_admin'),
      ctrl = $scope.$ctrl = this;
    this.node = $scope.model.node;
    this.editor = $scope.model.editor;
    this.conditions = parseConditions();
    loadAllFields();

    this.save = function() {
      if (!ctrl.conditions.length) {
        delete ctrl.node['af-if'];
      } else {
        ctrl.node['af-if'] = '(' + JSON.stringify(ctrl.conditions).replace(/"/g, '&quot;') + ')';
      }
      dialogService.close('afformGuiConditionalDialog');
    };

    function parseConditions() {
      if (!ctrl.node['af-if']) {
        return [];
      }
      var ngIf = _.trim(ctrl.node['af-if'].replace(/&quot;/g, '"'));
      if (!_.startsWith(ngIf, '(')) {
        return [];
      }
      return $parse(ngIf.slice(1, -1))();
    }

    function loadAllFields() {
      ctrl.fieldSelector = [];
      ctrl.fieldDefns = {};
      _.each(ctrl.editor.getEntities(), function(entity) {
        var entityFields = ctrl.editor.getEntityFields(entity.name),
          items = _.transform(entityFields.fields, function(items, field) {
            // Conditional in case field is missing
            if (field) {
              var key = entity.name + "[0][fields][" + field.name + "]";
              ctrl.fieldDefns[key] = field;
              items.push({id: key, text: field.label || field.input_attrs.label});
            }
          });
        _.each(entityFields.joins, function(join) {
          items.push({
            text: afGui.getEntity(join.entity).label,
            children: _.transform(join.fields, function(items, field) {
              var key = entity.name + "[0][joins][" + join.entity + "][0][" + field.name + "]";
              ctrl.fieldDefns[key] = field;
              items.push({id: key, text: field.label || field.input_attrs.label});
            })
          });
        });
        ctrl.fieldSelector.push({
          text: entity.label,
          children: items
        });
      });
    }

  });

})(angular, CRM.$, CRM._);
