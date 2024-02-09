(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchTasks').directive('crmSearchInputFocus', function($timeout) {
    return {
      link: function(scope, element, attrs) {
        const e = $(element[0]);

        var focusOnElement = function() {
          scope.manualFocus = true;
          e.trigger('focus');
        };

        var removeFocus = function () {
          e.trigger('blur');
        };

        var endManualFocus = function () {
          scope.manualFocus = false;
        };

        scope.$watch(attrs.crmSearchInputFocus, function(flag) {
          if(flag === true) {
            $timeout(focusOnElement).then(endManualFocus);
          }
        });

        scope.$watch(attrs.crmSearchInputBlinkFocus, function(flag) {
          if(flag === true) {
            $timeout(focusOnElement).then(removeFocus).then(endManualFocus);
          }
        });
      }
    };
  });

})(angular, CRM.$, CRM._);
