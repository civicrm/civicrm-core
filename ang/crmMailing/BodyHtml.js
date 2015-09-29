(function(angular, $, _) {
  angular.module('crmMailing').directive('crmMailingBodyHtml', function(crmMailingSimpleDirective) {
    return crmMailingSimpleDirective('crmMailingBodyHtml', '~/crmMailing/BodyHtml.html');
  });
})(angular, CRM.$, CRM._);
