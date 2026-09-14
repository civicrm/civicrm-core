(function(angular, $) {
  angular.module('crmMailing').directive('crmMailingBlockResponses', function(crmMailingSimpleDirective) {
    return crmMailingSimpleDirective('crmMailingBlockResponses', '~/crmMailing/BlockResponses.html');
  });
})(angular, CRM.$);
