(function(angular, $, _) {
  angular.module('crmMailing').directive('crmMailingBlockSchedule', function(crmMailingSimpleDirective) {
    return crmMailingSimpleDirective('crmMailingBlockSchedule', '~/crmMailing/BlockSchedule.html');
  });
})(angular, CRM.$, CRM._);
