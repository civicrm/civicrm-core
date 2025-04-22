(function(angular, $, _) {
  angular.module('crmMonaco', CRM.angRequires('crmMonaco'));

  // "crmMonaco" is a basic skeletal directive.
  // Example usage: <div crm-monaco ng-model="my.content"></div>
  // Example usage: <div crm-monaco="{readOnly: true}" ng-model="my.content"></div>
  angular.module('crmMonaco').directive('crmMonaco', function($timeout, $parse) {
    return {
      restrict: 'AE',
      require: ['ngModel', 'crmMonaco'],
      template: '<div class="crm-monaco-container"></div>',
      controller: function() {
        this.editor = null; // Filled in by link().
      },
      link: function($scope, $el, $attr, controllers) {
        var ngModel = controllers[0], crmMonaco = controllers[1];
        var heightPct = 0.70;
        var editor;
        require.config({paths: CRM.crmMonaco.paths});
        require(['vs/editor/editor.main'], function() {
          var options =  {
            readOnly: false,
            language: 'html',
            // theme: 'vs-dark',
            theme: 'vs'
          };
          if ($attr.crmMonaco) {
            angular.extend(options, $parse($attr.crmMonaco)($scope));
          }
          angular.extend(options, {
            value: ngModel.$modelValue,
            minimap: {
              enabled: false
            },
            automaticLayout: true,
            scrollbar: {
              useShadows: false,
              verticalHasArrows: true,
              horizontalHasArrows: true,
              vertical: 'visible',
              horizontal: 'visible',
              verticalScrollbarSize: 17,
              horizontalScrollbarSize: 17,
              arrowSize: 30
            }
          });

          heightPct = options.crmHeightPct || heightPct;
          delete options.crmHeightPct;

          var editorEl = $el.find('.crm-monaco-container');
          editorEl.css({height: Math.round(heightPct * $(window).height())});
          let originalMessage = $parse($attr.crmMonacoOriginal)($scope);
          if (originalMessage) {
            var originalModel = monaco.editor.createModel(originalMessage);
            // var modifiedModel = monaco.editor.createModel('');
            var modifiedModel = monaco.editor.createModel(ngModel.$modelValue);
            // ^^^ This looks like it works on first load,
            // but it's not following ngModel contract -- need to sort the listeners below.

            options.renderSideBySide = false;
            options.enableSplitViewResizing = false;
            editor = monaco.editor.createDiffEditor(editorEl[0], options);
            editor.setModel({ original: originalModel, modified: modifiedModel });

            // Important -- how to propagate changes back to angular
            editor.getModifiedEditor().onDidChangeModelContent(_.debounce(function () {
              $scope.$apply(function () {
                ngModel.$setViewValue(modifiedModel.getValue());
              });
            }, 150));

            ngModel.$render = function () {
              //   console.log('update modifiedModemodifiedEditorl content ' + new Date());
              if (editor) {
                editor.getModifiedEditor().setValue(ngModel.$modelValue);
              }
              // FIXME: else: retry?
            };

          }
          else {
            editor = monaco.editor.create(editorEl[0], options);

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
          }
          if ($attr.ngDisabled) {
            $scope.$watch($parse($attr.ngDisabled), function (disabled) {
              editor.updateOptions({ readOnly: disabled });
            });
          }

          crmMonaco.editor = editor;

          $scope.$on('$destroy', function () {
            if (editor) editor.dispose();
            delete crmMonaco.editor;
          });
        });
      }
    };
  });

  angular.module('crmMonaco').directive('crmMonacoInsertRx', function() {
    return {
      require: 'crmMonaco',
      link: function(scope, element, attrs, crmMonaco) {
        scope.$on(attrs.crmMonacoInsertRx, function(e, tokenName) {
          var editor = crmMonaco.editor;
          var id = { major: 1, minor: 1 };
          var op = {identifier: id, range: editor.getSelection(), text: tokenName, forceMoveMarkers: true};
          editor.executeEdits("tokens", [op]);
          editor.focus();
        });
      }
    };
  });

})(angular, CRM.$, CRM._);
