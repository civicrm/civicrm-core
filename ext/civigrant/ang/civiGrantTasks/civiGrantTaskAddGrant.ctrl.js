(function(angular, $, _) {
  "use strict";

  angular.module('civiGrantTasks').controller('civiGrantTaskAddGrant', function($scope, crmApi4, searchTaskBaseTrait, searchTaskFieldsTrait) {
    const ts = $scope.ts = CRM.ts('civi_grant');
    // Combine this controller with model properties (ids, entity, entityInfo) and base traits
    const ctrl = angular.extend(this, $scope.model, searchTaskBaseTrait, searchTaskFieldsTrait);

    this.skipDuplicates = true;

    this.loadFieldsAndValues(this.task, 'Grant',
      {action: 'create', where: [['name', 'NOT IN', ['contact_id', 'is_deleted']]]},
      {existingGrantContacts: ['Grant', 'get', {
        where: [
          ['contact_id', 'IN', ctrl.ids],
        ],
      }, ['contact_id']]}
    ).then((results) => {
      this.existingGrantContacts = results.existingGrantContacts;
    });

    this.submit = function() {
      const apiParams = {};
      if (ctrl.skipDuplicates) {
        ctrl.ids = ctrl.ids.filter(id => !ctrl.existingGrantContacts.includes(id));
      }
      if (!ctrl.ids.length) {
        CRM.alert(_.escape(ts('No grants to add.')), _.escape(ts('All contacts skipped')));
        ctrl.cancel();
      }
      apiParams.records = [_.zipObject(ctrl.values)];
      ctrl.start(apiParams);
    };

    this.onSuccess = function(result) {
      let msg = _.escape(ts('Successfully added 1 grant.', {plural: 'Successfully added %count grants.', count: result.length}));
      if (result.length === 1) {
        const viewLink = this.getUrl('view', result[0]);
        if (viewLink) {
          msg += '<br><a href="' + viewLink + '" target="_blank"><i class="crm-i fa-external-link" role="img" aria-hidden="true"></i> ' + _.escape(ts('View grant')) + '</a>';
        }
      }
      CRM.alert(msg, ts('Saved'), 'success');
      this.close(result);
    };

  });
})(angular, CRM.$, CRM._);
