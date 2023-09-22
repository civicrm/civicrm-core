(function(angular, $, _) {

  angular.module('afCore').directive('afApi4Action', function($parse, crmStatus, crmApi4) {
    return {
      restrict: 'A',
      scope: {
        afApi4Action: '@',
        afApi4StartMsg: '=',
        afApi4ErrorMsg: '=',
        afApi4SuccessMsg: '=',
        afApi4Success: '@',
        onError: '@'
      },
      link: function($scope, $el, $attr) {
        var ts = CRM.ts(null);
        function running(x) {$el.toggleClass('af-api4-action-running', x).toggleClass('af-api4-action-idle', !x);}
        running(false);
        $el.click(function(){
          var parts = $parse($scope.afApi4Action)($scope.$parent);
          var msgs = {start: $scope.afApi4StartMsg || ts('Submitting...'), success: $scope.afApi4SuccessMsg, error: $scope.afApi4ErrorMsg};
          running(true);
          crmStatus(msgs, crmApi4(parts[0], parts[1], parts[2]))
            .finally(function(){running(false);})
            .then(function(response){$scope.$parent.$eval($scope.afApi4Success, {response: response});})
            .catch(function(error){$scope.$parent.$eval($scope.onError, {error: error});});
        });
      }
    };
  });

})(angular, CRM.$, CRM._);
