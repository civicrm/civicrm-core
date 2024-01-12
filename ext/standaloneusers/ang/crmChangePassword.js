(function (angular, $, _) {
  "use strict";

  angular.module('crmChangePassword', CRM.angRequires('crmChangePassword'));

  angular.module('crmChangePassword').component('crmChangePassword', {
    templateUrl: '~/crmChangePassword/crmChangePassword.html',
    bindings: {
      // things listed here become properties on the controller using values from attributes.
      hibp: '@',
      userId: '@'
    },
    controller: function($scope, $timeout, crmApi4) {
      var ts = $scope.ts = CRM.ts(null),
      ctrl = this;

      console.log('init crmChangePassword component starting');
      // $onInit gets run after the this controller is called, and after the bindings have been applied.
      // this.$onInit = function() { console.log('user', ctrl.userId); };
      ctrl.actorPassword = '';
      ctrl.newPassword = '';
      ctrl.newPasswordAgain = '';
      ctrl.busy = '';
      ctrl.pwnd = false;

      let updateAngular = (prop, newVal) => {
        $timeout(() => {
          console.log("Setting", prop, "to", newVal);
          ctrl[prop] = newVal;
        }, 0);
      };

      ctrl.attemptChange = () => {
        updateAngular('busy', '');
        updateAngular('pwnd', false);
        updateAngular('formInactive', true);
        if (ctrl.newPassword.length < 8) {
          alert(ts("C'mon now, you can do better than that."));
          return;
        }
        if (ctrl.newPassword != ctrl.newPasswordAgain) {
          alert(ts("Passwords do not match"));
          return;
        }

        let promises = Promise.resolve(null);
        if (ctrl.hibp) {
          promises = promises.then(() => {
            updateAngular('busy', ts('Checking password is not known to have been involved in data breach...'));
            return sha1(ctrl.newPassword)
              .then(hash => {
                if (!hash.match(/^[a-f0-9]+$/)) {
                  updateAngular('busy', ts('Could not check. Is your browser up-to-date?'));
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
                        updateAngular('pwn', true);
                        return;
                      }
                      updateAngular('busy', '');
                    })
                    .catch( () => {
                      updateAngular('busy', ts('Could not perform check; service error.'));
                    });
                }
              });
          });
        }

        promises = promises.then(() => {
          updateAngular('busy', ctrl.busy + ts('Changing...'));
          // Now submit api request.
          const userUpdateParams = {
            actorPassword: ctrl.actorPassword,
            values: {password: ctrl.newPassword},
            where: [['id', '=', ctrl.userId]]
          };
          return crmApi4('User', 'Update', userUpdateParams)
            .then(r => updateAngular('busy', ts('Password successfully updated')))
            .catch(e => {
              let msg = (e.error_message === 'Authorization failed') ?
                ts("Sorry, that didn't work. Perhaps you mistyped your password?") :
                e.error_message;
              updateAngular('busy', msg);
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
