(function(angular, $) {
  "use strict";

  angular.module('crmSearchTasks').directive('crmSearchInputFocus', function($timeout) {
    function waitForElement(selector, context = document) {
      return new Promise((resolve) => {
        const observer = new MutationObserver((mutations, observer) => {
          const match = $(selector, context);
          if (match.length) {
            observer.disconnect();
            resolve(match);
          }
        });

        observer.observe(context, {
          childList: true,
          subtree: true,
        });
      });
    }

    return {
      link: function(scope, element, attrs) {
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
          let [jQueryObject, type] = getFocusableInput();

          if (type === 'default') {
            jQueryObject.first().trigger('focus');
          }

          else if (type === 'boolean') {
            // focus on the checked option, if any
            let checkedItem = $(':checked', element);
            if (checkedItem.length) {
              jQueryObject = checkedItem;
            }
            jQueryObject.first().trigger('focus');
          }

          else if (type === 'select2') {
            // the widget isn't built yet; wait for it to exist
            waitForElement('.select2-choice', element[0]).then((elements) => {
              $timeout(() => {
                const container = elements.first().closest('.select2-container');
                // this takes care of some non-ajax select2s
                container.select2('open');
                // for ajax select2s, wait until the selected option is rendered
                $(element[0]).one('initSelectionComplete', () => {
                  container.select2('open');
                });
              });
            });
          }

          else if (type === 'datepicker') {
            // the widget isn't built yet; wait for it to exist
            waitForElement('.hasDatepicker', element[0]).then((elements) => {
              elements.first().datepicker('show');
            });
          }
        }

        scope.$watch(attrs.crmSearchInputFocus, function(flag) {
          if(flag === true) {
            $timeout(focusOn);
          }
        });
      }
    };
  });

})(angular, CRM.$);
