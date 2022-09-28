(function(angular, $, _) {
  "use strict";
  angular.module('af').directive('afTitle', function() {
    return {
      restrict: 'A',
      bindToController: {
        title: '@afTitle'
      },
      controller: function($scope, $element) {
        var ctrl = this;

        $scope.$watch(function() {return ctrl.title;}, function(text) {
          var tag = $element.is('fieldset') ? 'legend' : 'h4',
            $title = $element.children(tag + '.af-title');
          if (!$title.length) {
            $title = $('<' + tag + ' class="af-title" />').prependTo($element);
            if ($element.hasClass('af-collapsible')) {
              $title.click(function() {
                $element.toggleClass('af-collapsed');
              });
            }
          }
          $title.text(text);
        });
      }
    };
  });
})(angular, CRM.$, CRM._);
