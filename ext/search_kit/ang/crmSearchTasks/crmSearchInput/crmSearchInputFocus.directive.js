(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchTasks').directive('crmSearchInputFocus', function($timeout) {
    return {
      link: function(scope, element, attrs) {

        function waitForElement(selector, context = document) {
          return new Promise((resolve) => {
            const observer = new MutationObserver((mutations, observer) => {
              const jQueryResult = $(selector, context);
              if (jQueryResult.length) {
                observer.disconnect();
                resolve(jQueryResult);
              }
            });

            observer.observe(context, {
              childList: true,
              subtree: true,
            });
          });
        }

        function getFocusableInput() {
          const types = new Map ([
            ['select2', 'input[crm-ui-select], input[crm-autocomplete]'],
            ['datepicker', 'input[crm-ui-datepicker]'],
            ['boolean', 'input[type="radio"], input[type="checkbox"]'],
            ['default', ':focusable']
          ]);
          for (const [type, selector] of types) {
            const jQueryObject = $(selector, element);
            if (jQueryObject.length > 0 || type === 'default') {
              return [jQueryObject, type];
            }
          }
        }

        function focusOn() {
          scope.manualFocus = true;
          let [jQueryObject, type] = getFocusableInput();
          if (type === 'default') {
            jQueryObject.first().trigger('focus');
          } else if (type === 'boolean') {
            let checkedItem = $(':checked', element);
            if (checkedItem.length) {
              jQueryObject = checkedItem;
            }
            jQueryObject.first().trigger('focus');
          }
          else if (type === 'select2') {
            waitForElement('.select2-choice', element[0]).then((elements) => {
              $timeout(() => {
                const container = elements.first().closest('.select2-container');
                container.select2('open');
                $(element[0]).one('initSelectionComplete', () => {
                  container.select2('open');
                });
              });
            });
          } else if (type === 'datepicker') {
            waitForElement('.hasDatepicker', element[0]).then((elements) => {
              elements.first().datepicker('show');
            });
          }
        }

        function removeFocus() {
          getFocusableInput().trigger('blur');
        }

        function endManualFocus() {
          scope.manualFocus = false;
        }

        scope.$watch(attrs.crmSearchInputFocus, function(flag) {
          if(flag === true) {
            $timeout(focusOn).then(endManualFocus);
          }
        });
      }
    };
  });

})(angular, CRM.$, CRM._);
