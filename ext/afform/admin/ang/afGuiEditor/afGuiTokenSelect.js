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

      this.getTokens = () => ({
        results: this.editor.getTokens(!this.noSubmissionTokens),
      });

      this.tokenSelectSettings = {
        data: this.getTokens,
        // The crm-action-menu icon doesn't show without a placeholder
        placeholder: ' ',
      };
    }
  });

})(angular, CRM.$, CRM._);
