(function(angular, $, _) {
  // "afFieldset" is a basic skeletal directive.
  // Example usage: <af-form>... <af-fieldset model="myModel">...</af-fieldset> ...</af-form>
  angular.module('af').directive('afFieldset', function() {
    return {
      restrict: 'AE',
      require: '^afForm',
      scope: {
        modelName: '@model'
      },
      link: function($scope, $el, $attr, afFormCtrl) {
        $scope.afFormCtrl = afFormCtrl;
        // This is faster than waiting for each field directive to register itself
        $('af-field', $el).each(function() {
          afFormCtrl.registerField($scope.modelName, $(this).attr('name'))
        });
      },
      controller: function($scope){
        this.getDefn = function getDefn() {
          return $scope.afFormCtrl.getEntity($scope.modelName);
          // return $scope.modelDefn;
        };
        this.getData = function getData() {
          return $scope.afFormCtrl.getData($scope.modelName);
        };
        this.getName = function() {
          return $scope.modelName;
        }
      }
    };
  });
})(angular, CRM.$, CRM._);
