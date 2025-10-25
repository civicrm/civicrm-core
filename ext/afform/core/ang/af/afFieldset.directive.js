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
      controller: function($scope, $element, crmApi4) {
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

        this.getFieldMeta = () => {
          const meta = {};
          const fieldElements = $element[0].querySelectorAll('af-field');
          fieldElements.forEach((field) => {
            const name = field.getAttribute('name');
            // this is nasty but we cant parse the `defn` attribute directly
            // because its not proper JSON, so this is least nasty for now
            const defn = angular.element(field).controller('afField').defn;
            meta[name] = defn;
          });
          return meta;
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
          $scope.$watch(this.getSearchParamSetId, () => this.fetchSearchParamSetValues());
        };

        /**
         * Get fieldset values to use for afform filters
         * @returns Object
         */
        this.getFilterValues = () => {
          const data = this.getFieldData();
          // filter out unset values
          // intended to be equivalent to previous lodash implementation
          // (typeof val !== 'undefined' && val !== null && (_.includes(['boolean', 'number', 'object'], typeof val) || val.length));
          return Object.fromEntries(Object.entries(data).filter(([key, value]) =>
            (['boolean', 'number', 'object'].includes(typeof value) && value !== null) || (value && value.length)
          ));
        };

        /**
         * Get value for a given field based on currently applied SearchParamSet
         * Used by afField controller on load
         */
        this.getSearchParamSetFieldValue = (fieldName) => {
          if (this.searchParamSetValues && this.searchParamSetValues.hasOwnProperty(fieldName)) {
            return this.searchParamSetValues[fieldName];
          }
          return null;
        };

        /**
         * Fetch values for a SearchParamSet from the server
         */
        this.fetchSearchParamSetValues = () => {
          const searchParamSetId = this.getSearchParamSetId();
          if (!searchParamSetId) {
            return;
          }
          crmApi4('SearchParamSet', 'get', {
            where: [
              ['id', '=', searchParamSetId],
              // check the searchParamSet ID is relevant to the current afform
              // (in case multiple on the page)
              ['afform_name', '=', this.getFormName()]
            ],
            select: ['filters'],
          })
          .then((result) => {
            if (result && result[0] && result[0].filters) {
              this.searchParamSetValues = result[0].filters;
            }
            else {
              this.searchParamSetValues = {};
            }
            $scope.$broadcast('afFormReset');
          });
        };

        /**
         * Get search param set ID from URL hash param
         */
        this.getSearchParamSetId = () => ($scope && $scope.routeParams) ? $scope.routeParams._s : null;
      }
    };
  });
})(angular, CRM.$, CRM._);
