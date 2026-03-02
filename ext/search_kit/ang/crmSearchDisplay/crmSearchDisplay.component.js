(function(angular, $, _) {
  "use strict";

  // Generic wrapper to render any display type
  angular.module('crmSearchDisplay').component('crmSearchDisplay', {
    bindings: {
      type: '@',
      apiEntity: '@',
      search: '<',
      display: '<',
      settings: '<',
      filters: '<',
    },
    template: function($element) {
      let html = '';
      const displayTypes = CRM.crmSearchDisplay.viewableDisplayTypes;
      Object.entries(displayTypes).forEach(([type, directive]) => {
        html += `<${directive} ng-if="$ctrl.type === '${type}'" api-entity="{{:: $ctrl.apiEntity }}" search="$ctrl.search" display="$ctrl.display" settings="$ctrl.settings" filters="$ctrl.filters"></${directive}>`;
      });
      return html;
    },
    controller: function($scope, $element) {
    }

  });

})(angular, CRM.$, CRM._);
