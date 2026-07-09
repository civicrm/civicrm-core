(function(angular, $, _) {
  "use strict";

  let formTypes;

  angular.module('afAdmin').component('afAdminListMenu', {
    bindings: {
      tab: '@',
    },
    templateUrl: '~/afAdmin/afAdminListMenu.html',
    controller: function($scope, afGui) {
      const ctrl = this;
      const ts = $scope.ts = CRM.ts('org.civicrm.afform_admin');

      $scope.searchCreateLinks = {};

      this.$onInit = () => {
        if (!formTypes) {
          formTypes = CRM.afAdmin.afform_fields.type.options.reduce((formTypes, option) => {
            formTypes[option.name] = {...option};
            return formTypes;
          }, {});
        }
        this.formType = formTypes[this.tab];
        if (this.tab === 'form') {
          this.formType.default = '#create/form/Individual';
        }
      };

      this.createLinks = () => {
        // Reset search input in dropdown
        $scope.searchCreateLinks.label = '';
        // A value means it's alredy loaded. Null means it's loading.
        if (this.formType.options || this.formType.options === null) {
          return;
        }

        this.formType.options = null;
        const links = [];

        if (ctrl.tab === 'form') {
          Object.entries(CRM.afGuiEditor.entities).forEach(([name, entity]) => {
            if (entity.type === 'primary') {
              links.push({
                url: '#create/form/' + name,
                label: entity.label,
                icon: entity.icon
              });
            }
          });
          this.formType.options = _.sortBy(links, 'Label');
        }

        if (ctrl.tab === 'block') {
          Object.entries(CRM.afGuiEditor.entities).forEach(([name, entity]) => {
            if (true) { // FIXME: What conditions do we use for block entities?
              links.push({
                url: '#create/block/' + name,
                label: entity.label,
                icon: entity.icon || 'fa-cog'
              });
            }
          });
          this.formType.options = _.sortBy(links, (item) => {
            return item.url === '#create/block/*' ? '0' : item.label;
          });
          // Add divider after the * entity (content block)
          this.formType.options.splice(1, 0, {'class': 'divider', label: ''});
        }

        if (ctrl.tab === 'search') {
          afGui.getAllSearchDisplays().then((links) => {
            this.formType.options = links;
          });
        }
      };
    }
  });

})(angular, CRM.$, CRM._);
