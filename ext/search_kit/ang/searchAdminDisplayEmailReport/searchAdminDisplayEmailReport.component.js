(function (angular, $, CRM) {
  "use strict";

  angular.module('searchAdminDisplayEmailReport').component('searchAdminDisplayEmailReport', {
    bindings: {
      display: '<',
      apiEntity: '<',
      apiParams: '<'
    },
    require: {
      parent: '^crmSearchAdminDisplay',
      crmSearchAdmin: '^crmSearchAdmin'
    },
    templateUrl: '~/searchAdminDisplayEmailReport/searchAdminDisplayEmailReport.html',
    controller: function ($scope, searchMeta, crmApi4) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit');

      this.getInitialDisplaySettings = () => ({
        searchDisplay: '',
        filters: {},
        toContactIds: '',
        messageTemplateId: '',
        frequency: '',
        subject: '',
        fileName: '',
        reportName: '',
        savedSearch: this.crmSearchAdmin.savedSearch.id,
        frequency_custom: '',
      });

      this.searchColumns = [];

      this.$onInit = () => {


        this.fieldsForfilter = () => ({
          results: this.crmSearchAdmin.getAllFields('', ['Field', 'Custom', 'Extra']),
        });

        this.changeInputMode = () => {
          if (this.inputMode === 'value') {
            delete this.filter.parent_field;
            this.filter.value = null;
          } else {
            delete this.filter.value;
            this.filter.parent_field = null;
          }
        };

        if (!this.display.settings) {
          this.display.settings = this.getInitialDisplaySettings();
        }

        this.email_report_frequencies = this.getEmailReportFrequencies();

        getSearchDisplays().then((result) => {
          // If search doesn't exist, it can't be used
          if (!result.savedSearch) {
            delete this.column.subsearch.search;
            delete this.column.subsearch.display;
          }
          if (!this.display.settings.savedSearch) {
            this.display.settings.savedSearch = this.crmSearchAdmin.savedSearch.id;
          }
        });

        //this.searchColumns = this.apiParams.select.map((select) => {
        //  const info = searchMeta.parseExpr(select);
        //  const field = (_.findWhere(info.args, {type: 'field'}) || {}).field || {};
        //  let dataType = (info.fn && info.fn.data_type) || field.data_type;
        //  // hack: search kit reports option group columns as
          // "Integer" data type - but for our purposes they
          // shouldn't be used for numeric scales
        //  if (select.includes(':label')) {
        //    dataType = 'Option';
        //  }
        //  return {
        //    type: 'field',
        //    key: info.alias,
        //    dataType: dataType,
        //    label: searchMeta.getDefaultLabel(select),
        //  };
        //});

      };

      this.getEmailReportFrequencies = () => (
        CRM.searchAdminDisplayEmailReport.frequencies || {}
      );

      const getSearchField = (fieldName) => searchMeta.getField(fieldName, this.savedSearch.api_entity);

      const getSearchDisplays = () => {
        this.searchDisplays = null;
        const displayTypes = CRM.crmSearchAdmin.displayTypes.map((displayType) => { if (displayType.id != 'email_report') { return displayType.id; }});
        const apiCalls = crmApi4({
          searchDisplays: ['SearchDisplay', 'get', {
            select: ['name', 'label'],
            where: [
              ['saved_search_id', '=', this.crmSearchAdmin.savedSearch.id],
              // Include all viewable display types
              ['type', 'IN', displayTypes],
            ],
          }],
          savedSearch: ['SavedSearch', 'get', {
            select: ['label', 'api_entity', 'api_params'],
            where: [['id', '=', this.crmSearchAdmin.savedSearch.id]],
          }, 0],
        });
        apiCalls.then((result) => {
          this.searchDisplays = result.searchDisplays;
          this.savedSearch = result.savedSearch;
          // Parse fields
          this.subsearchFields = this.savedSearch.api_params.select.reduce((fields, fieldName) => {
            const field = getSearchField(fieldName);
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

      this.addFilter = (fieldName) => {
        this.display.settings.filters = this.display.settings.filters || [];
        this.display.settings.filters.push({
          field: fieldName,
          operator: null,
          value: null,
        });
      };

      $scope.fieldsForFilter = () => {
      console.log(this);
        return {
          results: this.crmSearchAdmin.getAllFields('', ['Field', 'Custom', 'Extra']),
        };
      };
    }

  });
})(angular, CRM.$, CRM);
