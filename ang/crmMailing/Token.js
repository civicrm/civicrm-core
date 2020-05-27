(function(angular, $, _) {
  // example: <input name="subject" /> <input crm-mailing-token on-select="doSomething(token.name)" />
  // WISHLIST: Instead of global CRM.crmMailing.mailTokens, accept token list as an input
  angular.module('crmMailing').directive('crmMailingToken', function() {
    return {
      require: '^crmUiIdScope',
      scope: {
        onSelect: '@'
      },
      template: '<input type="text" class="crmMailingToken" />',
      link: function(scope, element, attrs, crmUiIdCtrl) {
        $(element).addClass('crm-action-menu fa-code').crmSelect2({
          width: "12em",
          dropdownAutoWidth: true,
          data: CRM.crmMailing.mailTokens,
          placeholder: ts('Tokens')
        });
        $(element).on('select2-selecting', function(e) {
          e.preventDefault();
          $(element).select2('close').select2('val', '');
          scope.$parent.$eval(attrs.onSelect, {
            token: {name: e.val}
          });
        });
      }
    };
  });
})(angular, CRM.$, CRM._);
