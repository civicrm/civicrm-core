(function(angular, $, _) {
  "use strict";

  angular.module('afGuiEditor').component('afGuiTokenSelect', {
    bindings: {
      model: '<',
      field: '@'
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

      this.insertToken = function(key) {
        ctrl.model[ctrl.field] = (ctrl.model[ctrl.field] || '') + '[' + key + ']';
      };

      this.getTokens = function() {
        var tokens = _.transform(ctrl.editor.getEntities(), function(tokens, entity) {
          const entityMeta = ctrl.editor.meta.entities[entity.type];
          if (entityMeta.submissionTokens) {
            entityMeta.submissionTokens.forEach((submissionToken) => {
              const description = submissionToken.description ? submissionToken.description : '';
              tokens.push({id: entity.name + '.0.' + submissionToken.token, text: entity.label + ' ' + submissionToken.label, description: description});
            });
          } else {
            tokens.push({id: entity.name + '.0.id', text: entity.label + ' ' + ts('ID')});
          }
        }, []);
        tokens.push({id: 'token', text: ts('Submission JWT')});
        return {
          results: tokens
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
