(function(angular, $, _) {
  angular.module('afDummy').component('afDummyCheckout', {
    require: {
      afCheckoutBlock: '^^afCheckoutBlock',
    },
    templateUrl: '~/afDummy/dummyCheckout.html',
    controller: function($scope, $element) {
      const ts = $scope.ts = CRM.ts(null);

      this.redirecting = false;

      const listener = (e, data) => this.onAfformSuccess(data);

      this.onAfformSuccess = (data) => {
        const response = data.submissionResponse;
        if (!response || !response.length || !response[0].dummy_checkout) {
          return;
        }
        this.redirecting = true;
        window.location.href = response[0].dummy_checkout.landing_url;
      };

      this.$onInit = () => {
        this.getFormElement().on('crmFormSuccess', listener);
      };

      this.$onDestroy = () => {
        this.getFormElement().off('crmFormSuccess', listener);
      };

      this.getFormElement = () => $element.closest('af-form');
    },
  });
})(angular, CRM.$, CRM._);
