(function (angular, $, _) {
  angular.module('crmMsgadm').component('crmMsgadmEditContent', {
    bindings: {
      onPreview: '&',
      tokenList: '<',
      disabled: '<',
      original: '=',
      msgtpl: '='
    },
    templateUrl: '~/crmMsgadm/EditContent.html',
    controller: function ($scope, $element, crmStatus, crmUiAlert, dialogService, $rootScope) {
      const ts = $scope.ts = CRM.ts('crmMsgadm');
      const $ctrl = this;

      $ctrl.isDisabled = function() {
        return $ctrl.disabled;
      };

      // "Show diff" only has something to diff against for templates that have a reserved-default
      // "Original" revision - without this guard, opening it for one that doesn't crashes trying to
      // read a field off $ctrl.original, which is undefined.
      $ctrl.hasDiffBase = function() {
        return !!$ctrl.original;
      };

      $ctrl.monacoOptions = function (opts) {
        return angular.extend({}, {
          wordWrap: 'wordWrapColumn',
          wordWrapColumn: 100,
          wordWrapMinified: false,
          wrappingIndent: 'indent'
        }, opts);
      };

      $ctrl.openFull = function(title, fld, monacoOptions, isDiff = false) {
        const model = {
          title: title,
          monacoOptions: $ctrl.monacoOptions(angular.extend({crmHeightPct: 0.80}, monacoOptions)),
          openPreview: function (options) {
            return $ctrl.openPreview(options);
          },
          record: $ctrl.msgtpl,
          field: fld,
          tokenList: $ctrl.tokenList,
          // Inserting a token doesn't make sense while comparing two versions of the content.
          isDiff: isDiff,
          original: (isDiff && $ctrl.hasDiffBase()) ? $ctrl.original[fld] : ''
        };
        var options = CRM.utils.adjustDialogDefaults({
          // show: {effect: 'slideDown'},
          dialogClass: 'crm-msgadm-dialog',
          autoOpen: false,
          height: '90%',
          width: '90%'
        });
        return dialogService.open('expandedEditDlg', '~/crmMsgadm/ExpandedEdit.html', model, options)
          // Nothing to do but hide warnings. The field was edited live.
          .then(function(){}, function(){});
      };

      $ctrl.openPreview = function(options) {
        $rootScope.$emit('previewMsgTpl', options);
      };

    }
  });
})(angular, CRM.$, CRM._);
