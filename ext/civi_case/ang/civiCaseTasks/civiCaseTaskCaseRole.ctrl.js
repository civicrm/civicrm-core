(function(angular, $, _) {
  "use strict";

  angular.module('civiCaseTasks').controller('civiCaseTaskCaseRole', function($scope, crmApi4, searchTaskBaseTrait) {
    const ts = $scope.ts = CRM.ts('org.civicrm.search_kit');
    // Combine this controller with model properties (ids, entity, entityInfo) and searchTaskBaseTrait
    const ctrl = angular.extend(this, $scope.model, searchTaskBaseTrait);

    // Values are initially empty unless `hook_civicrm_searchKitTasks` has set them
    const values = this.task.values && !Array.isArray(this.task.values) ? this.task.values : {};
    $scope.values = values;

    this.existingCount = null;
    this.autocompleteParams = {};

    // RelationshipTypes are provided by CaseTasksProvider
    this.relationshipInfo = this.task.relationshipTypes.reduce((acc, item) => {
      acc[item.id] = item;
      return acc;
    }, {});

    // If initial value was set via `hook_civicrm_searchKitTasks`
    if (values.relationship_type) {
      setCountAndFilters();
    }

    this.onChangeRole = function() {
      values.addOrReplace = null;
      setCountAndFilters();
    };

    function setCountAndFilters() {
      ctrl.existingCount = null;
      crmApi4('Relationship', 'get', {
        select: ['row_count'],
        where: [
          ['case_id', 'IN', ctrl.ids],
          ['is_current', '=', true],
          ['relationship_type_id', '=', values.relationship_type.split('_')[0]],
        ],
        groupBy: ['case_id']
      }).then(function(result) {
        ctrl.existingCount = result.count;
      });
      ctrl.autocompleteParams = {
        filters: {contact_sub_type: ctrl.relationshipInfo[values.relationship_type].contact_sub_type}
      };
    }

    this.submit = function() {
      const relationshipType = values.relationship_type.split('_')[0];
      const a = values.relationship_type.split('_')[1];
      const b = values.relationship_type.split('_')[2];
      if (values.addOrReplace === 'replace') {
        crmApi4('Relationship', 'update', {
          values: {end_date: 'now', is_active: false},
          where: [
            ['case_id', 'IN', ctrl.ids],
            ['is_current', '=', true],
            ['relationship_type_id', '=', relationshipType],
            ['contact_id_' + b, '!=', values.contact_id],
          ]
        });
      }
      // Use chaining to fetch case clients and add relationship to each one
      // Since a case can have ≥ 1 clients, this will create 1 or more relationship per case.
      const apiValues = {
        case_id: '$case_id',
        relationship_type_id: relationshipType,
        start_date: 'now',
      };
      apiValues['contact_id_' + a] = '$contact_id';
      apiValues['contact_id_' + b] = values.contact_id;
      const apiParams = {
        chain: {relationship: ['Relationship', 'create', {values: apiValues}]}
      };
      ctrl.start(apiParams);
    };

    this.onSuccess = function(result) {
      let added = 0;
      let duplicate = 0;
      result.forEach(function(item) {
        item.relationship.forEach(function(relationship) {
          if (relationship.duplicate_id) {
            duplicate++;
          } else {
            added++;
          }
        });
      });
      let msg = _.escape(added === 1 ? ts('1 case role added.') : ts('%1 case roles added.', {1: added}));
      if (duplicate) {
        msg += '<br>' + _.escape(duplicate === 1 ? ts('1 case role already occupied by the selected contact.') : ts('%1 case roles already occupied by the selected contact.', {1: duplicate}));
      }
      CRM.alert(msg, ts('Saved'), 'success');
      this.close(result);
    };

  });
})(angular, CRM.$, CRM._);
