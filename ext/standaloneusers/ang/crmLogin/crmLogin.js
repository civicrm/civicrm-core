(function(angular, $, _) {
  "use strict";

  angular.module('crmLogin', CRM.angRequires('crmLogin'));

  angular.module('crmLogin').component('crmLogin', {
    templateUrl: '~/crmLogin/crmLogin.html',
    bindings: {},
    controller: function($scope, $window, $timeout, crmApi4, crmStatus) {
      var ts = $scope.ts = CRM.ts('standaloneusers');

      this.loading = false;

      this.forgottenPasswordUrl = CRM.url('civicrm/login/password');

      this.canSubmit = () => {
        return this.identifier && this.password && !this.loading;
      };

      this.attemptLogin = () => {
        if (!this.canSubmit()) {
          throw new Error('Invalid login');
        }

        this.loading = true;

        let originalUrl = location.href;

        // Remove the current status popup messages.
        $('#crm-notification-container .ui-notify-message').remove();

        let publicError = ts('Unexpected error');

        crmApi4('User', 'login', {
          identifier: this.identifier,
          password: this.password,
          originalUrl
        })
        .then((response) => {
          if (response.url) {
            $window.location = response.url;
            return;
          }
          if (response.publicError) {
            publicError = response.publicError;
            throw new Error('Login failed');
          }
        })
        .catch((e) => {
          this.loading = false;
          CRM.alert('', publicError, 'error', {'expires': 10000});
        });
      };
    }
  });
})(angular, CRM.$, CRM._);
