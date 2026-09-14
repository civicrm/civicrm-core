(function(angular, $) {
  angular.module('crmMailing').directive('crmMailingBodyText', function(crmMailingSimpleDirective) {
    return crmMailingSimpleDirective('crmMailingBodyText', '~/crmMailing/BodyText.html');
  });
})(angular, CRM.$);
