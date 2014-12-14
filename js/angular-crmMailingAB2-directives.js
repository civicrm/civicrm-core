(function (angular, $, _) {
  var partialUrl = function (relPath) {
    return CRM.resourceUrls['civicrm'] + '/partials/crmMailingAB2/' + relPath;
  };

  // example:
  //   scope.myAbtest = new CrmMailingAB();
  //   <crm-mailing-ab-block-mailing="{fromAddressA: 1, fromAddressB: 1}" crm-abtest="myAbtest" />
  angular.module('crmMailingAB2').directive('crmMailingAbBlockMailing', function ($parse) {
    return {
      scope: {
        crmMailingAbBlockMailing: '@',
        crmAbtest: '@'
      },
      templateUrl: partialUrl('joint-mailing.html'),
      link: function (scope, elm, attr) {
        var model = $parse(attr.crmAbtest);
        scope.abtest = model(scope.$parent);
        scope.crmMailingConst = CRM.crmMailing;
        scope.ts = CRM.ts('CiviMail');

        var fieldsModel = $parse(attr.crmMailingAbBlockMailing);
        scope.fields = fieldsModel(scope.$parent);
      }
    };
  })
})(angular, CRM.$, CRM._);
