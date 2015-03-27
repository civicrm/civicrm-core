(function(angular, $, _) {

  angular.module('crmCxn').controller('CrmCxnManageCtrl', function CrmCxnManageCtrl($scope, apiCalls, crmApi, crmUiAlert, crmBlocker, crmStatus, $timeout) {
    var ts = $scope.ts = CRM.ts(null);
    $scope.appMetas = apiCalls.appMetas.values;
    $scope.cxns = apiCalls.cxns.values;
    $scope.alerts = _.where(apiCalls.sysCheck.values, {name: 'checkCxnOverrides'});

    $scope.filter = {};
    var block = $scope.block = crmBlocker();

    _.each($scope.alerts, function(alert){
      crmUiAlert({text: alert.message, title: alert.title, type: 'error'});
    });

    $scope.findCxnByAppId = function(appId) {
      var result = _.where($scope.cxns, {
        app_guid: appId
      });
      switch (result.length) {
        case 0:
          return null;
        case 1:
          return result[0];
        default:
          throw "Error: Too many connections for appId: " + appId;
      }
    };

    $scope.hasAvailApps = function() {
      // This should usu return after the 1st or 2nd item, but in testing with small# apps, we may exhaust the list.
      for (var i = 0; i< $scope.appMetas.length; i++) {
        if (!$scope.findCxnByAppId($scope.appMetas[i].appId)) {
          return true;
        }
      }
      return false;
    };

    $scope.refreshCxns = function() {
      crmApi('Cxn', 'get', {sequential: 1}).then(function(result) {
        $timeout(function(){
          $scope.cxns = result.values;
        });
      });
    };

    $scope.register = function(appMeta) {
      var reg = crmApi('Cxn', 'register', {app_guid: appMeta.appId}).then($scope.refreshCxns);
      return block(crmStatus({start: ts('Connecting...'), success: ts('Connected')}, reg));
    };

    $scope.unregister = function(appMeta) {
      var reg = crmApi('Cxn', 'unregister', {app_guid: appMeta.appId, debug: 1}).then($scope.refreshCxns);
      return block(crmStatus({start: ts('Disconnecting...'), success: ts('Disconnected')}, reg));
    };

    $scope.toggleCxn = function toggleCxn(cxn) {
      var reg = crmApi('Cxn', 'create', {id: cxn.id, is_active: !cxn.is_active, debug: 1}).then(function(){
        cxn.is_active = !cxn.is_active;
      });
      return block(crmStatus({start: ts('Saving...'), success: ts('Saved')}, reg));
    };
  });

})(angular, CRM.$, CRM._);
