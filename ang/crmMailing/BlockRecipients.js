(function(angular, $, _) {
  angular.module('crmMailing').directive('crmMailingBlockRecipients', function(crmMailingSimpleDirective) {
    return crmMailingSimpleDirective('crmMailingBlockRecipients', '~/crmMailing/BlockRecipients.html');
  });
})(angular, CRM.$, CRM._);
