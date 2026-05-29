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

      this.getInitialDisplaySettings = () => {
        const pad = (n) => String(n).padStart(2, '0');
        const d = new Date();
        const startDate = `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:00`;
        return {
          searchDisplay: '',
          filters: {},
          toContactIds: '',
          messageTemplateId: '',
          frequency: '',
          startDate: startDate,
          subject: '',
          fileName: '',
          reportName: '',
          savedSearch: this.crmSearchAdmin.savedSearch.id,
          frequency_custom: '',
        };
      };

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

        // Validates a 5-field cron expression. Returns null if valid, or a human-readable error string.
        this.validateCron = (expr) => {
          if (!expr || !expr.trim()) {
            return ts('Cron expression is required when frequency is "custom".');
          }
          const trimmed = expr.trim();

          // --- Explicitly unsupported forms ---
          if (trimmed.startsWith('@')) {
            return ts('Shorthand aliases (@yearly, @monthly, @weekly, @daily, @hourly, @reboot) are not supported. Use the standard 5-field format.');
          }
          if (/[A-Za-z]/.test(trimmed)) {
            return ts('Named months (JAN, FEB…) and named weekdays (MON, TUE…) are not supported. Use numbers: months 1–12, weekdays 0 (Sun) – 6 (Sat).');
          }
          if (/[LW#]/.test(trimmed)) {
            return ts('Quartz-style extensions (L, W, #) are not supported.');
          }

          const parts = trimmed.split(/\s+/);
          if (parts.length === 6) {
            return ts('6-field cron expressions (with seconds) are not supported. Use the standard 5-field format: minute hour day-of-month month day-of-week.');
          }
          if (parts.length !== 5) {
            return ts('Cron expression must have exactly 5 fields: minute hour day-of-month month day-of-week.');
          }

          // --- Per-field syntax + range validation ---
          const fields = [
            { name: ts('minute'),       min: 0, max: 59 },
            { name: ts('hour'),         min: 0, max: 23 },
            { name: ts('day-of-month'), min: 1, max: 31 },
            { name: ts('month'),        min: 1, max: 12 },
            { name: ts('day-of-week'),  min: 0, max: 6  },
          ];
          for (let i = 0; i < 5; i++) {
            const fieldErr = validateCronField(parts[i], fields[i].min, fields[i].max);
            if (fieldErr) {
              return ts('Invalid %1 field "%2": %3', { 1: fields[i].name, 2: parts[i], 3: fieldErr });
            }
          }
          return null;

          // Inner helper — closure so it can see `ts`
          function validateCronField(field, min, max) {
            for (const part of field.split(',')) {
              let range = part;
              let step = 1;

              if (part.includes('/')) {
                const slashed = part.split('/');
                if (slashed.length !== 2 || !/^\d+$/.test(slashed[1]) || parseInt(slashed[1], 10) < 1) {
                  return ts('invalid step value');
                }
                step = parseInt(slashed[1], 10);
                range = slashed[0];
              }

              let start;
              let end;
              if (range === '*') {
                start = min;
                end = max;
              } else if (range.includes('-')) {
                const dashed = range.split('-');
                if (dashed.length !== 2 || !/^\d+$/.test(dashed[0]) || !/^\d+$/.test(dashed[1])) {
                  return ts('malformed range');
                }
                start = parseInt(dashed[0], 10);
                end = parseInt(dashed[1], 10);
              } else {
                if (!/^\d+$/.test(range)) {
                  return ts('not a number');
                }
                start = end = parseInt(range, 10);
              }

              if (start < min || end > max) {
                return ts('value out of range (must be %1–%2)', { 1: min, 2: max });
              }
              if (start > end) {
                return ts('range start is greater than end');
              }
            }
            return null;
          }
        };

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
