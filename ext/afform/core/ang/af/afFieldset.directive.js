(function(angular, $, _) {
  // Example usage: <af-form><af-entity name="Person" type="Contact" /> ... <fieldset af-fieldset="Person> ... </fieldset></af-form>
  angular.module('af').directive('afFieldset', function() {
    return {
      restrict: 'A',
      require: ['afFieldset', '^afForm'],
      bindToController: {
        modelName: '@afFieldset'
      },
      link: function($scope, $el, $attr, ctrls) {
        var self = ctrls[0];
        self.afFormCtrl = ctrls[1];
      },
      controller: function($scope){
        this.getDefn = function() {
          return this.afFormCtrl.getEntity(this.modelName);
        };
        this.getData = function() {
          return this.afFormCtrl.getData(this.modelName);
        };
        this.getName = function() {
          return this.modelName;
        };
        this.getEntityType = function() {
          return this.afFormCtrl.getEntity(this.modelName).type;
        };
        this.getFieldData = function() {
          var data = this.getData();
          if (!data.length) {
            data.push({fields: {}});
          }
          return data[0].fields;
        };
      }
    };
  });
})(angular, CRM.$, CRM._);
