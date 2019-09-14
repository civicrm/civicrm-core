(function(angular, $, _) {
  // "afEntity" is a basic skeletal directive.
  // Example usage: <af-form>... <af-entity name="myModel" type="Individual" /> ...</af-form>
  angular.module('af').directive('afEntity', function() {
    // Whitelist of all allowed properties of an af-fieldset
    // (at least the ones we care about client-side - other's can be added for server-side processing and we'll just ignore them)
    var modelProps = {
      type: '@',
      data: '=',
      modelName: '@name',
      label: '@',
      autofill: '@'
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
        // $scope.$watch('afEntity', function(newValue){$scope.myOptions = newValue;});
      }
    };
  });
})(angular, CRM.$, CRM._);
