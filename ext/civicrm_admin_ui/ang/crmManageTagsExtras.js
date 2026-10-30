(function(angular, $, _) {
  "use strict";

  // A button/link that only renders if the current user has the given permission
  // (or unconditionally, if no permission is given), opening the given path in a
  // crm-popup dialog. Useful for actions a SearchDisplay's own JSON-driven toolbar
  // can't gate on an arbitrary permission (only on the generic create/update/delete
  // action permissions for a single entity), or whose path depends on scope state
  // the static JSON toolbar can't reference (e.g. the currently selected tag set).
  angular.module('crmManageTagsExtras', ['crmUi']).component('crmPermissionPopupLink', {
    bindings: {
      permission: '@',
      path: '@',
      text: '@',
      icon: '@',
      style: '@',
    },
    template:
      '<a ng-if="$ctrl.allowed" ng-href="{{ $ctrl.url }}" target="crm-popup" class="btn btn-{{:: $ctrl.style || \'default\' }}">' +
      '<i ng-if="$ctrl.icon" class="crm-i {{:: $ctrl.icon }}" role="img" aria-hidden="true"></i> {{:: $ctrl.text }}' +
      '</a>',
    controller: function() {
      this.$onInit = function() {
        this.allowed = !this.permission || CRM.checkPerm(this.permission);
      };
      // `path` may be interpolated from scope state (e.g. the active tag set's id),
      // so recompute the url whenever it changes rather than only once at $onInit.
      this.$onChanges = function(changes) {
        if (changes.path) {
          this.url = CRM.url(this.path);
        }
      };
    },
  });

  // Fetches the list of tag sets (Tag rows with is_tagset = true) for the "one tab per
  // tag set" nav in afsearchManageTags, refreshing whenever a popup form in the enclosing
  // Afform succeeds (adding/removing a tag set changes this list).
  angular.module('crmManageTagsExtras').component('crmManageTagsetList', {
    bindings: {
      tagsets: '=',
      activeTagset: '=',
    },
    template: '',
    controller: function($scope, $element, crmApi4) {
      const ctrl = this;
      const fetchTagsets = () => {
        crmApi4('Tag', 'get', {
          select: ['id', 'label'],
          where: [['is_tagset', '=', true]],
          orderBy: {label: 'ASC'},
        }).then((result) => {
          ctrl.tagsets = result;
          // If the active tab's tag set was just removed (e.g. via "Remove Tag Set"),
          // fall back to the overview tab rather than showing a dead, now-empty tab.
          if (ctrl.activeTagset && !result.some((tagset) => tagset.id === ctrl.activeTagset.id)) {
            ctrl.activeTagset = null;
          }
        });
      };
      this.$onInit = function() {
        fetchTagsets();
        const $closestForm = $element.closest('form');
        $closestForm.on('crmPopupFormSuccess crmFormSuccess', fetchTagsets);
        $scope.$on('$destroy', () => $closestForm.off('crmPopupFormSuccess crmFormSuccess', fetchTagsets));
      };
    },
  });

})(angular, CRM.$, CRM._);
