(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchTasks').controller('crmSearchTaskTagMerge', function($scope, crmApi4, searchTaskBaseTrait) {
    const ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
      // Combine this controller with model properties (ids, entity, entityInfo) and searchTaskBaseTrait
      ctrl = angular.extend(this, $scope.model, searchTaskBaseTrait);

    this.running = false;

    crmApi4('Tag', 'get', {
      select: ['id', 'label', 'is_reserved'],
      where: [['id', 'IN', ctrl.ids]],
      orderBy: {label: 'ASC'},
    }).then(function(tags) {
      ctrl.tags = tags;
      ctrl.hasReserved = _.some(tags, 'is_reserved');
      ctrl.targetId = tags[0].id;
      ctrl.label = tags[0].label;
    });

    this.onTargetChange = function() {
      const target = _.find(ctrl.tags, {id: ctrl.targetId});
      ctrl.label = target ? target.label : '';
    };

    this.merge = function() {
      ctrl.running = true;
      crmApi4('Tag', 'merge', {
        targetId: ctrl.targetId,
        tagIds: _.without(ctrl.ids, ctrl.targetId),
        label: ctrl.label,
      }).then(function() {
        CRM.alert(ts('%1 tags merged into "%2".', {1: ctrl.ids.length, 2: ctrl.label}), ts('Merged'), 'success');
        ctrl.close();
      }, function(error) {
        ctrl.running = false;
        CRM.alert(error.error_message || ts('An error occurred while merging tags.'), ts('Error'), 'error');
      });
    };

  });
})(angular, CRM.$, CRM._);
