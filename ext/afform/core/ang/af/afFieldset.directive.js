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
            // TODO: one day we might use regular JSON for defn
            // and then switch to JSON.parse
            const defn = $scope.$eval(field.getAttribute('defn'));
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
          if (!this.reloadedStoredValues) {
            this.reloadedStoredValues = CRM.cache.get(getCacheKey(), {});
          }
          return this.reloadedStoredValues[fieldName];
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
          if (this.selectedSearchParamSet && this.selectedSearchParamSet.filters && this.selectedSearchParamSet.filters.hasOwnProperty(fieldName)) {
            return this.selectedSearchParamSet.filters[fieldName];
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
            select: ['filters', 'columns'],
          })
          .then((result) => {
            if (result && result[0]) {
              this.selectedSearchParamSet = result[0];
            }
            else {
              this.selectedSearchParamSet = {};
            }
            // reset the form so af-fields read these
            // values into their controllers
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
