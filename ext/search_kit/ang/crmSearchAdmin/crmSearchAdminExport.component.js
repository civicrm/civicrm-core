(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('crmSearchAdminExport', {
    bindings: {
      savedSearch: '<'
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
        this.apiExplorerLink = CRM.url('civicrm/api4#/explorer/SavedSearch/export?_format=php&cleanup=always&id=' + ctrl.savedSearch.id);
        this.simpleLink = CRM.url('civicrm/admin/search#/create/' + ctrl.savedSearch.api_entity + '?params=' + encodeURI(angular.toJson(ctrl.savedSearch.api_params)));

        let apiCalls = [
          ['SavedSearch', 'export', {id: ctrl.savedSearch.id}],
        ];
        if (ctrl.afformEnabled) {
          let findDisplays = [['search_displays', 'CONTAINS', ctrl.savedSearch.name]];
          if (ctrl.savedSearch.display_name) {
            ctrl.savedSearch.display_name.forEach(displayName => {
              findDisplays.push(['search_displays', 'CONTAINS', `${ctrl.savedSearch.name}.${displayName}`]);
            });
          }
          apiCalls.push(['Afform', 'get', {layoutFormat: 'html', where: [['type', '=', 'search'], ['OR', findDisplays]]}]);
        }
        crmApi4(apiCalls)
          .then(function(result) {
            _.each(ctrl.types, function (type) {
              var params = _.pluck(_.where(result[0], {entity: type.entity}), 'params');
              type.values = _.pluck(params, 'values');
              type.match = params[0] && params[0].match;
              type.enabled = !!params.length;
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
        document.getElementById('crm-search-admin-export-output-code').select();
        document.execCommand('copy');
        ctrl.copied = true;
      };
    }
  });

})(angular, CRM.$, CRM._);
