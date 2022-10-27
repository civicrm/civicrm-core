(function(angular, $, _) {
  // Whitelist of all allowed properties of an af-fieldset
  // (at least the ones we care about client-side - other's can be added for server-side processing and we'll just ignore them)
  var modelProps = {
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
        var entity = _.pick(this, _.keys(modelProps));
        entity.actions = entity.actions || {update: true, create: true};
        entity.id = null;
        this.afForm.registerEntity(entity);
      };
    }

  });
})(angular, CRM.$, CRM._);
