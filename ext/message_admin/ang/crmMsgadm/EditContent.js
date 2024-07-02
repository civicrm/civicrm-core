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
      var ts = $scope.ts = CRM.ts('crmMsgadm');
      var $ctrl = this;

      $ctrl.isDisabled = function() {
        return $ctrl.disabled;
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
        var model = {
          title: title,
          monacoOptions: $ctrl.monacoOptions(angular.extend({crmHeightPct: 0.80}, monacoOptions)),
          openPreview: function(options) {
            return $ctrl.openPreview(options);
          },
          record: $ctrl.msgtpl,
          field: fld,
          tokenList: $ctrl.tokenList,
          original: isDiff ? $ctrl.original[fld]: ''
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
