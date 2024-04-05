(function(angular, $, _) {

  // example:
  //   scope.myAbtest = new CrmMailingAB();
  //   <crm-mailing-ab-block-setup="{abName: 1, group_percentage: 1}" crm-abtest="myAbtest" />
  var simpleDirectives = {
    crmMailingAbBlockSetup: '~/crmMailingAB/BlockSetup.html'
  };
  _.each(simpleDirectives, function(templateUrl, directiveName) {
    angular.module('crmMailingAB').directive(directiveName, function($parse, crmMailingABCriteria, crmUiHelp) {
      var scopeDesc = {crmAbtest: '@'};
      scopeDesc[directiveName] = '@';

      return {
        scope: scopeDesc,
        templateUrl: templateUrl,
        link: function(scope, elm, attr) {
          var model = $parse(attr.crmAbtest);
          scope.abtest = model(scope.$parent);
          scope.crmMailingConst = CRM.crmMailing;
          scope.crmMailingABCriteria = crmMailingABCriteria;
          scope.ts = CRM.ts('civi_mail');
          scope.hs = crmUiHelp({file: 'CRM/Mailing/MailingUI'});

          var fieldsModel = $parse(attr[directiveName]);
          scope.fields = fieldsModel(scope.$parent);
        }
      };
    });
  });

})(angular, CRM.$, CRM._);
