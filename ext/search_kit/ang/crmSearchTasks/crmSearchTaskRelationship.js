(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchTasks').controller('crmSearchTaskRelationship', function($scope, crmApi4, searchTaskBaseTrait) {
    const ts = $scope.ts = CRM.ts('org.civicrm.search_kit');
    // Combine this controller with model properties (ids, entity, entityInfo) and searchTaskBaseTrait
    const ctrl = angular.extend(this, $scope.model, searchTaskBaseTrait);

    // Values are initially empty unless `hook_civicrm_searchKitTasks` has set them
    const values = this.task.values && !Array.isArray(this.task.values) ? this.task.values : {};
    $scope.values = values;

    this.autocompleteParams = {};

    // RelationshipTypes are pre-loaded by GetSearchTasks
    this.relationshipInfo = this.task.relationshipTypes.reduce((acc, item) => {
      acc[item.id] = item;
      return acc;
    }, {});

    // If initial value was set via `hook_civicrm_searchKitTasks`
    if (values.relationship_type) {
      this.onChangeRelationship();
    }

    this.onChangeRelationship = function() {
      ctrl.relationshipFields = null;
        ctrl.autocompleteParams = {
        filters: {contact_sub_type: ctrl.relationshipInfo[values.relationship_type].contact_sub_type}
      };
      // Load fields for the relationship form, including relevant custom fields
      crmApi4('Relationship', 'getFields', {
        action: 'create',
        values: {relationship_type_id: values.relationship_type.split('_')[0]},
        loadOptions: ['id', 'name', 'label', 'description', 'color', 'icon'],
        select: ['name', 'label', 'description', 'input_type', 'data_type', 'serialize', 'options', 'fk_entity', 'nullable', 'required'],
        where: [
          ['readonly', '=', false],
          ['OR', [
            ['type', '=', 'Custom'],
            ['name', 'IN', ['description', 'start_date', 'end_date']]]
          ]
        ]
      }).then(function(result) {
        ctrl.relationshipFields = result;
      });
    };

    this.submit = function() {
      const apiValues = _.cloneDeep(values);
      const relationshipType = values.relationship_type.split('_')[0];
      const a = values.relationship_type.split('_')[1];
      const b = values.relationship_type.split('_')[2];
      // Used by crmSearchBatchRunner to set contact_id_a or b depending on direction
      ctrl.idField = 'contact_id_' + a;
      delete apiValues.relationship_type;
      delete apiValues.contact_id;
      delete apiValues.disableRelationshipSelect;
      apiValues.relationship_type_id = relationshipType;
      apiValues['contact_id_' + b] = values.contact_id;
      // crmSearchBatchRunner will clone `values: [{idField: id} ...]` once per contact selected in the search display
      ctrl.start({defaults: apiValues});
    };

    this.onSuccess = function(result) {
      let added = 0;
      let duplicate = 0;
      result.forEach(function(relationship) {
        if (relationship.duplicate_id) {
          duplicate++;
        } else {
          added++;
        }
      });
      let msg = _.escape(added === 1 ? ts('1 relationship added.') : ts('%1 relationships added.', {1: added}));
      if (duplicate) {
        msg += '<br>' + _.escape(duplicate === 1 ? ts('1 relationship already exists.') : ts('%1 relationships already exist.', {1: duplicate}));
      }
      CRM.alert(msg, ts('Saved'), 'success');
      this.close(result);
    };

  });
})(angular, CRM.$, CRM._);
