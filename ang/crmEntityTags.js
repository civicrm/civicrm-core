(function(angular, $, _) {
  "use strict";

  // Generic inline "add/remove tags" widget for a single entity - a dropdown to toggle
  // existing tags (with inline quick-create) plus colored badges for the tags already applied.
  angular.module('crmEntityTags', CRM.angRequires('crmEntityTags'));

  // Shared for the lifetime of the page, so a list with many rows - each embedding its own
  // <crm-entity-tags> - triggers one Tag.get per distinct entity_table, not one per row.
  const cache = {};

  // Tag.get is keyed by entity_table, matching the tag_used_for option value for real DB-table entities.
  angular.module('crmEntityTags').factory('crmEntityTagsCache', function(crmApi4) {
    return {
      get: function(entityTable) {
        if (!cache[entityTable]) {
          cache[entityTable] = crmApi4('Tag', 'get', {
            select: ['id', 'label', 'color', 'is_selectable', 'description'],
            where: [['used_for', 'CONTAINS', entityTable]]
          });
        }
        return cache[entityTable];
      }
    };
  });

  angular.module('crmEntityTags').component('crmEntityTags', {
    bindings: {
      tagIds: '<',
      // Omit entityId for a not-yet-saved record - tagIds is then mutated locally,
      // with no EntityTag API calls, until the caller saves the record itself.
      entityId: '<',
      entityTable: '<'
    },
    templateUrl: '~/crmEntityTags/crmEntityTags.html',
    controller: function ($scope, $element, crmApi4, crmStatus, crmEntityTagsCache) {
      const ts = $scope.ts = CRM.ts(null),
        ctrl = this;
      ctrl.allTags = [];

      function reset() {
        ctrl.menuOpen = false;
        ctrl.search = '';
      }

      this.$onInit = function() {
        ctrl.tagIds = ctrl.tagIds || [];
        reset();
        crmEntityTagsCache.get(ctrl.entityTable).then(function(tags) {
          ctrl.allTags = tags;
        });
        $element.on('hidden.bs.dropdown', function() {
          $scope.$apply(reset);
        });
      };

      this.openMenu = function() {
        ctrl.menuOpen = true;
        ctrl.color = getRandomColor();
      };

      this.getTag = function(id) {
        return _.findWhere(ctrl.allTags, {id: id});
      };

      this.hasTag = function(tag) {
        return _.includes(ctrl.tagIds, tag.id);
      };

      this.getStyle = function(id) {
        const tag = ctrl.getTag(id);
        if (tag && tag.color) {
          return 'background-color: ' + tag.color + '; color: ' + CRM.utils.colorContrast(tag.color);
        }
        return '';
      };

      this.toggleTag = function(tag) {
        if (ctrl.hasTag(tag)) {
          _.remove(ctrl.tagIds, function(id) {return id === tag.id;});
          if (ctrl.entityId) {
            crmStatus({}, crmApi4('EntityTag', 'delete', {
              where: [['entity_id', '=', ctrl.entityId], ['tag_id', '=', tag.id], ['entity_table', '=', ctrl.entityTable]]
            }));
          }
        } else {
          ctrl.tagIds.push(tag.id);
          if (ctrl.entityId) {
            crmStatus({}, crmApi4('EntityTag', 'create', {
              values: {entity_id: ctrl.entityId, tag_id: tag.id, entity_table: ctrl.entityTable}
            }));
          }
        }
      };

      this.makeTag = function(label) {
        crmApi4('Tag', 'create', {
          values: {label: label, color: ctrl.color, is_selectable: true, used_for: [ctrl.entityTable]}
        }, 0).then(function(tag) {
          // Push through the cache lookup, not ctrl.allTags directly - if this fires before
          // the initial cache fetch resolves, ctrl.allTags is still the placeholder array from
          // $onInit, and pushing there would get silently discarded when the fetch overwrites
          // it. Going through the cache also means every other <crm-entity-tags> instance for
          // this table sees the new tag immediately, since they all share the same array.
          crmEntityTagsCache.get(ctrl.entityTable).then(function(tags) {
            tags.push(tag);
            ctrl.toggleTag(tag);
          });
        });
      };

      // TODO: Use https://github.com/davidmerfield/randomColor
      function getRandomColor() {
        return '#' + Math.floor(Math.random()*16777215).toString(16);
      }

    }
  });

})(angular, CRM.$, CRM._);
