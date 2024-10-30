(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchTasks').controller('crmSearchTaskTag', function($scope, crmApi4, searchTaskBaseTrait) {
    var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
      // Combine this controller with model properties (ids, entity, entityInfo) and searchTaskBaseTrait
      ctrl = angular.extend(this, $scope.model, searchTaskBaseTrait);

    this.entityTitle = this.getEntityTitle();
    this.action = 'save';
    this.selection = [];
    this.selectedTags = [];
    this.selectedTagsetTags = {};

    crmApi4({
      tags: ['Tag', 'get', {
        select: ['id', 'label', 'color', 'description', 'is_selectable', 'parent_id'],
        where: [
          ['is_tagset', '=', false],
          ['used_for:name', 'CONTAINS', this.entity],
          ['OR', [['parent_id', 'IS NULL'], ['parent_id.is_tagset', '=', false]]]
        ],
        orderBy: {label: 'ASC'}
      }],
      tagsets: ['Tag', 'get', {
        select: ['id', 'name', 'label'],
        where: [['is_tagset', '=', true], ['used_for:name', 'CONTAINS', this.entity]]
      }],
    }).then(function(result) {
      ctrl.tagsets = result.tagsets;
      ctrl.tags = sortTagsForSelect2(result.tags);
    });

    // Sort non-tagset tags into a nested hierarchy
    function sortTagsForSelect2(rawTags) {
      var sorted = _.transform(rawTags, function(sorted, tag) {
        sorted[tag.id] = {
          id: tag.id,
          text: tag.label,
          description: tag.description,
          color: tag.color,
          disabled: !tag.is_selectable,
          parent_id: tag.parent_id
        };
      }, {});
      // Capitalizing on the fact that javascript objects always copy-by-reference,
      // this creates a multi-level hierarchy in a single pass by placing child tags under their parents
      // while keeping a reference to children at the top level (which allows them to receive children of their own).
      _.each(sorted, function(tag) {
        if (tag.parent_id && sorted[tag.parent_id]) {
          sorted[tag.parent_id].children = sorted[tag.parent_id].children || [];
          sorted[tag.parent_id].children.push(tag);
        }
      });
      // Remove the child tags from the top level, and what remains is a nested hierarchy
      return _.filter(sorted, {parent_id: null});
    }

    this.saveTags = function() {
      var params = {};
      if (ctrl.action === 'save') {
        params.defaults = {
          'entity_table:name': ctrl.entity
        };
        params.records = _.transform(ctrl.selection, function(records, tagId) {
          records.push({tag_id: tagId});
        });
      } else {
        params.where = [
          ['entity_table:name', '=', ctrl.entity],
          ['tag_id', 'IN', ctrl.selection]
        ];
      }
      ctrl.start(params);
    };

    this.onSelectTags = function() {
      ctrl.selection = _.cloneDeep(ctrl.selectedTags);
      _.each(ctrl.selectedTagsetTags, function(set) {
        ctrl.selection = ctrl.selection.concat(set);
      });
    };

    this.onSuccess = function() {
      if (ctrl.action === 'delete') {
        CRM.alert(ts('Removed tags from %1 %2.', {1: ctrl.ids.length, 2: ctrl.entityTitle}), ts('Saved'), 'success');
      } else {
        CRM.alert(ts('Added tags to %1 %2.', {1: ctrl.ids.length, 2: ctrl.entityTitle}), ts('Saved'), 'success');
      }
      this.close();
    };

    this.onError = function() {
      CRM.alert(ts('An error occurred while updating tags.'), ts('Error'), 'error');
      this.cancel();
    };

  });
})(angular, CRM.$, CRM._);
