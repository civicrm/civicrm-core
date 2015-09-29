(function(angular, $, _) {
  angular.module('crmMailing').directive('crmMailingBlockApprove', function(crmMailingSimpleDirective) {
    return crmMailingSimpleDirective('crmMailingBlockApprove', '~/crmMailing/BlockApprove.html');
  });
})(angular, CRM.$, CRM._);
