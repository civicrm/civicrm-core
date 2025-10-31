(function(angular, $, _) {
  "use strict";

  angular.module('crmChangePassword', CRM.angRequires('crmChangePassword'));

  angular.module('crmChangePassword').component('crmChangePassword', {
    templateUrl: '~/crmChangePassword/crmChangePassword.html',
    bindings: {
      // things listed here become properties on the controller using values from attributes.
      hibp: '@',
      userId: '@'
    },
    controller: function($scope, $timeout, crmApi4, crmStatus) {
      var ts = $scope.ts = CRM.ts(null),
        ctrl = this;

      ctrl.actorPassword = '';
      ctrl.newPassword = '';
      ctrl.newPasswordAgain = '';
      ctrl.busy = '';
      ctrl.pwnd = false;

      let updateAngular = (newVals) => {
        $timeout(() => Object.assign(ctrl, newVals), 0);
      };

      ctrl.attemptChange = () => {
        updateAngular({ busy: '', pwnd: false, formInactive: true });
        if (ctrl.newPassword != ctrl.newPasswordAgain) {
          alert(ts("Passwords do not match"));
          return;
        }

        let promises = Promise.resolve({ okToProceed: true });
        if (ctrl.hibp) {
          promises = promises.then(() => {
            updateAngular({ busy: ts('Checking password is not known to have been involved in data breach...') });
            return sha1(ctrl.newPassword)
              .then(hash => {
                if (!hash.match(/^[a-f0-9]+$/)) {
                  updateAngular({ busy: ts('Could not check. Is your browser up-to-date?') });
                }
                else {
                  hash = hash.toUpperCase();
                  let hashPrefix = hash.substring(0, 5);
                  return fetch(ctrl.hibp + hashPrefix)
                    .then(r => r.text())
                    .then(hibpResult => {
                      if (hibpResult &&
                        hibpResult.split(/\r\n/).find(line => hashPrefix + line.replace(/:\d+$/, '') === hash)) {
                        // e.g. Password123
                        updateAngular({ pwnd: true, busy: '' });
                        return { okToProceed: false };
                      }
                      updateAngular({ busy: '' });
                      return { okToProceed: true };
                    })
                    .catch(() => {
                      updateAngular({ busy: ts('Could not perform check; service error.') });
                      return { okToProceed: false };
                    });
                }
              });
          });
        }

        promises = promises.then((status) => {
          if (!status.okToProceed) {
            CRM.alert(
              ts('The given password cannot be considered secure.'),
              ts('Password NOT changed'),
              'error');
            console.log("Prevented changing password because of hibp result");
            return;
          }

          updateAngular({ busy: ctrl.busy + ts('Changing...') });
          // Now submit api request.
          const userUpdateParams = {
            actorPassword: ctrl.actorPassword,
            values: { password: ctrl.newPassword },
            where: [['id', '=', ctrl.userId]]
          };
          return crmApi4('User', 'Update', userUpdateParams)
            .then(r => {
              CRM.alert(ts('Your password was successfully changed.'), ts('Password updated'), 'success');
              updateAngular({ busy: '' });
            })
            .catch(e => {
              CRM.alert(
                (e.error_message === 'Authorization failed') ?
                  ts('Perhaps you mistyped your existing password?') :
                  e.error_message,
                ts('Password NOT changed'),
                'error');
              updateAngular({ busy: '' });
            });
        });
      };

      // Generate SHA-1 digest for given text. Returns Promise
      function sha1(message) {
        const encoder = new TextEncoder();
        const data = encoder.encode(message);
        // const hashBuffer =
        return crypto.subtle.digest('SHA-1', data)
          .then(hashBuffer => {
            const hashArray = Array.from(new Uint8Array(hashBuffer)); // convert buffer to byte array
            const hashHex = hashArray
              .map((b) => b.toString(16).padStart(2, "0"))
              .join(""); // convert bytes to hex string
            return hashHex;
          });
      }
    }
  });
})(angular, CRM.$, CRM._);
