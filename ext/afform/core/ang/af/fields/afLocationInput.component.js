(function(angular, $, _) {
  "use strict";
  angular.module('af').component('afLocationInput', {
    require: {
      ngModel: 'ngModel',
    },
    templateUrl: '~/af/fields/afLocationInput.html',
    controller: function($scope, $element) {
      const ts = $scope.ts = CRM.ts('org.civicrm.afform');

      this.values = {
        distance: null,
        // This default is set by `\Civi\Afform\AfformMetadataInjector::preprocess()`
        distance_unit: null,
        address: '',
      };

      this.$onInit = () => {
        // When the user changes a value, update the model
        $scope.$watchCollection('$ctrl.values', (values) => {
          // Only set model value if all fields have been set.
          if (values.address && values.distance_unit && values.distance !== null) {
            this.ngModel.$setViewValue(values);
          } else {
            this.ngModel.$setViewValue(null);
          }
        });

        // When the model changes, update the view
        this.ngModel.$render = () => {
          // Only update view if the model is set.
          this.values = this.ngModel.$viewValue || this.values;
        };

      };

    }
  });
})(angular, CRM.$, CRM._);
