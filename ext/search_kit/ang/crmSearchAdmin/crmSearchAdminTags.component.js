(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('crmSearchAdminTags', {
    bindings: {
      tagIds: '<',
      savedSearchId: '<'
    },
    templateUrl: '~/crmSearchAdmin/crmSearchAdminTags.html',
    controller: function ($scope, $element, crmApi4, crmStatus) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this;
      this.allTags = CRM.crmSearchAdmin.tags;

      function reset() {
        ctrl.menuOpen = false;
        ctrl.search = '';
      }

      this.$onInit = function() {
        ctrl.tagIds = ctrl.tagIds || [];
        reset();
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
          if (ctrl.savedSearchId) {
            crmStatus({}, crmApi4('EntityTag', 'delete', {
              where: [['entity_id', '=', ctrl.savedSearchId], ['tag_id', '=', tag.id], ['entity_table', '=', 'civicrm_saved_search']]
            }));
          }
        } else {
          ctrl.tagIds.push(tag.id);
          if (ctrl.savedSearchId) {
            crmStatus({}, crmApi4('EntityTag', 'create', {
              values: {entity_id: ctrl.savedSearchId, tag_id: tag.id, entity_table: 'civicrm_saved_search'}
            }));
          }
        }
      };

      this.makeTag = function(label) {
        crmApi4('Tag', 'create', {
          values: {label: label, color: ctrl.color, is_selectable: true, used_for: ['civicrm_saved_search']}
        }, 0).then(function(tag) {
          ctrl.allTags.push(tag);
          ctrl.toggleTag(tag);
        });
      };

      // TODO: Use https://github.com/davidmerfield/randomColor
      function getRandomColor() {
        return '#' + Math.floor(Math.random()*16777215).toString(16);
      }

    }
  });

})(angular, CRM.$, CRM._);
