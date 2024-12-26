(function (angular) {
  "use strict";

  angular.module('crmResetPassword', CRM.angRequires('crmResetPassword'));

  angular.module('crmResetPassword').component('crmResetPassword', {
    templateUrl: '~/crmResetPassword/crmResetPassword.html',
    bindings: {
      // things listed here become properties on the controller using values from attributes.
      hibp: '@',
      token: '@'
    },
    controller: function($scope, $timeout, crmApi4) {
      var ts = $scope.ts = CRM.ts(null),
      ctrl = this;

      // console.log('init crmResetPassword component starting');
      // $onInit gets run after the this controller is called, and after the bindings have been applied.
      // this.$onInit = function() { console.log('user', ctrl.userId); };

      ctrl.completeReset = () => {
        ctrl.busy='';
        ctrl.formSubmitted = false;
        ctrl.identifier = '';
        ctrl.newPassword = '';
        ctrl.newPasswordAgain = '';
        ctrl.pwnd = false;
        ctrl.resetSuccessfullySubmitted=false;
        ctrl.token='';
      };
      ctrl.completeReset();

      let updateAngular = (prop, newVal) => {
        $timeout(() => {
          console.log("Setting", prop, "to", newVal);
          ctrl[prop] = newVal;
        }, 0);
      };
      ctrl.requestPasswordResetEmail = () => {
        updateAngular('busy', ts('Just a moment...'));
        updateAngular('formSubmitted', true);
        if (!ctrl.identifier) {
          alert(ts('Please provide your username/email.'));
          return;
        }
        crmApi4('User', 'requestPasswordResetEmail', { identifier: ctrl.identifier })
        .then(r => {
          updateAngular('busy', '');
          updateAngular('resetSuccessfullySubmitted', true);
        })
        .catch(e => {
          updateAngular('busy', ts('Sorry, something went wrong. Please contact your site administrators.'));
        });
      };

      ctrl.attemptChange = () => {
        updateAngular('busy', '');
        updateAngular('formSubmitted', true);
        updateAngular('pwnd', false);
        if (ctrl.newPassword.length < 8) {
          alert(ts("Passwords under 8 characters are simply not secure. Ideally you should use a secure password generator."));
          return;
        }
        if (ctrl.newPassword != ctrl.newPasswordAgain) {
          updateAngular('formSubmitted', false);
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
          updateAngular('formSubmitted', true);
          // Now submit api request.
          return crmApi4('User', 'passwordReset', {
              token: ctrl.token,
              password: ctrl.newPassword,
            })
            .then(r => {
              updateAngular('busy', ts('Password successfully updated. Redirecting to login...'));
              $timeout(() => {
                window.location = '/civicrm/login';
              }, 1300);
            })
            .catch(e => {
              updateAngular('token', 'invalid');
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
})(angular);
