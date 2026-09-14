// https://civicrm.org/licensing
(function(angular, $, _) {
  "use strict";

  angular.module('afGuiEditor').controller('AfGuiConditionalDialog', function ($scope, $parse, afGui, dialogService) {
    const ts = $scope.ts = CRM.ts('org.civicrm.afform_admin');
    $scope.$ctrl = this;

    this.fieldSelector = [];
    this.fieldDefns = {};

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

    this.conditions = {};

    this.selectRule = (ruleName) => {
      this.activeRule = ruleName;
    };


    this.save = () => {
      Object.keys(this.applicableRules).forEach(ruleName => {
        if (!this.conditions[ruleName] || !this.conditions[ruleName].length) {
          delete this.node[ruleName];
        } else {
          // Don't have e.g. `required` and `af-required`
          const staticItem = ruleName.replace(/af-/, '');
          if (staticItem !== ruleName && this.node.defn) {
            delete this.node.defn[staticItem];
          }
          this.node[ruleName] = '(' + JSON.stringify(this.conditions[ruleName]).replace(/"/g, '&quot;') + ')';
        }
      });
      dialogService.close('afformGuiConditionalDialog');
    };

    this.parseConditions = (rule) => {
      if (!this.node[rule]) {
        return [];
      }
      const raw = this.node[rule].replace(/&quot;/g, '"').trim();
      if (raw.charAt(0) !== '(') {
        return [];
      }
      return $parse(raw.slice(1, -1))();
    };

    this.$onInit = () => {
      this.node = $scope.model.node;
      this.editor = $scope.model.editor;

      if (this.node['#tag'] !== 'af-field' || $scope.model.isReadOnly) {
        delete this.applicableRules['af-required'];
        delete this.applicableRules['af-disabled'];
      }

      Object.keys(this.applicableRules).forEach(ruleName => {
        this.conditions[ruleName] = this.parseConditions(ruleName);
      });

      this.activeRule = $scope.model.rule in this.applicableRules ? $scope.model.rule : Object.keys(this.applicableRules)[0];

      this.editor.getEntities().forEach((entity) => {
        const entityFields = this.editor.getEntityFields(entity.name);

        const items = entityFields.fields.reduce((items, field) => {
          // Conditional in case field is missing
          if (field) {
            const key = entity.name + "[0][fields][" + field.name + "]";
            this.fieldDefns[key] = field;
            items.push({id: key, text: field.label || field.input_attrs.label});
          }
          return items;
        }, []);

        entityFields.joins.forEach((join) => {
          items.push({
            text: afGui.getEntity(join.entity).label,
            children: join.fields.reduce((items, field) => {
              const key = entity.name + "[0][joins][" + join.entity + "][0][" + field.name + "]";
              this.fieldDefns[key] = field;
              items.push({id: key, text: field.label || field.input_attrs.label});
              return items;
            }, [])
          });
        });
        this.fieldSelector.push({
          text: entity.label,
          children: items
        });
      });
    };

  });

})(angular, CRM.$, CRM._);
