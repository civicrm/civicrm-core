(function(angular, $, _) {
  // "afModel" is a basic skeletal directive.
  // Example usage: <af-model-list>... <af-model af-name="myModel">...</af-model> ...</af-model-list>
  angular.module('af').directive('afModel', function() {
    return {
      restrict: 'AE',
      require: '^afModelList',
      scope: {
        afName: '@'
      },
      link: function($scope, $el, $attr, afModelListCtrl) {
        $scope.afModelListCtrl = afModelListCtrl;
        // $scope.$watch('afName', function(newValue){
        //   // $scope.myOptions = newValue;
        //   $scope.modelDefn = afModelListCtrl.getEntity(newValue);
        //   console.log('Lookup entity', newValue, $scope.modelDefn);
        // });
      },
      controller: function($scope){
        this.getDefn = function getDefn() {
          return $scope.afModelListCtrl.getEntity($scope.afName);
          // return $scope.modelDefn;
        }
      }
    };
  });
})(angular, CRM.$, CRM._);
