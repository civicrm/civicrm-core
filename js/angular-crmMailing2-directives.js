(function (angular, $, _) {
  var partialUrl = function (relPath) {
    return CRM.resourceUrls['civicrm'] + '/partials/crmMailing2/' + relPath;
  };

  var crmMailing2 = angular.module('crmMailing2');

  // example: <input name="subject" /> <input crm-mailing-token crm-for="subject"/>
  // WISHLIST: Instead of global CRM.crmMailing.mailTokens, accept token list as an input
  crmMailing2.directive('crmMailingToken', function () {
    return {
      scope: {
        crmFor: '@'
      },
      template: '<input type="text" class="crmMailingToken" />',
      link: function (scope, element, attrs) {
        // 1. Find the corresponding input element (crmFor)

        var form = $(element).closest('form');
        var crmForEl = $('input[name="' + attrs.crmFor + '"],textarea[name="' + attrs.crmFor + '"]', form);
        if (form.length != 1 || crmForEl.length != 1) {
          if (console.log)
            console.log('crmMailingToken cannot be matched to input element. Expected to find one form and one input.', form.length, crmForEl.length);
          return;
        }

        // 2. Setup the token selector
        $(element).select2({width: "10em",
          dropdownAutoWidth: true,
          data: CRM.crmMailing.mailTokens,
          placeholder: ts('Insert')
        });
        $(element).on('select2-selecting', function (e) {
          var origVal = crmForEl.val();
          var origPos = crmForEl[0].selectionStart;
          var newVal = origVal.substring(0, origPos) + e.val + origVal.substring(origPos, origVal.length);
          crmForEl.val(newVal);
          var newPos = (origPos + e.val.length);
          crmForEl[0].selectionStart = newPos;
          crmForEl[0].selectionEnd = newPos;

          $(element).select2('close').select2('val', '');
          crmForEl.triggerHandler('change');
          crmForEl.focus();

          e.preventDefault();
        });
      }
    };
  });

})(angular, CRM.$, CRM._);
