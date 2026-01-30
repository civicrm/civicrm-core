(function(angular, $, _) {
  // Whitelist of all allowed properties of an af-fieldset
  // (at least the ones we care about client-side - other's can be added for server-side processing and we'll just ignore them)
  const modelProps = {
    type: '@',
    data: '=',
    actions: '=',
    modelName: '@name',
    label: '@'
  };
  // Example usage: <af-form><af-entity name="Person" type="Contact" /> ... <fieldset af-fieldset="Person"> ... </fieldset></af-form>
  angular.module('af').component('afEntity', {
    require: {afForm: '^afForm'},
    bindings: modelProps,
    controller: function() {

      this.$onInit = function() {
        // Reduce allowed entity properties to the whitelist
        const entity = Object.keys(modelProps).reduce((obj, key) => {
          if (this[key] !== undefined) {
            obj[key] = this[key];
          }
          return obj;
        }, {});
        entity.actions = entity.actions || {update: true, create: true};
        entity.id = null;
        this.afForm.registerEntity(entity);
      };
    }

  });
})(angular, CRM.$, CRM._);
