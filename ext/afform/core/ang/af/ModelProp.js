(function(angular, $, _) {
  // "afModelProp" is a basic skeletal directive.
  // Example usage: <af-model-list>... <af-model-prop af-name="myModel" af-type="Individual" /> ...</af-model-list>
  angular.module('af').directive('afModelProp', function() {
    return {
      restrict: 'AE',
      require: '^afModelList',
      scope: {
        afType: '@',
        afName: '@',
        afLabel: '@'
      },
      link: function($scope, $el, $attr, afModelListCtrl) {
        var ts = $scope.ts = CRM.ts('afform');
        afModelListCtrl.registerEntity({
          type: $scope.afType,
          name: $scope.afName,
          label: $scope.afLabel
        });
        // $scope.$watch('afModelProp', function(newValue){$scope.myOptions = newValue;});
      }
    };
  });
})(angular, CRM.$, CRM._);
