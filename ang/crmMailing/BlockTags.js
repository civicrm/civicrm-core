(function(angular, $, _) {
  angular.module('crmMailing').directive('crmMailingBlockTags', function(crmMailingSimpleDirective) {
    return crmMailingSimpleDirective('crmMailingBlockTags', '~/crmMailing/BlockTags.html');
  });
})(angular, CRM.$, CRM._);
