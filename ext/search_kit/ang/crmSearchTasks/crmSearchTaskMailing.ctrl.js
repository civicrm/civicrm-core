(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchTasks').controller('crmSearchTaskMailing', function($scope, crmApi4, searchTaskBaseTrait) {
    var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
      // Combine this controller with model properties (ids, entity, entityInfo) and searchTaskBaseTrait
      ctrl = angular.extend(this, $scope.model, searchTaskBaseTrait),
      mailingId,
      templateTypes;

    this.entityTitle = this.getEntityTitle();

    // This option is needed to determine whether the mailing will be handled by CiviMail or Mosaico
    crmApi4({
      templateTypes: ['Mailing', 'getFields', {
        loadOptions: ['id'],
        where: [['name', '=', 'template_type']]
      }, ['options']],
      recipientCount: ['Contact', 'get', {
        select: ['row_count'],
        join: [['Email AS email', 'INNER', ['id', '=', 'email.contact_id']]],
        where: [['id', 'IN', ctrl.ids], ['do_not_email', '!=', true], ['is_opt_out', '!=', true], ['email.on_hold', '=', 0]],
        groupBy: ['id']
      }]
    }).then(function(results) {
      templateTypes = results.templateTypes[0];
      ctrl.recipientCount = results.recipientCount.count;
    });

    this.submit = function() {
      ctrl.start({
        values: {
          title: 'Hidden Group ' + Date.now(),
          is_hidden: true,
          'group_type:name': ['Mailing List'],
        },
        chain: {
          mailing: ['Mailing', 'create', {
            values: {
              name: ctrl.name,
              template_type: templateTypes[0].id
            }
          }, 0],
          mailingGroup: ['MailingGroup', 'create', {
            values: {
              group_type: 'Include',
              'entity_table:name': 'Group',
              entity_id: '$id',
              mailing_id: '$mailing.id'
            },
          }, 0]
        }
      });
    };

    // After running first api call to create group & mailing,
    // This runs a batch of api calls to add contacts to the mailing group
    this.afterGroupCreate = function(result) {
      mailingId = result[0].mailing.id;
      ctrl.addContacts = {
        defaults: {group_id: result[0].id}
      };
    };

    this.onSuccess = function(result) {
      window.location = CRM.url('civicrm/a#/mailing/' + mailingId);
    };

    this.onError = function() {
      CRM.alert(ts('An error occurred while attempting to create mailing.'), ts('Error'), 'error');
      this.cancel();
    };

  });
})(angular, CRM.$, CRM._);
