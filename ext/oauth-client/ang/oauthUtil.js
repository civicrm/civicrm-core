(function(angular, $) {
  angular.module('oauthUtil', CRM.angRequires('oauthUtil'));
  // Import data from the 'CRM.foo' settings.
  // Ex: <div oauth-util-import="CRM.oauthUtil.providers" to="theProviders" />
  angular.module('oauthUtil').directive('oauthUtilImport', function() {
    return {
      restrict: 'EA',
      scope: {
        to: '=',
        oauthUtilImport: '@'
      },
      controller: function($scope, $parse) {
        $scope.to = $parse($scope.oauthUtilImport)({CRM: CRM});
      }
    };
  });
  angular.module('oauthUtil').directive('oauthUtilGrantCtrl', function() {
    return {
      restrict: 'EA',
      scope: {
        oauthUtilGrantCtrl: '='
      },
      controllerAs: 'oauthUtilGrantCtrl',
      controller: function($scope, $parse, crmBlocker, crmApi4, crmStatus) {
        const block = crmBlocker();
        // A `tag` re-connects the record that tag points at, e.g. "MailSettings:12".
        this.authCode = (clientId, tag) => {
          const confirmOpt = {
            message: ts('You are about to be redirected to an external site.'),
            options: {no: ts('Cancel'), yes: ts('Continue')}
          };
          CRM.confirm(confirmOpt)
            .on('crmConfirm:yes', () => {
              const params = {
                'landingUrl': window.location.href,
                'where': [['id', '=', clientId]]
              };
              if (tag) {
                params.tag = tag;
                params.prompt = 'select_account';
              }
              const going = crmApi4('OAuthClient', 'authorizationCode', params)
                .then((r) => window.location = r[0].url);
              return block(crmStatus({start: ts('Redirecting...'), success: ts('Redirecting...')}, going));
            });
        };

        $scope.oauthUtilGrantCtrl = this;
      }
    };
  });
  angular.module('oauthUtil').directive('oauthUtilLoadSysToken', function() {
    return {
      restrict: 'A',
      controller: function($scope, $location, crmApi4) {
        $scope.$watch(() => $location.search(), (params) => {
          crmApi4('OAuthSysToken', 'get', {where: [['id', '=', params.id]]})
            .then((r) => $scope.tokens = {result: r});
        });
      }
    };
  });
})(angular, CRM.$);
