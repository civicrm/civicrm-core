(function(angular, $, _) {
  "use strict";

  angular.module('afGuiEditor').component('afGuiTokenSelect', {
    bindings: {
      model: '<',
      field: '@',
      noSubmissionTokens: '@',
    },
    require: {
      editor: '^afGuiEditor'
    },
    templateUrl: '~/afGuiEditor/afGuiTokenSelect.html',
    controller: function ($scope, $element) {
      const ts = $scope.ts = CRM.ts('org.civicrm.afform_admin'),
        ctrl = this;

      this.$onInit = function() {
        // Because this widget is so small, some placeholder text is helpful once it's open
        $element.on('select2-open', function() {
          $('#select2-drop > .select2-search > input').attr('placeholder', ts('Insert Token'));
        });
      };

      this.insertToken = (key) => {
        const token = '[' + key + ']';
        let value = getModelValue();
        if (value.length) {
          value += ' ';
        }
        value += token;
        setModelValue(value);
      };

      const getModelValue = () => {
        // If using getter/setter factory
        if (typeof this.model === 'function') {
          return this.model(this.field)() || '';
        }
        return this.model[this.field] || '';
      };

      const setModelValue = (value) => {
        // If using getter/setter factory
        if (typeof this.model === 'function') {
          this.model(this.field)(value);
        } else {
          this.model[this.field] = value;
        }
      };

      this.getTokens = function() {
        const allTokens = [];
        ctrl.editor.getEntities().forEach((entity) => {
          const entityTokens = [];
          const entityMeta = ctrl.editor.meta.entities[entity.type];
          if (entityMeta.submissionTokens && !ctrl.noSubmissionTokens) {
            // Explicitly defined submission tokens e.g. by FormProcessor extension
            entityMeta.submissionTokens.forEach((submissionToken) => {
              entityTokens.push({
                id: entity.name + '.0.' + submissionToken.token,
                text: entity.label + ' ' + submissionToken.label,
                description: submissionToken.description ?? '',
              });
            });
          } else if (!entityMeta.submissionTokens) {
            // Primary key token
            if (!ctrl.noSubmissionTokens) {
              entityTokens.push({
                id: entity.name + '.0.id',
                text: ts('%1 ID', {1: entity.label}),
              });
            }
            // Tokens from entity data values
            if (entity.data) {
              Object.keys(entity.data).forEach((key) => {
                if (entityMeta.fields[key]) {
                  entityTokens.push({
                    id: entity.name + '.0.' + key,
                    text: entity.label + ' ' + entityMeta.fields[key].label,
                  });
                }
              });
            }
            // Tokens from entity fields on the form
            ctrl.editor.getEntityFields(entity.name).fields.forEach((field) => {
              entityTokens.push({
                id: entity.name + '.0.' + field.name,
                text: entity.label + ' ' + field.label,
              });
            });
          }
          if (entityTokens.length) {
            allTokens.push({
              text: entity.label,
              children: entityTokens,
            });
          }
        });
        if (!ctrl.noSubmissionTokens) {
          allTokens.push({
            text: ts('Form'),
            children: [
              {id: 'token', text: ts('Submission JWT')},
            ],
          });
        }
        return {
          results: allTokens
        };
      };

      this.tokenSelectSettings = {
        data: this.getTokens,
        // The crm-action-menu icon doesn't show without a placeholder
        placeholder: ' ',
      };

    }
  });

})(angular, CRM.$, CRM._);
