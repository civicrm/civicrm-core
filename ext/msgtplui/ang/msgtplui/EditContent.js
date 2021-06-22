(function (angular, $, _) {
  angular.module('msgtplui').component('msgtpluiEditContent', {
    bindings: {
      options: '='
    },
    templateUrl: '~/msgtplui/EditContent.html',
    controller: function ($scope, $element, crmStatus, crmUiAlert) {
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

      $ctrl.openFull = function(fld) {
        crmUiAlert({type: 'error', title: ts('TODO: openFull'), text: ts('Not yet implemented')});
      };

      $ctrl.openPreview = function(fld) {
        crmUiAlert({type: 'error', title: ts('TODO: openPreview'), text: ts('Not yet implemented')});
      };

    }
  });
})(angular, CRM.$, CRM._);
