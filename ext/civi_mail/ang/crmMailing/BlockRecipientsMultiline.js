(function(angular, $) {
  angular.module('crmMailing').directive('crmMailingBlockRecipientsMultiline', function(crmMailingSimpleDirective) {
    return crmMailingSimpleDirective('crmMailingBlockRecipientsMultiline', '~/crmMailing/BlockRecipientsMultiline.html');
  });
})(angular, CRM.$);
