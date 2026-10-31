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
        this.authCode = (clientId) => {
          const confirmOpt = {
            message: ts('You are about to be redirected to an external site.'),
            options: {no: ts('Cancel'), yes: ts('Continue')}
          };
          CRM.confirm(confirmOpt)
            .on('crmConfirm:yes', () => {
              const going = crmApi4('OAuthClient', 'authorizationCode', {
                'landingUrl': window.location.href,
                'where': [['id', '=', clientId]]
              }).then((r) => window.location = r[0].url);
              return block(crmStatus({start: ts('Redirecting...'), success: ts('Redirecting...')}, going));
            });
        };

        $scope.oauthUtilGrantCtrl = this;
      }
    };
  });
  // Refresh one system token. A refresh the provider rejects comes back as an empty result, with the
  // provider's reason recorded on the token, so read that back to report it.
  // Ex: <a oauth-util-refresh-token="token.id" on-refreshed="tokens.refresh()">
  angular.module('oauthUtil').directive('oauthUtilRefreshToken', function($q, crmApi4, crmStatus) {
    return {
      restrict: 'A',
      scope: {
        tokenId: '<oauthUtilRefreshToken',
        onRefreshed: '&'
      },
      link: function($scope, $el) {
        $el.on('click', () => {
          const where = [['id', '=', $scope.tokenId]];
          const refreshing = crmApi4('OAuthSysToken', 'refresh', {where})
            .then((result) => result.length ? result : crmApi4('OAuthSysToken', 'get', {select: ['error'], where})
              .then((tokens) => {
                const error = (tokens[0] && tokens[0].error) || {};
                return $q.reject({error_message: error.error_description || error.error || ts('The token could not be refreshed.')});
              }));
          crmStatus({start: ts('Refreshing...'), success: ts('Refreshed')}, refreshing)
            .finally(() => $scope.onRefreshed())
            // CRM.status has already shown the error.
            .catch(() => {});
        });
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
