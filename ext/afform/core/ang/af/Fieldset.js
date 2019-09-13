(function(angular, $, _) {
  // "afFieldset" is a basic skeletal directive.
  // Example usage: <af-form>... <af-fieldset af-name="myModel">...</af-fieldset> ...</af-form>
  angular.module('af').directive('afFieldset', function() {
    return {
      restrict: 'AE',
      require: '^afForm',
      scope: {
        afName: '@'
      },
      link: function($scope, $el, $attr, afFormCtrl) {
        $scope.afFormCtrl = afFormCtrl;
        // This is faster than waiting for each field directive to register itself
        $('af-field', $el).each(function() {
          afFormCtrl.registerField($scope.afName, $(this).attr('field-name'))
        });
      },
      controller: function($scope){
        this.getDefn = function getDefn() {
          return $scope.afFormCtrl.getEntity($scope.afName);
          // return $scope.modelDefn;
        };
        this.getData = function getData() {
          return $scope.afFormCtrl.getData($scope.afName);
        };
        this.getName = function() {
          return $scope.afName;
        }
      }
    };
  });
})(angular, CRM.$, CRM._);
