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
          let tag = 'h4';
          if ($element.is('fieldset')) {
            tag = 'legend';
          }
          if ($element.is('details')) {
            tag = 'summary';
          }
          let $title = $element.children(tag + '.af-title');
          if (!$title.length) {
            $title = $('<' + tag + ' class="af-title" />').prependTo($element);
          }
          $title.text(text);
        });
      }
    };
  });
})(angular, CRM.$, CRM._);
