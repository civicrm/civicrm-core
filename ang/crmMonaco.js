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

          if ($attr.ngDisabled) {
            $scope.$watch($parse($attr.ngDisabled), function(disabled){
              editor.updateOptions({readOnly: disabled});
            });
          }

          // FIXME: This makes vertical scrolling much better, but horizontal is still weird.
          var origOverflow;
          function bodyScrollSuspend() {
            if (origOverflow !== undefined) return;
            origOverflow = $('body').css('overflow');
            $('body').css('overflow', 'hidden');
          }
          function bodyScrollRestore() {
            if (origOverflow === undefined) return;
            $('body').css('overflow', origOverflow);
            origOverflow = undefined;
          }
          editorEl.on('mouseenter', bodyScrollSuspend);
          editorEl.on('mouseleave', bodyScrollRestore);
          editor.onDidFocusEditorWidget(bodyScrollSuspend);
          editor.onDidBlurEditorWidget(bodyScrollRestore);

          crmMonaco.editor = editor;

          $scope.$on('$destroy', function () {
            bodyScrollRestore();
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
