(function(angular, $, _) {
  "use strict";

  angular.module('civiCaseTasks').controller('civiCaseTaskOpenCase', function($scope, crmApi4, searchTaskBaseTrait, searchTaskFieldsTrait) {
    const ts = $scope.ts = CRM.ts('civi_case');
    // Combine this controller with model properties (ids, entity, entityInfo) and base traits
    const ctrl = angular.extend(this, $scope.model, searchTaskBaseTrait, searchTaskFieldsTrait);

    this.skipDuplicates = true;
    this.multipleClients = false;

    this.loadFieldsAndValues(this.task, 'Case',
      {action: 'create', where: [['name', 'NOT IN', ['contact_id', 'is_deleted']]]},
      {existingCaseContacts: ['CaseContact', 'get', {
          where: [
            ['contact_id', 'IN', ctrl.ids],
            ['case_id.status_id:name', '!=', 'Closed']
          ],
        }, ['contact_id']]}
    ).then((results) => {
      this.existingCaseContacts = results.existingCaseContacts;
      this.addField('subject');
    });

    this.submit = function() {
      const apiParams = {};
      if (ctrl.skipDuplicates) {
        ctrl.ids = ctrl.ids.filter(id => !ctrl.existingCaseContacts.includes(id));
      }
      if (!ctrl.ids.length) {
        CRM.alert(_.escape(ts('No cases to open.')), _.escape(ts('All contacts skipped')));
        ctrl.cancel();
      }
      if (ctrl.multipleClients) {
        apiParams.values = _.zipObject(ctrl.values);
        apiParams.values.contact_id = ctrl.ids;
      }
      else {
        apiParams.records = [_.zipObject(ctrl.values)];
      }
      ctrl.start(apiParams);
    };

    this.onSuccess = function(result) {
      let msg = _.escape(ts('Successfully opened 1 case.', {plural: 'Successfully opened %count cases.', count: result.length}));
      if (result.length === 1) {
        const viewLink = this.getUrl('view', result[0]);
        if (viewLink) {
          msg += '<br><a href="' + viewLink + '" target="_blank"><i class="crm-i fa-external-link" role="img" aria-hidden="true"></i> ' + _.escape(ts('View case')) + '</a>';
        }
      }
      CRM.alert(msg, ts('Saved'), 'success');
      this.close(result);
    };

  });
})(angular, CRM.$, CRM._);
