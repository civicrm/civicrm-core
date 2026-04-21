(function(angular, $, _) {
  // Example usage: <af-form><af-entity name="Donation" type="Contribution" /> ... <fieldset af-fieldset="Donation"> <af-checkout></af-checkout> ... </fieldset></af-form>
  angular.module('afCheckout').component('afCheckoutBlock', {
    require: {
      // TODO: this might be neater if we bound to the afField controller
      // and used it setValue method. but a) setValues isn't exposed method from the afField controller
      // and b) we need to get `afform_payment_processor` field value from the afFieldset anyway
      // so jump straight to fieldset for now (requires Contribution isn't a join, which it definitely shouldn't be)
      afFieldset: '^^afFieldset',
      afForm: '^^afForm',
    },
    templateUrl: '~/afCheckout/afCheckoutBlock.html',
    controller: function($scope, $element, crmApi4) {

      const ts = $scope.ts = CRM.ts('afform_payments');

      this.checkout_params = {};

      // from settings factory
      // @see \Civi\AfformPayment\AngularManager::getSettings
      const options = CRM.afCheckout.checkoutOptions;

      this.getCheckoutOptionKey = () => {
        return this.getContributionValue('checkout_option');
      };

      this.getContributionValue = (key) => {
        if (!this.afFieldset) {
          return null;
        }
        // first check for an input on the form
        const fieldData = this.afFieldset.getFieldData();
        if (fieldData && fieldData[key]) {
          return fieldData[key];
        }
        // then check for a fixed processor in the form entity data
        const entity = this.afFieldset.getEntity();
        if (entity && entity.data && entity.data[key]) {
          return entity.data[key];
        }
        return null;
      };

      this.getCheckoutOption = () => {
        const key = this.getCheckoutOptionKey();
        if (!key || !options[key]) {
          return {};
        }
        return options[key];
      };

      this.getCheckoutOptionLabel = () => {
        const meta = this.getCheckoutOption();
        return meta.label ?? null;
      };

      this.getCheckoutOptionDescription = () => {
        const meta = this.getCheckoutOption();
        return meta.description ?? null;
      };

      this.getCheckoutOptionTemplate = () => {
        const meta = this.getCheckoutOption();
        return meta.template ?? null;
      };

      this.getCheckoutOptionFields = () => {
        const meta = this.getCheckoutOption();
        return meta.fields ?? null;
      };

      /**
       * NOTE: This function is a horrible hack for handling
       * billing state/province fields from quickform payment processor
       * meta. This would be much better handled as proper afform
       * Address entities.
       *
       * TODO: move to a separate component
       * and then hopefully this can be ripped out before too long.
       */

      this.stateProvinceOptions = {};

      this.getChainSelectEmptyOptionLabel = (fieldName) => {
        if (fieldName.indexOf('billing_state_province_id-') !== 0) {
          return '- invalid chain select -';
        }
        const controlField = fieldName.replace('billing_state_province_id-', 'billing_country_id-');
        const controlValue = this.checkout_params[controlField];
        if (!controlValue || !controlValue.length || !this.stateProvinceOptions[controlValue]) {
          return ts('- select country first -');
        }
        if (!this.stateProvinceOptions[controlValue].length) {
          return ts('- please wait -');
        }
        return ts('- select -');
      };

      this.getLegacyBillingStateProvinceOptions = (fieldName) => {
        if (fieldName.indexOf('billing_state_province_id-') !== 0) {
          console.error("Unrecognised chain select field");
          return [{id: '', label: ts('- please wait -')}];
        }
        const controlField = fieldName.replace('billing_state_province_id-', 'billing_country_id-');
        // ensure both field values are initialised as empty strings
        // to show '- select -'s properly
//        if (!this.checkout_params[fieldName]) {
//          this.checkout_params[fieldName] = '';
//        }
//        if (!this.checkout_params[controlField]) {
//          this.checkout_params[controlField] = '';
//        }
        const controlValue = this.checkout_params[controlField];
        if (controlValue && controlValue.length && !this.stateProvinceOptions[controlValue]) {
          // initialising here prevents triggering multiple fetches
          this.stateProvinceOptions[controlValue] = [];
          this.fetchLegacyStateProvinceOptions(controlValue);
        }
        return this.stateProvinceOptions[controlValue];
      };

      this.fetchLegacyStateProvinceOptions = (controlValue) => crmApi4('StateProvince', 'get', {
          where: [["country_id", "=", controlValue]],
        })
        // reshape for select options
        .then((result) => result.map((record) => ({
          id: record.id,
          label: record.name
        })))
        .then((options) => this.stateProvinceOptions[controlValue] = options);


      this.exportParamValues = () => {
        // this is a quick way to check the data provider is ready
        if (!this.getCheckoutOptionKey()) {
          return;
        }
        this.afFieldset.getData()[0].fields.checkout_params = this.checkout_params;
      };

      this.checkParamValues = () => this.checkout_params;

      this.$onInit = () => {
        // reset params if payment processor changes
        $scope.$watch(this.getCheckoutOptionKey, () => this.checkout_params = {});
        // pass value changes up to the afFieldset data provider
        $scope.$watch(this.checkParamValues, () => this.exportParamValues(), true);
      };
    }
  });
})(angular, CRM.$, CRM._);
