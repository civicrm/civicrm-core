(function(angular, $) {
  angular.module('crmMailing').directive('crmMailingBlockPublication', function (crmMailingSimpleDirective) {
    return crmMailingSimpleDirective('crmMailingBlockPublication', '~/crmMailing/BlockPublication.html');
  });
})(angular, CRM.$);
