(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('crmSearchAdminNested', {
    bindings: {
      display: '<',
      column: '<',
    },
    require: {
      crmSearchAdmin: '^crmSearchAdmin'
    },
    templateUrl: '~/crmSearchAdmin/crmSearchAdminNested.html',
    controller: function ($scope, searchMeta, crmApi4) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit');

      this.$onInit = () => {
        const searchName = this.column.nested?.search;
        if (searchName) {
          getNestedSearchInfo(searchName).then((result) => {
            // If search doesn't exist, it can't be used
            if (!result.savedSearch) {
              delete this.column.nested;
            }
          });
        }
        else if (this.column.nested) {
          delete this.column.nested;
        }
      };

      const getNestedField = (fieldName) => searchMeta.getField(fieldName, this.savedSearch.api_entity);

      const getNestedSearchInfo = (searchName) => {
        this.searchDisplays = null;
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
          this.searchDisplays = result.searchDisplays;
          this.savedSearch = result.savedSearch;
          // Parse fields
          this.nestedFields = this.savedSearch.api_params.select.reduce((fields, fieldName) => {
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
        const searchName = this.column.nested?.search;
        if (!searchName) {
          delete this.column.nested;
          this.savedSearch = null;
          this.searchDisplays = null;
        } else {
          this.column.nested.filters = [];
          getNestedSearchInfo(searchName).then((result) => {
            if (result.searchDisplays.length) {
              this.column.nested.display = result.searchDisplays[0].name;
              // Set default filter
              const baseEntity = searchMeta.getBaseEntity();
              this.savedSearch.api_params.select.forEach((fieldName) => {
                const field = getNestedField(fieldName);
                if (field?.fk_entity === baseEntity.name) {
                  this.column.nested.filters.push({
                    field: field.name,
                    data: baseEntity.primary_key[0],
                  });
                }
              });
              this.noIdFilterFound = !this.column.nested.filters.length;
            }
          });
        }
      };

      this.onChangeNestedFilter = (index) => {
        if (!this.column.nested.filters[index].field) {
          this.column.nested.filters.splice(index, 1);
        }
      };

      this.addFilter = (fieldName) => {
        this.column.nested.filters = this.column.nested.filters || [];
        this.column.nested.filters.push({
          field: fieldName,
          data: null,
        });
      };

      this.fieldsForFilter = () => ({
        results: this.crmSearchAdmin.getAllFields('', ['Field', 'Custom', 'Extra']),
      });

    }
  });

})(angular, CRM.$, CRM._);
