(function(angular, $, _) {
  // Example usage: <af-form><af-entity name="Person" type="Contact" /> ... <fieldset af-fieldset="Person> ... </fieldset></af-form>
  angular.module('af').directive('afFieldset', function() {
    return {
      restrict: 'A',
      require: ['afFieldset', '?^^afForm'],
      bindToController: {
        modelName: '@afFieldset',
        storeValues: '<'
      },
      link: function($scope, $el, $attr, ctrls) {
        var self = ctrls[0];
        self.afFormCtrl = ctrls[1];
      },
      controller: function($scope, $element) {
        let ctrl = this;
        let localData = [];
        let joinOffsets = {};
        let ts = $scope.ts = CRM.ts('org.civicrm.afform');

        this.getData = function() {
          return ctrl.afFormCtrl ? ctrl.afFormCtrl.getData(ctrl.modelName) : localData;
        };
        // Get name of afform entity or search display
        this.getName = function() {
          return this.modelName ||
            // If there is no Afform entity, get the name of embedded search display
            $element.find('[search-name][display-name]').attr('display-name');
        };
        this.getEntity = function() {
          return this.afFormCtrl.getEntity(this.modelName);
        };
        this.getEntityType = function() {
          return this.afFormCtrl.getEntity(this.modelName).type;
        };
        this.getFieldData = function() {
          var data = ctrl.getData();
          if (!data.length) {
            data.push({fields: {}});
          }
          return data[0].fields;
        };
        this.getFormName = function() {
          return ctrl.afFormCtrl ? ctrl.afFormCtrl.getFormMeta().name : $scope.meta.name;
        };

        this.getJoinOffset = function(joinEntity) {
          joinOffsets[joinEntity] = joinEntity in joinOffsets ? joinOffsets[joinEntity] + 1 : 0;
          return joinOffsets[joinEntity];
        };

        // If `storeValue` setting is enabled, field values are cached in localStorage
        function getCacheKey() {
          return 'afform:' + ctrl.getFormName() + ctrl.getName();
        }
        this.getStoredValue = function(fieldName) {
          if (!this.storeValues) {
            return;
          }
          return CRM.cache.get(getCacheKey(), {})[fieldName];
        };
        this.$onInit = function() {
          if (this.storeValues) {
            $scope.$watch(ctrl.getFieldData, function(newVal, oldVal) {
              if (typeof newVal === 'object' && typeof oldVal === 'object' && Object.keys(newVal).length) {
                CRM.cache.set(getCacheKey(), newVal);
              }
            }, true);
          }
        };
      }
    };
  });
})(angular, CRM.$, CRM._);
