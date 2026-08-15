(function(angular, $) {
  "use strict";

  angular.module('crmSearchTasks').controller('crmSearchTaskTagMerge', function($scope, crmApi4, searchTaskBaseTrait) {
    const ts = $scope.ts = CRM.ts('org.civicrm.search_kit');
    // Combine this controller with model properties (ids, entity, entityInfo) and searchTaskBaseTrait
    angular.extend(this, $scope.model, searchTaskBaseTrait);

    this.running = false;

    crmApi4('Tag', 'get', {
      select: ['id', 'label', 'is_reserved'],
      where: [['id', 'IN', this.ids]],
      orderBy: {label: 'ASC'},
    }).then((tags) => {
      this.tags = tags;
      this.hasReserved = tags.some((tag) => tag.is_reserved);
      this.targetId = tags[0].id;
      this.label = tags[0].label;
    });

    this.onTargetChange = () => {
      const target = this.tags.find((tag) => tag.id === this.targetId);
      this.label = target ? target.label : '';
    };

    this.merge = () => {
      this.running = true;
      crmApi4('Tag', 'merge', {
        targetId: this.targetId,
        tagIds: this.ids.filter((id) => id !== this.targetId),
        label: this.label,
      }).then(() => {
        CRM.alert(ts('%1 tags merged into "%2".', {1: this.ids.length, 2: this.label}), ts('Merged'), 'success');
        this.close();
      }, (error) => {
        this.running = false;
        CRM.alert(error.error_message || ts('An error occurred while merging tags.'), ts('Error'), 'error');
      });
    };

  });
})(angular, CRM.$);
