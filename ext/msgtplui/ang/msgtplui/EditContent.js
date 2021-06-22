(function (angular, $, _) {
  angular.module('msgtplui').component('msgtpluiEditContent', {
    bindings: {
      options: '='
    },
    templateUrl: '~/msgtplui/EditContent.html',
    controller: function ($scope, $element, crmStatus, crmUiAlert, dialogService) {
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
          openPreview: function() { return $ctrl.openPreview(model.field); },
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

      $ctrl.openPreview = function(fld) {
        crmUiAlert({type: 'error', title: ts('TODO: openPreview'), text: ts('Not yet implemented')});
      };

    }
  });
})(angular, CRM.$, CRM._);
