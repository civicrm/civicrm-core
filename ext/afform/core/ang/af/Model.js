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
        // This is faster than waiting for each field directive to register itself
        $('af-field', $el).each(function() {
          afModelListCtrl.registerField($scope.afName, $(this).attr('field-name'))
        });
      },
      controller: function($scope){
        this.getDefn = function getDefn() {
          return $scope.afModelListCtrl.getEntity($scope.afName);
          // return $scope.modelDefn;
        };
        this.getData = function getData() {
          return $scope.afModelListCtrl.getData($scope.afName);
        };
        this.getName = function() {
          return $scope.afName;
        }
      }
    };
  });
})(angular, CRM.$, CRM._);
