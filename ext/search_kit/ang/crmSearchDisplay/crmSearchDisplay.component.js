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
    template: () => {
      let html = '';
      const displayTypes = CRM.crmSearchDisplay.viewableDisplayTypes;
      Object.entries(displayTypes).forEach(([type, directive]) => {
        html += `<${directive} ng-if="$ctrl.type === '${type}'" api-entity="{{:: $ctrl.apiEntity }}" search="$ctrl.search" display="$ctrl.display" settings="$ctrl.settings" filters="$ctrl.filters"></${directive}>`;
      });
      return html;
    },
    controller: function($scope, crmApi4) {

      this.$onInit = () => {
        // Load display settings if not supplied by e.g. AfformSearchMetadataInjector
        if (!this.settings || !this.apiEntity || !this.type) {
          const apiCalls = {
            search: ['SavedSearch', 'get', {
              select: ['id', 'name', 'api_entity'],
              where: [['name', '=', this.search]],
            }, 0],
          };
          if (this.display) {
            apiCalls.display = ['SearchDisplay', 'get', {
              select: ['type', 'settings'],
              where: [['name', '=', this.display], ['saved_search_id.name', '=', this.search]],
            }, 0];
          } else {
            apiCalls.display = ['SearchDisplay', 'getDefault', {
              select: ['type', 'settings'],
              savedSearch: this.search,
            }, 0];
          }
          crmApi4(apiCalls).then((result) => {
            this.apiEntity = result.search.api_entity;
            this.settings = result.display.settings;
            this.type = result.display.type;
          });
        }
      };

    }

  });

})(angular, CRM.$, CRM._);
