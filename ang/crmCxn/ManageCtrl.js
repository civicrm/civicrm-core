(function(angular, $, _) {

  angular.module('crmCxn').controller('CrmCxnManageCtrl', function CrmCxnManageCtrl($scope, apiCalls, crmApi, crmUiAlert, crmBlocker, crmStatus, $timeout, dialogService, crmCxnCheckAddr) {
    var ts = $scope.ts = CRM.ts(null);
    if (apiCalls.appMetas.is_error) {
      $scope.appMetas = [];
      CRM.alert(apiCalls.appMetas.error_message, ts('Application List Unavailable'), 'error');
    }
    else {
      $scope.appMetas = apiCalls.appMetas.values;
    }
    $scope.cxns = apiCalls.cxns.values;
    $scope.alerts = _.where(apiCalls.sysCheck.values, {name: 'checkCxnOverrides'});

    crmCxnCheckAddr(apiCalls.cfg.values.siteCallbackUrl).then(function(response) {
      if (response.valid) return;
      crmUiAlert({
        type: 'warning',
        title: ts('Internet Access Required'),
        templateUrl: '~/crmCxn/Connectivity.html',
        scope: $scope.$new(),
        options: {expires: false}
      });
    });

    $scope.filter = {};
    var block = $scope.block = crmBlocker();

    _.each($scope.alerts, function(alert){
      crmUiAlert({text: alert.message, title: alert.title, type: 'error'});
    });

    // Convert array [x] to x|null|error
    function asOne(result, msg) {
      switch (result.length) {
        case 0:
          return null;
        case 1:
          return result[0];
        default:
          throw msg;
      }
    }

    $scope.findCxnByAppId = function(appId) {
      var result = _.where($scope.cxns, {
        app_guid: appId
      });
      return asOne(result, "Error: Too many connections for appId: " + appId);
    };

    $scope.findAppByAppId = function(appId) {
      var result = _.where($scope.appMetas, {
        appId: appId
      });
      return asOne(result, "Error: Too many apps for appId: " + appId);
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

    $scope.reregister = function(appMeta) {
      var reg = crmApi('Cxn', 'register', {app_guid: appMeta.appId}).then($scope.refreshCxns);
      return block(crmStatus({start: ts('Reconnecting...'), success: ts('Reconnected')}, reg));
    };

    $scope.unregister = function(appMeta) {
      var reg = crmApi('Cxn', 'unregister', {app_guid: appMeta.appId, debug: 1}).then($scope.refreshCxns);
      return block(crmStatus({start: ts('Disconnecting...'), success: ts('Disconnected')}, reg));
    };

    $scope.toggleCxn = function toggleCxn(cxn) {
      var is_active = (cxn.is_active=="1" ? 0 : 1); // we switch the flag
      var reg = crmApi('Cxn', 'create', {id: cxn.id, app_guid: cxn.app_meta.appId, is_active: is_active, debug: 1}).then(function(){
        cxn.is_active = is_active;
      });
      return block(crmStatus({start: ts('Saving...'), success: ts('Saved')}, reg));
    };

    $scope.openLink = function openLink(appMeta, page, options) {
      var promise = crmApi('Cxn', 'getlink', {app_guid: appMeta.appId, page_name: page}).then(function(result) {
        var mode = result.values.mode ? result.values.mode : 'popup';
        switch (result.values.mode) {
          case 'iframe':
            var passThrus = ['height', 'width']; // Options influenced by remote server.
            options = angular.extend(_.pick(result.values, passThrus), options);
            $scope.openIframe(result.values.url, options);
            break;
          case 'popup':
            CRM.alert(ts('The page "%1" will open in a popup. If it does not appear automatically, check your browser for notifications.', {1: options.title}), '', 'info');
            window.open(result.values.url, 'cxnSettings', 'resizable,scrollbars,status');
            break;
          case 'redirect':
            window.location = result.values.url;
            break;
          default:
            CRM.alert(ts('Cannot open link. Unrecognized mode.'), '', 'error');
        }
      });
      return block(crmStatus({start: ts('Opening...'), success: ''}, promise));
    };

    // @param Object options -- see dialogService.open
    $scope.openIframe = function openIframe(url, options) {
      var model = {
        url: url
      };
      options = CRM.utils.adjustDialogDefaults(angular.extend(
        {
          autoOpen: false,
          height: 'auto',
          width: '40%',
          title: ts('External Link')
        },
        options
      ));
      return dialogService.open('cxnLinkDialog', '~/crmCxn/LinkDialogCtrl.html', model, options)
        .then(function(item) {
          mailing.msg_template_id = item.id;
          return item;
        });
    };
  });

})(angular, CRM.$, CRM._);
