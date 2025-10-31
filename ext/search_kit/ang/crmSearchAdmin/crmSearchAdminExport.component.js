(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('crmSearchAdminExport', {
    bindings: {
      savedSearch: '<'
    },
    templateUrl: '~/crmSearchAdmin/crmSearchAdminExport.html',
    controller: function ($scope, $element, crmApi4) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit');
      const ctrl = this;
      this.afformEnabled = 'org.civicrm.afform' in CRM.crmSearchAdmin.modules;

      this.types = [
        {entity: 'SavedSearch', title: ts('Saved Search')},
        {entity: 'SearchDisplay', title: ts('1 Display'), plural: ts('%1 Displays')},
        {entity: 'Group', title: ts('Smart Group'), plural: ts('%1 Smart Groups')},
      ];

      this.$onInit = function() {
        this.apiExplorerLink = CRM.url('civicrm/api4#/explorer/SavedSearch/export?_format=php&cleanup=always&id=' + ctrl.savedSearch.id);
        this.simpleLink = CRM.url('civicrm/admin/search#/create/' + ctrl.savedSearch.api_entity + '?params=' + encodeURI(angular.toJson(ctrl.savedSearch.api_params)));

        const apiCalls = [
          ['SavedSearch', 'export', {id: ctrl.savedSearch.id}],
        ];
        if (ctrl.afformEnabled) {
          const findDisplays = [['search_displays', 'CONTAINS', ctrl.savedSearch.name]];
          if (ctrl.savedSearch.display_name) {
            ctrl.savedSearch.display_name.forEach(displayName => {
              findDisplays.push(['search_displays', 'CONTAINS', `${ctrl.savedSearch.name}.${displayName}`]);
            });
          }
          apiCalls.push(['Afform', 'getFields', {action: 'create', select: ['name'], where: [['readonly', '=', false], ['type', '=', 'Field']]}, ['name']]);
          apiCalls.push(['Afform', 'get', {layoutFormat: 'html', where: [['type', '=', 'search'], ['OR', findDisplays]]}]);
        }
        crmApi4(apiCalls)
          .then(function(result) {
            ctrl.types.forEach(type => {
              const params = result[0]
                .filter(item => item.entity === type.entity)
                .map(item => item.params);
              type.values = params.map(param => param.values);
              type.match = params[0] && params[0].match;
              type.enabled = !!params.length;
            });
            // Afforms are not included in the export and are fetched separately
            if (ctrl.afformEnabled) {
              const afformFields = result[1];
              // Filter out readonly and null fields
              const afforms = result[2].map(afform => {
                return afformFields.reduce((obj, key) => {
                  if (key in afform && afform[key] !== null) {
                    obj[key] = afform[key];
                  }
                  return obj;
                }, {});
              });
              ctrl.types.push({entity: 'Afform', enabled: !!afforms.length, values: afforms, title: ts('1 Form'), plural: ts('%1 Forms')});
            }
            ctrl.refreshOutput();
          });
      };

      this.refreshOutput = function() {
        const data = [];
        ctrl.types.forEach(function(type) {
          if (type.enabled) {
            const params = {records: type.values};
            if (type.match && type.match.length) {
              params.match = type.match;
            }
            data.push([type.entity, 'save', params]);
          }
        });
        ctrl.output = JSON.stringify(data, null, 2);
        ctrl.copied = false;
      };

      this.copyToClipboard = function() {
        const textElement = document.getElementById('crm-search-admin-export-output-code');

        function copyWithClipboardApi() {
          navigator.clipboard.writeText(textElement.value)
            .then(() => {
              $scope.$evalAsync(() => {
                ctrl.copied = true;
              });
            })
            .catch(error => {
              $scope.$evalAsync(legacyCopy);
            });
        }

        function legacyCopy() {
          textElement.select();
          document.execCommand('copy');
          ctrl.copied = true;
        }

        try {
          if (navigator.clipboard && window.isSecureContext) {
            copyWithClipboardApi();
          } else {
            legacyCopy();
          }
        } catch (error) {
          console.error('Failed to copy text: ', error);
          ctrl.copied = false;
        }
      };
    }
  });

})(angular, CRM.$, CRM._);
