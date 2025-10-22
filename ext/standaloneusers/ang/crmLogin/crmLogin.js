(function(angular, $, _) {
  "use strict";

  angular.module('crmLogin', CRM.angRequires('crmLogin'));

  angular.module('crmLogin').component('crmLogin', {
    templateUrl: '~/crmLogin/crmLogin.html',
    bindings: {},
    controller: function($scope, $window, $timeout, crmApi4, crmStatus) {
      var ts = $scope.ts = CRM.ts('standaloneusers');

      this.loading = true;
      this.loggedInAs = null;

      let rememberJWT = localStorage.getItem('rememberJWT');
      this.rememberMe = !!rememberJWT;
      this.mfa_remember = CRM.vars.standalone.mfa_remember;

      this.forgottenPasswordUrl = CRM.url('civicrm/login/password');

      this.logoutUrl = CRM.url('civicrm/logout');
      this.$onInit = () => {
        if (CRM.config.cid) {
          return crmApi4('Contact', 'get', {
            select: ['display_name'],
            where: [['id', '=', CRM.config.cid]],
          })
          .then((result) => this.loggedInAs = ts('Logged in as %1.', {1: result[0].display_name}))
          .then(() => this.loading = false);
        }
        else {
          this.loading = false;
        }
      };

      this.canSubmit = () => {
        return this.identifier && this.password && !this.loading;
      };
      this.rememberMeChanged = () => {
        if (!this.rememberMe) {
          // Immediately remove the JWT if user de-selects.
          localStorage.removeItem('rememberJWT');
        }
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

        if (!this.rememberMe) {
          // They have chosen not to remember, so clear any existing thing now.
          localStorage.removeItem('rememberJWT');
          rememberJWT = null;
        }

        crmApi4('User', 'login', {
          identifier: this.identifier,
          password: this.password,
          originalUrl,
          rememberMe: this.rememberMe,
          rememberJWT
        })
        .then((response) => {
          if (response.url) {
            // Success.
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
