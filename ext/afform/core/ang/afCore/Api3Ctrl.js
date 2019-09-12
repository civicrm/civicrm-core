(function(angular, $, _) {

  angular.module('afCore').directive('afApi3Ctrl', function() {
    return {
      restrict: 'EA',
      scope: {
        afApi3Ctrl: '=',
        afApi3: '@',
        afApi3Refresh: '@',
        onRefresh: '@'
      },
      controllerAs: 'afApi3Ctrl',
      controller: function($scope, $parse, crmThrottle, crmApi) {
        var ctrl = this;

        // CONSIDER: Trade-offs of upfront vs ongoing evaluation.
        var parts = $parse($scope.afApi3)($scope.$parent);
        ctrl.entity = parts[0];
        ctrl.action = parts[1];
        ctrl.params = parts[2];
        ctrl.result = {};
        ctrl.loading = ctrl.firstLoad = true;

        ctrl.refresh = function refresh() {
          ctrl.loading = true;
          crmThrottle(function () {
            return crmApi(ctrl.entity, ctrl.action, ctrl.params)
              .then(function (response) {
                ctrl.result = response;
                ctrl.loading = ctrl.firstLoad = false;
                if ($scope.onRefresh) {
                  $scope.$parent.$eval($scope.onRefresh, ctrl);
                }
              });
          });
        };

        $scope.afApi3Ctrl = this;

        var mode = $scope.afApi3Refresh ? $scope.afApi3Refresh : 'auto';
        switch (mode) {
          case 'auto': $scope.$watchCollection('afApi3Ctrl.params', ctrl.refresh); break;
          case 'init': ctrl.refresh(); break;
          case 'manual': break;
          default: throw 'Unrecognized refresh mode: '+ mode;
        }
      }
    };
  });

})(angular, CRM.$, CRM._);
