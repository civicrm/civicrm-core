(function(angular, $, _) {
  // Ex: <crm-mailing-recipients-autocomplete crm-recipients="mymailing.recipients" crm-mailing-id="mymailing.id" mode="include">
  angular.module('crmMailing').component('crmMailingRecipientsAutocomplete', {
    bindings: {
      recipients: '<crmRecipients',
      mailingId: '<crmMailingId',
      mode: '@crmMode',
    },
    template: '<input type="text" crm-autocomplete="\'EntitySet\'" ' +
      'crm-autocomplete-params="$ctrl.autocompleteParams" ' +
      'ng-required="$ctrl.mode === \'include\'" ' +
      'ng-model="$ctrl.getSetValue" ' +
      'ng-model-options="{getterSetter: true}" ' +
      'multi="true" ' +
      'auto-open="true" ' +
      'placeholder="{{ $ctrl.placeholder }}" ' +
      'title="{{ $ctrl.title }}" ' +
      '>',
    controller: function($timeout) {
      var ctrl = this;

      this.$onInit = function() {
        this.placeholder = this.mode === 'include' ? ts('Include Groups & Mailings') :  ts('Exclude Groups & Mailings');
        this.title = this.mode === 'include' ? ts('Include recipients from groups and past mailings.') :  ts('Exclude recipients from groups and past mailings.');
        ctrl.autocompleteParams = {
          formName: 'crmMailing.' + ctrl.mailingId,
          fieldName: 'Mailing.recipients_' + ctrl.mode
        };
      };

      // Getter/setter for the select's ng-model
      // Converts between a munged string e.g. 'mailings_3,groups_2,groups_5'
      // and the mailing.recipients object e.g. {groups: {include: [2,5]}, mailings: {include: [3]}}
      this.getSetValue = function(val) {
        var selectValues = '';
        if (arguments.length) {
          ctrl.recipients.groups[ctrl.mode].length = 0;
          ctrl.recipients.mailings[ctrl.mode].length = 0;
          _.each(val, function(munged) {
            var entityType = munged.split('_')[0],
              id = parseInt(munged.split('_')[1], 10),
              oppositeMode = ctrl.mode === 'include' ? 'exclude' : 'include';
            ctrl.recipients[entityType][ctrl.mode].push(id);
            // Items cannot be both include and exclude so remove from opposite collection
            _.pull(ctrl.recipients[entityType][oppositeMode], id);
          });
        }
        else {
          _.each(ctrl.recipients, function (items, entityType) {
            _.each(items[ctrl.mode], function (id) {
              selectValues += (selectValues.length ? ',' : '') + entityType + '_' + id;
            });
          });

        }
        return selectValues;
      };
    }
  });

})(angular, CRM.$, CRM._);
