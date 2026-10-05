(function(angular, $) {
  angular.module('crmMailing').directive('crmMailingBlockRecipients', function(crmMailingSimpleDirective) {
    return crmMailingSimpleDirective('crmMailingBlockRecipients', '~/crmMailing/BlockRecipients.html');
  });
})(angular, CRM.$);
