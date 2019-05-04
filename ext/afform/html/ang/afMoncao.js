(function(angular, $, _) {
  angular.module('afMoncao', CRM.angRequires('afMoncao'));

  // "afMonaco" is a basic skeletal directive.
  // Example usage: <div af-monaco ng-model="my.content"></div>
  angular.module('afMoncao').directive('afMonaco', function() {
    return {
      restrict: 'AE',
      require: 'ngModel',
      template: '<div id="myContainer" style="width:800px;height:600px;border:1px solid grey"></div>',
      link: function($scope, $el, $attr, ngModel) {
        var editor;
        require.config({paths: CRM.afMoncao.paths});
        require(['vs/editor/editor.main'], function() {
          editor = monaco.editor.create(document.getElementById('myContainer'), {
            value: ngModel.$modelValue,
            language: 'html',
            theme: 'vs-dark',
            minimap: {
              enabled: false
            }
          });

          editor.onDidChangeModelContent(_.debounce(function () {
            $scope.$apply(function () {
              ngModel.$setViewValue(editor.getValue());
            });
          }, 150));

          ngModel.$render = function() {
            if (editor) {
              editor.setValue(ngModel.$modelValue);
            }
            // FIXME: else: retry?
          };

          $scope.$on('$destroy', function () {
            if (editor) editor.dispose();
          });
        });
      }
    };
  });

})(angular, CRM.$, CRM._);
