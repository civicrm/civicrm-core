(function(angular, $, _) {

  angular.module('afCore').directive('afApi4Ctrl', function() {
    return {
      restrict: 'EA',
      scope: {
        afApi4Ctrl: '=',
        afApi4: '@',
        afApi4Refresh: '@',
        onRefresh: '@'
      },
      controllerAs: 'afApi4Ctrl',
      controller: function($scope, $parse, crmThrottle, crmApi4) {
        var ctrl = this;

        // CONSIDER: Trade-offs of upfront vs ongoing evaluation.
        var parts = $parse($scope.afApi4)($scope.$parent);
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

        $scope.afApi4Ctrl = this;

        var mode = $scope.afApi4Refresh ? $scope.afApi4Refresh : 'auto';
        switch (mode) {
          case 'auto':
            // Note: Do NOT watch '.result' or '.loading' - causes infinite reloads.
            $scope.$watchCollection('afApi4Ctrl.params', ctrl.refresh, true);
            $scope.$watch('afApi4Ctrl.index', ctrl.refresh, true);
            $scope.$watch('afApi4Ctrl.entity', ctrl.refresh, true);
            $scope.$watch('afApi4Ctrl.action', ctrl.refresh, true);
            break;
          case 'init': ctrl.refresh(); break;
          case 'manual': break;
          default: throw 'Unrecognized refresh mode: '+ mode;
        }
      }
    };
  });

})(angular, CRM.$, CRM._);
