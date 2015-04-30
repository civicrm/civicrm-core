(function(angular, $, _) {
  angular.module('crmSetting').config(function($routeProvider) {
      $routeProvider.when('/settings', {
        controller: 'CrmSettingEditCtrl',
        templateUrl: '~/crmSetting/EditCtrl.html',

        // Fetch settings metadata when opening the page
        resolve: {
          settingsFields: function(crmApi) {
            return crmApi('Setting', 'getfields', {}).then(function (response) {
              return _.groupBy(response.values, 'group_name');
            });
          },
          settingsValues: function(crmApi) {
            return crmApi('Setting', 'get', {}).then(function (response) {
              return response.values;
            });
          }
        }
      });
    }
  );

  // The controller uses *injection*. This default injects a few things:
  //   $scope -- This is the set of variables shared between JS and HTML.
  //   crmApi, crmStatus, crmUiHelp -- These are services provided by civicrm-core.
  //   settingsFields, settingsValues -- Defined above in config().
  angular.module('crmSetting').controller('CrmSettingEditCtrl', function($scope, crmApi, crmStatus, crmUiHelp, settingsFields, settingsValues) {
 
    // The ts() and hs() functions help load strings for this module.
    var ts = $scope.ts = CRM.ts(null);
    //var hs = $scope.hs = crmUiHelp({file: 'CRM/settings/SettingsCtrl'}); // See: templates/CRM/settings/SettingsCtrl.hlp
    var hs = $scope.hs = '';

    // Make data available to the HTML layer.
    $scope.settingsFields = settingsFields;
    $scope.settingsValues = settingsValues;

    $scope.save = function save() {
      return crmStatus(
        // Status messages. For defaults, just use "{}"
        {start: ts('Saving...'), success: ts('Saved')},
        // The save action. Note that crmApi() returns a promise.
        crmApi('Contact', 'create', {
          id: myContact.id,
          first_name: myContact.first_name,
          last_name: myContact.last_name
        })
      );
    };
  });

})(angular, CRM.$, CRM._);
