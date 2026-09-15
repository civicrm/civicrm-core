(function(angular, $) {
  angular.module('crmMailing').directive('crmMailingBlockHeaderFooter', function(crmMailingSimpleDirective) {
    return crmMailingSimpleDirective('crmMailingBlockHeaderFooter', '~/crmMailing/BlockHeaderFooter.html');
  });
})(angular, CRM.$);
