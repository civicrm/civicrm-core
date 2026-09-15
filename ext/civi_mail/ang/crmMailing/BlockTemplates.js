(function(angular, $) {
  angular.module('crmMailing').directive('crmMailingBlockTemplates', function(crmMailingSimpleDirective) {
    return crmMailingSimpleDirective('crmMailingBlockTemplates', '~/crmMailing/BlockTemplates.html');
  });
})(angular, CRM.$);