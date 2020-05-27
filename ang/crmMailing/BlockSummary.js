(function(angular, $, _) {
  angular.module('crmMailing').directive('crmMailingBlockSummary', function(crmMailingSimpleDirective) {
    return crmMailingSimpleDirective('crmMailingBlockSummary', '~/crmMailing/BlockSummary.html');
  });
})(angular, CRM.$, CRM._);
