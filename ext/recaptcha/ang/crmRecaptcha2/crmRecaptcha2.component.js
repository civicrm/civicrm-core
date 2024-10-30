(function(angular, $, _) {
  angular.module('crmRecaptcha2').component('crmRecaptcha2', {
    templateUrl: '~/crmRecaptcha2/crmRecaptcha2.html',
    bindings: {
      recaptchakey: '@',
      recaptchatheme: '@',
    },
    require: {
      afForm: '^^',
    },
    controller: function($scope, $element) {
      var ctrl = this;

      this.$onInit = function() {
        this.recaptchatheme = this.recaptchatheme || 'light';

        // Global callback because recaptcha can't directly call angular functions
        window.crmRecaptcha2Change = function(response) {
          $scope.$apply(function() {
            // Add response to form data
            var extra = ctrl.afForm.getData('extra');
            extra.recaptcha2 = response;
          });
        };
      };
    }
  });
})(angular, CRM.$, CRM._);
