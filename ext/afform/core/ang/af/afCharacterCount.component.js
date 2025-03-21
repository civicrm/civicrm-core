(function(angular, $, _) {
  "use strict";
  angular.module('af').component('afCharacterCount', {
    bindings: {
      maxlength: '<',
      getter: '<',
    },
    templateUrl: '~/af/afCharacterCount.html',
    controller: function($scope, $element) {
      this.$onInit = function() {
      };

      this.getLength = function() {
        return (this.getter() || '').length;
      };

      this.getStatusClass = function() {
        let fraction = this.getLength() / (this.maxlength || 1);
        if (fraction > 0.9) {
          return 'label-danger';
        }
        if (fraction > 0.7) {
          return 'label-warning';
        }
        return 'label-success';
      };

    }
  });
})(angular, CRM.$, CRM._);
