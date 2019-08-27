(function(angular, $, _) {
  // "afModelProp" is a basic skeletal directive.
  // Example usage: <af-model-list>... <af-model-prop af-name="myModel" af-type="Individual" /> ...</af-model-list>
  angular.module('af').directive('afModelProp', function() {
    // Whitelist of all allowed properties of an af-model
    // (at least the ones we care about client-side - other's can be added for server-side processing and we'll just ignore them)
    var modelProps = {
      afType: '@',
      afData: '=',
      afName: '@',
      afLabel: '@',
      afAutofill: '@'
    };
    return {
      restrict: 'AE',
      require: '^afModelList',
      scope: modelProps,
      link: function($scope, $el, $attr, afModelListCtrl) {
        var ts = $scope.ts = CRM.ts('afform'),
          entity = _.pick($scope, _.keys(modelProps));
        entity.id = null;
        entity.fields = [];
        afModelListCtrl.registerEntity(entity);
        // $scope.$watch('afModelProp', function(newValue){$scope.myOptions = newValue;});
      }
    };
  });
})(angular, CRM.$, CRM._);
