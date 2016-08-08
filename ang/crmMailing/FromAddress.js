(function(angular, $, _) {
  // Convert between a mailing "From Address" (mailing.from_name,mailing.from_email) and a unified label ("Name" <e@ma.il>)
  // example: <span crm-mailing-from-address="myPlaceholder" crm-mailing="myMailing"><select ng-model="myPlaceholder.label"></select></span>
  // NOTE: This really doesn't belong in a directive. I've tried (and failed) to make this work with a getterSetter binding, eg
  // <select ng-model="mailing.convertFromAddress" ng-model-options="{getterSetter: true}">
  angular.module('crmMailing').directive('crmMailingFromAddress', function(crmFromAddresses) {
    return {
      link: function(scope, element, attrs) {
        var placeholder = attrs.crmMailingFromAddress;
        var mailing = null;
        scope.$watch(attrs.crmMailing, function(newValue) {
          mailing = newValue;
          scope[placeholder] = {
            label: crmFromAddresses.getByAuthorEmail(mailing.from_name, mailing.from_email, true).label
          };
        });
        scope.$watch(placeholder + '.label', function(newValue) {
          var addr = crmFromAddresses.getByLabel(newValue);
          mailing.from_name = addr.author;
          mailing.from_email = addr.email;
          // CRM-18364: set replyTo as from_email only if custom replyTo is disabled in mail settings.
          if (!CRM.crmMailing.enableReplyTo) {
            mailing.replyto_email = crmFromAddresses.getByAuthorEmail(mailing.from_name, mailing.from_email, true).label;
          }
        });
        // FIXME: Shouldn't we also be watching mailing.from_name and mailing.from_email?
      }
    };
  });
})(angular, CRM.$, CRM._);
