(function(angular, $, _) {
  // "afWillBeModel" is a basic skeletal directive.
  // Example usage: <af-form>... <af-will-be-model af-name="myModel" type="Individual" /> ...</af-form>
  angular.module('af').directive('afWillBeModel', function() {
    // Whitelist of all allowed properties of an af-model
    // (at least the ones we care about client-side - other's can be added for server-side processing and we'll just ignore them)
    var modelProps = {
      type: '@',
      afData: '=',
      afName: '@',
      afLabel: '@',
      afAutofill: '@'
    };
    return {
      restrict: 'AE',
      require: '^afForm',
      scope: modelProps,
      link: function($scope, $el, $attr, afFormCtrl) {
        var ts = $scope.ts = CRM.ts('afform'),
          entity = _.pick($scope, _.keys(modelProps));
        entity.id = null;
        entity.fields = [];
        afFormCtrl.registerEntity(entity);
        // $scope.$watch('afWillBeModel', function(newValue){$scope.myOptions = newValue;});
      }
    };
  });
})(angular, CRM.$, CRM._);
