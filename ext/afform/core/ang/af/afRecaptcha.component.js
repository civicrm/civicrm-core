(function(angular, $, _) {
  angular.module('af').component('afRecaptcha', {
    templateUrl: '~/af/afRecaptcha.html',
    bindings: {
      recaptchakey: '@'
    }
  });
})(angular, CRM.$, CRM._);
