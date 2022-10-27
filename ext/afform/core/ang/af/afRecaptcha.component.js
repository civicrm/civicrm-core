(function(angular, $, _) {
  angular.module('af').component('afRecaptcha', {
    templateUrl: '~/af/afRecaptcha.html',
    controller: function($element) {
      $('div.g-recaptcha').attr('data-sitekey', $($element).attr('recaptchakey'));
    }
  });
})(angular, CRM.$, CRM._);
