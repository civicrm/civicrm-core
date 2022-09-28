(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('crmSearchAdminExport', {
    bindings: {
      savedSearchId: '<',
      savedSearchName: '<',
      displayNames: '<'
    },
    templateUrl: '~/crmSearchAdmin/crmSearchAdminExport.html',
    controller: function ($scope, $element, crmApi4) {
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this;
      this.afformEnabled = 'org.civicrm.afform' in CRM.crmSearchAdmin.modules;

      this.types = [
        {entity: 'SavedSearch', title: ts('Saved Search')},
        {entity: 'SearchDisplay', title: ts('1 Display'), plural: ts('%1 Displays')},
        {entity: 'Group', title: ts('Smart Group'), plural: ts('%1 Smart Groups')},
      ];

      this.$onInit = function() {
        this.apiExplorerLink = CRM.url('civicrm/api4#/explorer/SavedSearch/export?_format=php&id=' + ctrl.savedSearchId);

        var findDisplays = _.transform(ctrl.displayNames, function(findDisplays, displayName) {
          findDisplays.push(['search_displays', 'CONTAINS', ctrl.savedSearchName + '.' + displayName]);
        }, [['search_displays', 'CONTAINS', ctrl.savedSearchName]]);
        var apiCalls = [
          ['SavedSearch', 'export', {id: ctrl.savedSearchId}],
        ];
        if (ctrl.afformEnabled) {
          apiCalls.push(['Afform', 'get', {layoutFormat: 'html', where: [['type', '=', 'search'], ['OR', findDisplays]]}]);
        }
        crmApi4(apiCalls)
          .then(function(result) {
            _.each(ctrl.types, function (type) {
              type.values = _.pluck(_.pluck(_.where(result[0], {entity: type.entity}), 'params'), 'values');
              type.enabled = !!type.values.length;
            });
            // Afforms are not included in the export and are fetched separately
            if (ctrl.afformEnabled) {
              ctrl.types.push({entity: 'Afform', enabled: !!result[1].length, values: _.toArray(result[1]), title: ts('1 Form'), plural: ts('%1 Forms')});
            }
            ctrl.refreshOutput();
          });
      };

      this.refreshOutput = function() {
        var data = [];
        _.each(ctrl.types, function(type) {
          if (type.enabled) {
            var params = {records: type.values};
            // Afform always matches on 'name', no need to add it to the API 'save' params
            if (type.entity !== 'Afform') {
              // Group and SavedSearch match by 'name', SearchDisplay also matches by 'saved_search_id'.
              params.match = type.entity === 'SearchDisplay' ? ['name', 'saved_search_id'] : ['name'];
            }
            data.push([type.entity, 'save', params]);
          }
        });
        ctrl.output = JSON.stringify(data, null, 2);
        ctrl.copied = false;
      };

      this.copyToClipboard = function() {
        document.getElementById('crm-search-admin-export-output-code').select();
        document.execCommand('copy');
        ctrl.copied = true;
      };
    }
  });

})(angular, CRM.$, CRM._);
