(function(angular, $) {
  angular.module('crmMailing').directive('crmMailingBlockApprove', function(crmMailingSimpleDirective) {
    return crmMailingSimpleDirective('crmMailingBlockApprove', '~/crmMailing/BlockApprove.html');
  });
})(angular, CRM.$);
