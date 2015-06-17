(function(angular, $, _) {
  angular.module('crmMailing').directive('crmMailingBlockTracking', function(crmMailingSimpleDirective) {
    return crmMailingSimpleDirective('crmMailingBlockTracking', '~/crmMailing/BlockTracking.html');
  });
})(angular, CRM.$, CRM._);
