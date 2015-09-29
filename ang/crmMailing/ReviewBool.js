(function(angular, $, _) {
  angular.module('crmMailing').directive('crmMailingReviewBool', function() {
    return {
      scope: {
        crmOn: '@',
        crmTitle: '@'
      },
      template: '<span ng-class="spanClasses"><span class="icon" ng-class="iconClasses"></span>{{evalTitle}} </span>',
      link: function(scope, element, attrs) {
        function refresh() {
          if (scope.$parent.$eval(attrs.crmOn)) {
            scope.spanClasses = {'crmMailing-active': true};
            scope.iconClasses = {'ui-icon-check': true};
          }
          else {
            scope.spanClasses = {'crmMailing-inactive': true};
            scope.iconClasses = {'ui-icon-close': true};
          }
          scope.evalTitle = scope.$parent.$eval(attrs.crmTitle);
        }

        refresh();
        scope.$parent.$watch(attrs.crmOn, refresh);
        scope.$parent.$watch(attrs.crmTitle, refresh);
      }
    };
  });
})(angular, CRM.$, CRM._);
