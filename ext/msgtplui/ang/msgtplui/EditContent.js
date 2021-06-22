(function (angular, $, _) {
  angular.module('msgtplui').component('msgtpluiEditContent', {
    bindings: {
      onPreview: '&',
      options: '='
    },
    templateUrl: '~/msgtplui/EditContent.html',
    controller: function ($scope, $element, crmStatus, crmUiAlert, dialogService, $rootScope) {
      var ts = $scope.ts = CRM.ts('msgtplui');
      var $ctrl = this;

      $ctrl.monacoOptions = function (opts) {
        return angular.extend({}, {
          readOnly: $ctrl.options.disabled,
          wordWrap: 'wordWrapColumn',
          wordWrapColumn: 100,
          wordWrapMinified: false,
          wrappingIndent: 'indent'
        }, opts);
      };

      $ctrl.openFull = function(title, fld, monacoOptions) {
        var model = {
          title: title,
          monacoOptions: $ctrl.monacoOptions(angular.extend({crmHeightPct: 0.80}, monacoOptions)),
          openPreview: function(options) {
            return $ctrl.openPreview(options);
          },
          record: $ctrl.options.record,
          field: fld,
          tokenList: $ctrl.options.tokenList
        };
        var options = CRM.utils.adjustDialogDefaults({
          // show: {effect: 'slideDown'},
          dialogClass: 'msgtplui-dialog',
          autoOpen: false,
          height: '90%',
          width: '90%'
        });
        return dialogService.open('expandedEditDlg', '~/msgtplui/ExpandedEdit.html', model, options)
          // Nothing to do but hide warnings. The field was edited live.
          .then(function(){}, function(){});
      };

      $ctrl.openPreview = function(options) {
        $rootScope.$emit('previewMsgTpl', options);
      };

    }
  });
})(angular, CRM.$, CRM._);
