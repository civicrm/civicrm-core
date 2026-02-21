(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('crmSearchAdminSubsearch', {
    bindings: {
      display: '<',
      column: '<',
    },
    require: {
      crmSearchAdmin: '^crmSearchAdmin'
    },
    templateUrl: '~/crmSearchAdmin/crmSearchAdminSubsearch.html',
    controller: function ($scope, searchMeta, crmApi4) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit');

      this.$onInit = () => {
        const searchName = this.column.subsearch?.search;
        if (searchName) {
          getSubsearchInfo(searchName).then((result) => {
            // If search doesn't exist, it can't be used
            if (!result.savedSearch) {
              delete this.column.subsearch.search;
              delete this.column.subsearch.display;
            }
          });
        }
      };

      const getSubsearchField = (fieldName) => searchMeta.getField(fieldName, this.savedSearch.api_entity);

      const getSubsearchInfo = (searchName) => {
        this.searchDisplays = null;
        const apiCalls = crmApi4({
          searchDisplays: ['SearchDisplay', 'get', {
            select: ['name', 'label'],
            where: [
              ['saved_search_id.name', '=', searchName],
              // Include all viewable display types
              ['type', 'IN', Object.keys(CRM.crmSearchDisplay.viewableDisplayTypes)],
            ],
          }],
          savedSearch: ['SavedSearch', 'get', {
            select: ['label', 'api_entity', 'api_params'],
            where: [['name', '=', searchName]],
          }, 0],
        });
        apiCalls.then((result) => {
          this.searchDisplays = result.searchDisplays;
          this.savedSearch = result.savedSearch;
          // Parse fields
          this.subsearchFields = this.savedSearch.api_params.select.reduce((fields, fieldName) => {
            const field = getSubsearchField(fieldName);
            if (field) {
              fields.push({
                id: fieldName.split(':')[0],
                text: field.label,
              });
            }
            return fields;
          }, []);
        });
        return apiCalls;
      };

      this.onChangeSearch = () => {
        const searchName = this.column.subsearch?.search;
        if (!searchName) {
          delete this.column.subsearch;
          this.savedSearch = null;
          this.searchDisplays = null;
        } else {
          this.column.subsearch.filters = [];
          getSubsearchInfo(searchName).then((result) => {
            if (result.searchDisplays.length) {
              // Set default label
              this.column.label = this.column.label || result.savedSearch.label;
              this.column.rewrite = this.column.rewrite || result.savedSearch.label;

              this.column.subsearch.display = result.searchDisplays[0].name;
              // Set default filter
              const baseEntity = searchMeta.getBaseEntity();
              const baseKey = baseEntity.primary_key[0];
              this.savedSearch.api_params.select.forEach((fieldName) => {
                const field = getSubsearchField(fieldName);
                if (field?.fk_entity === baseEntity.name || (field?.name === baseKey && field?.entity === baseEntity.name)) {
                  this.column.subsearch.filters.push({
                    subsearch_field: fieldName.split(':')[0],
                    parent_field: baseKey,
                  });
                }
              });
              this.noIdFilterFound = !this.column.subsearch.filters.length;
            }
          });
        }
      };

      this.onChangeFilter = (index) => {
        if (!this.column.subsearch.filters[index].field) {
          this.column.subsearch.filters.splice(index, 1);
        }
      };

      this.addFilter = (fieldName) => {
        this.column.subsearch.filters = this.column.subsearch.filters || [];
        this.column.subsearch.filters.push({
          subsearch_field: fieldName,
          parent_field: null,
        });
      };

      this.fieldsForFilter = () => ({
        results: this.crmSearchAdmin.getAllFields('', ['Field', 'Custom', 'Extra']),
      });

    }
  });

})(angular, CRM.$, CRM._);
