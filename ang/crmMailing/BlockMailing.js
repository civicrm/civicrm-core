(function(angular, $, _) {
  angular.module('crmMailing').directive('crmMailingBlockMailing', function(crmMailingSimpleDirective) {
    return crmMailingSimpleDirective('crmMailingBlockMailing', '~/crmMailing/BlockMailing.html');
  });
})(angular, CRM.$, CRM._);
