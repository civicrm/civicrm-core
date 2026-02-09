(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('crmSearchAdminNested', {
    bindings: {
      display: '<',
    },
    require: {
      crmSearchAdmin: '^crmSearchAdmin'
    },
    templateUrl: '~/crmSearchAdmin/crmSearchAdminNested.html',
    controller: function ($scope, searchMeta, crmApi4) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this;

      this.$onInit = function() {
        const searchName = ctrl.display.settings?.nested?.search;
        if (searchName) {
          getNestedSearchInfo(searchName).then((result) => {
            // If search doesn't exist, it can't be used
            if (!result.savedSearch) {
              delete ctrl.display.settings.nested;
            }
          });
        }
        else if (ctrl.display.settings.nested) {
          delete ctrl.display.settings.nested;
        }
      };

      function getNestedField(fieldName) {
        return searchMeta.getField(fieldName, ctrl.savedSearch.api_entity);
      }

      function getNestedSearchInfo(searchName) {
        ctrl.searchDisplays = null;
        const apiCalls = crmApi4({
          searchDisplays: ['SearchDisplay', 'get', {
            select: ['name', 'label'],
            where: [
              ['saved_search_id.name', '=', searchName],
              // For now this is the only type of embedded display we support
              ['type:name', '=', 'crm-search-display-table'],
            ],
          }],
          savedSearch: ['SavedSearch', 'get', {
            select: ['api_entity', 'api_params'],
            where: [['name', '=', searchName]],
          }, 0],
        });
        apiCalls.then((result) => {
          ctrl.searchDisplays = result.searchDisplays;
          ctrl.savedSearch = result.savedSearch;
          // Parse fields
          ctrl.nestedFields = ctrl.savedSearch.api_params.select.reduce((fields, fieldName) => {
            const field = getNestedField(fieldName);
            if (field) {
              fields.push({
                id: field.name,
                text: field.label,
              });
            }
            return fields;
          }, []);
        });
        return apiCalls;
      }

      this.onChangeNestedSearch = () => {
        const searchName = ctrl.display.settings?.nested?.search;
        if (!searchName) {
          delete ctrl.display.settings.nested;
          ctrl.savedSearch = null;
          ctrl.searchDisplays = null;
        } else {
          ctrl.display.settings.nested.filters = [];
          getNestedSearchInfo(searchName).then((result) => {
            if (result.searchDisplays.length) {
              ctrl.display.settings.nested.display = result.searchDisplays[0].name;
              // Set default filter
              const baseEntity = searchMeta.getBaseEntity();
              ctrl.savedSearch.api_params.select.forEach((fieldName) => {
                const field = getNestedField(fieldName);
                if (field?.fk_entity === baseEntity.name) {
                  ctrl.display.settings.nested.filters.push({
                    field: field.name,
                    data: baseEntity.primary_key[0],
                  });
                }
              });
              ctrl.noIdFilterFound = !ctrl.display.settings.nested.filters.length;
            }
          });
        }
      };

      this.onChangeNestedFilter = (index) => {
        if (!ctrl.display.settings.nested.filters[index].field) {
          ctrl.display.settings.nested.filters.splice(index, 1);
        }
      };

      this.addFilter = (fieldName) => {
        ctrl.display.settings.nested.filters = ctrl.display.settings.nested.filters || [];
        ctrl.display.settings.nested.filters.push({
          field: fieldName,
          data: null,
        });
      };

      this.fieldsForFilter = function() {
        return {
          results: ctrl.crmSearchAdmin.getAllFields('', ['Field', 'Custom', 'Extra']),
        };
      };

    }
  });

})(angular, CRM.$, CRM._);
