(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchTasks').controller('crmSearchTaskTag', function($scope, crmApi4, searchTaskBaseTrait) {
    const ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
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
      const sorted = rawTags.reduce((accum, tag) => {
        accum[tag.id] = {
          id: tag.id,
          text: tag.label,
          description: tag.description,
          color: tag.color,
          disabled: !tag.is_selectable,
          parent_id: tag.parent_id
        };
        return accum;
      }, {});
      // Capitalizing on the fact that javascript objects always copy-by-reference,
      // this creates a multi-level hierarchy in a single pass by placing child tags under their parents
      // while keeping a reference to children at the top level (which allows them to receive children of their own).
      Object.values(sorted).forEach(tag => {
        if (tag.parent_id && sorted[tag.parent_id]) {
          sorted[tag.parent_id].children = sorted[tag.parent_id].children || [];
          sorted[tag.parent_id].children.push(tag);
        }
      });
      // Remove the child tags from the top level, and what remains is a nested hierarchy
      return Object.values(sorted).filter(tag => tag.parent_id === null);
    }

    this.saveTags = function() {
      const params = {};
      // Add tags
      if (ctrl.action === 'save') {
        params.defaults = {
          'entity_table:name': ctrl.entity
        };
        params.records = ctrl.selection.map(tagId => {
          return {tag_id: tagId};
        });
        params.match = ['entity_id', 'tag_id', 'entity_table'];
      }
      // Remove tags
      else {
        params.where = [
          ['entity_table:name', '=', ctrl.entity],
          ['tag_id', 'IN', ctrl.selection]
        ];
      }
      ctrl.start(params);
    };

    this.onSelectTags = function() {
      ctrl.selection = [...ctrl.selectedTags];
      Object.values(ctrl.selectedTagsetTags).forEach(set => {
        ctrl.selection = [...ctrl.selection, ...set];
      });
    };

    this.onSuccess = function(result) {
      let msg;
      if (ctrl.action === 'delete') {
        msg = result.batchCount === 1 ? ts('1 tag removed.') : ts('%1 tags removed.', {1: result.batchCount});
        CRM.alert(msg, ts('Saved'), 'success');
      } else {
        const added = result.batchCount - result.countMatched;
        msg = added === 1 ? ts('1 tag added') : ts('%1 tags added.', {1: added});
        msg += '<br/>';
        if (result.countMatched > 0) {
          msg += result.countMatched === 1 ? ts('1 tag already exists and was not added.') : ts('%1 tags already exist and were not added.', {1: result.countMatched});
        }
        CRM.alert(msg, ts('Saved'), 'success');
      }
      this.close();
    };

    this.onError = function() {
      CRM.alert(ts('An error occurred while updating tags.'), ts('Error'), 'error');
      this.cancel();
    };

  });
})(angular, CRM.$, CRM._);
