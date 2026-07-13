// https://civicrm.org/licensing
(function(angular, $, _) {
  "use strict";

  angular.module('afGuiEditor').controller('AfGuiConditionalDialog', function($scope, $parse, afGui, dialogService) {
    const ts = $scope.ts = CRM.ts('org.civicrm.afform_admin'),
      ctrl = $scope.$ctrl = this;

    this.node = $scope.model.node;
    this.editor = $scope.model.editor;

    this.applicableRules = {
      'af-if': {
        title: ts('Visibility'),
        description: ts('Element will be shown if...')
      },
      'af-required': {
        title: ts('Required'),
        description: ts('Element will be required if...')
      },
      'af-disabled': {
        title: ts('Disabled'),
        description: ts('Element will be disabled if...')
      }
    };

    if (this.node['#tag'] !== 'af-field' || $scope.model.isReadOnly) {
      delete this.applicableRules['af-required'];
      delete this.applicableRules['af-disabled'];
    }

    this.conditions = {};
    Object.keys(this.applicableRules).forEach(ruleName => {
      this.conditions[ruleName] = parseConditions(ruleName);
    });

    this.activeRule = $scope.model.rule in this.applicableRules ? $scope.model.rule : Object.keys(this.applicableRules)[0];

    this.selectRule = function(ruleName) {
      this.activeRule = ruleName;
    };

    loadAllFields();

    this.save = function() {
      Object.keys(ctrl.applicableRules).forEach(ruleName => {
        if (!ctrl.conditions[ruleName] || !ctrl.conditions[ruleName].length) {
          delete ctrl.node[ruleName];
        } else {
          // Don't have e.g. `required` and `af-required`
          const staticItem = ruleName.replace(/af-/, '');
          if (staticItem !== ruleName && ctrl.node.defn) {
            delete ctrl.node.defn[staticItem];
          }
          ctrl.node[ruleName] = '(' + JSON.stringify(ctrl.conditions[ruleName]).replace(/"/g, '&quot;') + ')';
        }
      });
      dialogService.close('afformGuiConditionalDialog');
    };

    function parseConditions(rule) {
      if (!ctrl.node[rule]) {
        return [];
      }
      const raw = _.trim(ctrl.node[rule].replace(/&quot;/g, '"'));
      if (raw.charAt(0) !== '(') {
        return [];
      }
      return $parse(raw.slice(1, -1))();
    }

    function loadAllFields() {
      ctrl.fieldSelector = [];
      ctrl.fieldDefns = {};

      ctrl.editor.getEntities().forEach((entity) => {
        const entityFields = ctrl.editor.getEntityFields(entity.name);

        const items = entityFields.fields.reduce((items, field) => {
          // Conditional in case field is missing
          if (field) {
            const key = entity.name + "[0][fields][" + field.name + "]";
            ctrl.fieldDefns[key] = field;
            items.push({id: key, text: field.label || field.input_attrs.label});
          }
          return items;
        }, []);

        entityFields.joins.forEach((join) => {
          items.push({
            text: afGui.getEntity(join.entity).label,
            children: join.fields.reduce((items, field) => {
              const key = entity.name + "[0][joins][" + join.entity + "][0][" + field.name + "]";
              ctrl.fieldDefns[key] = field;
              items.push({id: key, text: field.label || field.input_attrs.label});
              return items;
            }, [])
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
