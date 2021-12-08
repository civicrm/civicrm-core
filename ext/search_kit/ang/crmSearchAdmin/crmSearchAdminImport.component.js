(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('crmSearchAdminImport', {
    templateUrl: '~/crmSearchAdmin/crmSearchAdminImport.html',
    controller: function ($scope, dialogService, crmApi4) {
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this;

      this.values = '';

      this.run = function() {
        ctrl.running = true;
        try {
          var apiCalls = JSON.parse(ctrl.values);
          _.each(apiCalls, function(apiCall) {
            if (apiCall[1] !== 'create' || ('chain' in apiCall[2] && !_.isEmpty(apiCall[2].chain))) {
              throw ts('Unsupported API action: only "create" is allowed.');
            }
          });
          crmApi4(apiCalls)
            .then(function(result) {
              CRM.alert(
                result.length === 1 ? ts('1 record successfully imported.') : ts('%1 records successfully imported.', {1: results.length}),
                ts('Saved'),
                'success'
              );
              dialogService.close('crmSearchAdminImport');
            }, function(error) {
              ctrl.running = false;
              alert(ts('Processing Error') + "\n" + error.error_message);
            });
        } catch(e) {
          ctrl.running = false;
          alert(ts('Input Error') + "\n" + e);
        }
      };
    }
  });

})(angular, CRM.$, CRM._);
