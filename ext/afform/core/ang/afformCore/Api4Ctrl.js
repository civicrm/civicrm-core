(function(angular, $, _) {

  angular.module('afformCore').directive('affApi4Ctrl', function() {
    return {
      restrict: 'EA',
      scope: {
        affApi4Ctrl: '=',
        affApi4: '@',
        affApi4Refresh: '@',
        onRefresh: '@'
      },
      controllerAs: 'affApi4Ctrl',
      controller: function($scope, $parse, crmThrottle, crmApi4) {
        var ctrl = this;

        // CONSIDER: Trade-offs of upfront vs ongoing evaluation.
        var parts = $parse($scope.affApi4)($scope.$parent);
        ctrl.entity = parts[0];
        ctrl.action = parts[1];
        ctrl.params = parts[2];
        ctrl.index = parts[3];
        ctrl.result = {};
        ctrl.loading = ctrl.firstLoad = true;

        ctrl.refresh = function refresh() {
          ctrl.loading = true;
          crmThrottle(function () {
            return crmApi4(ctrl.entity, ctrl.action, ctrl.params, ctrl.index)
              .then(function (response) {
                ctrl.result = response;
                ctrl.loading = ctrl.firstLoad = false;
                if ($scope.onRefresh) {
                  $scope.$parent.$eval($scope.onRefresh, ctrl);
                }
              });
          });
        };

        $scope.affApi4Ctrl = this;

        var mode = $scope.affApi4Refresh ? $scope.affApi4Refresh : 'auto';
        switch (mode) {
          case 'auto':
            // Note: Do NOT watch '.result' or '.loading' - causes infinite reloads.
            $scope.$watchCollection('affApi4Ctrl.params', ctrl.refresh, true);
            $scope.$watch('affApi4Ctrl.index', ctrl.refresh, true);
            $scope.$watch('affApi4Ctrl.entity', ctrl.refresh, true);
            $scope.$watch('affApi4Ctrl.action', ctrl.refresh, true);
            break;
          case 'init': ctrl.refresh(); break;
          case 'manual': break;
          default: throw 'Unrecognized refresh mode: '+ mode;
        }
      }
    };
  });

})(angular, CRM.$, CRM._);
