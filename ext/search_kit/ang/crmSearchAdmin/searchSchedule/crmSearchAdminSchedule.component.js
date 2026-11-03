(function(angular, $) {
  "use strict";

  angular.module('crmSearchAdmin').component('crmSearchAdminSchedule', {
    bindings: {
      searchDisplayId: '<',
      display: '<',
      savedSearch: '<'
    },
    templateUrl: '~/crmSearchAdmin/searchSchedule/crmSearchAdminSchedule.html',
    controller: function($scope, searchMeta, dialogService, crmApi4, crmStatus) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit');

      this.loading = true;
      this.saving = false;
      this.sending = false;
      this.schedules = [];
      this.scheduleIndex = 0;
      this.schedule = null;

      this.msgTemplateParams = {
        where: [
          ['is_active', '=', true],
          ['is_sms', '=', false],
          ['workflow_name', 'IS NULL']
        ]
      };

      this.formats = [
        {id: 'csv', text: ts('CSV (Comma Separated Values)')},
        {id: 'xlsx', text: ts('Excel (XLSX)')},
        {id: 'ods', text: ts('OpenDocument Spreadsheet (ODS)')},
        {id: 'pdf', text: ts('PDF')}
      ];

      this.frequencyUnits = [
        {id: 'day', text: ts('Day(s)')},
        {id: 'week', text: ts('Week(s)')},
        {id: 'month', text: ts('Month(s)')},
        {id: 'year', text: ts('Year(s)')}
      ];

      this.$onInit = () => {
        crmApi4('SearchSchedule', 'get', {
          where: [['search_display_id', '=', this.searchDisplayId]],
          orderBy: {id: 'ASC'}
        }).then((schedules) => {
          this.schedules = schedules;
          if (this.schedules.length) {
            this.selectSchedule(0);
          } else {
            this.addSchedule();
          }
          this.loading = false;
        }).catch(() => {
          this.loading = false;
        });

        // Ensure field metadata is loaded for filters
        if (this.savedSearch && this.savedSearch.api_entity) {
          const entities = [this.savedSearch.api_entity];
          (this.savedSearch.api_params?.join || []).forEach((joinClause) => {
            const join = searchMeta.getJoin(this.savedSearch, joinClause[0]);
            if (join) {
              entities.push(join.baseEntity || join.entity);
            }
          });
          searchMeta.loadFieldOptions(entities);
        }
      };

      this.selectSchedule = (index) => {
        this.scheduleIndex = index;
        if (index === 'new') {
          return;
        }
        this.schedule = this.schedules[index];
        this.schedule.filters = this.schedule.filters || [];
      };

      this.addSchedule = () => {
        this.schedule = {
          search_display_id: this.searchDisplayId,
          label: (this.display?.label ? this.display.label + ' ' : '') + ts('Schedule'),
          is_active: true,
          recipients: [],
          msg_template_id: null,
          subject: this.display?.label || '',
          format: 'csv',
          frequency_interval: 1,
          frequency_unit: 'week',
          start_date: '',
          filters: []
        };
        this.scheduleIndex = 'new';
      };

      this.formatDate = (dateStr) => {
        return dateStr ? CRM.utils.formatDate(dateStr, null, true) : '';
      };

      this.fieldsForFilters = () => {
        const results = [];
        if (!this.savedSearch || !this.savedSearch.api_entity) {
          return {results};
        }
        const allowedTypes = ['Field', 'Custom', 'Extra', 'Filter'];
        const formatFields = (fields, prefix = '') => {
          return (fields || [])
            .filter((f) => f && allowedTypes.includes(f.type))
            .map((f) => {
              const suffix = (f.suffixes || []).includes('name') ? ':name' : '';
              return {
                id: prefix + f.name + suffix,
                text: f.label,
                description: f.description
              };
            });
        };

        const mainEntity = searchMeta.getEntity(this.savedSearch.api_entity);
        if (mainEntity && mainEntity.fields) {
          results.push({
            text: mainEntity.title_plural,
            icon: mainEntity.icon,
            children: formatFields(mainEntity.fields)
          });
        }
        (this.savedSearch.api_params?.join || []).forEach((joinClause) => {
          const join = searchMeta.getJoin(this.savedSearch, joinClause[0]);
          if (join) {
            const entity = searchMeta.getEntity(join.baseEntity || join.entity);
            if (entity && entity.fields) {
              results.push({
                text: join.label,
                icon: entity.icon,
                children: formatFields(entity.fields, join.alias + '.')
              });
            }
          }
        });
        return {results};
      };

      this.save = () => {
        this.saving = true;
        if (!this.schedule.search_display_id) {
          this.schedule.search_display_id = this.searchDisplayId;
        }
        crmStatus({}, crmApi4('SearchSchedule', 'save', {
          records: [this.schedule]
        }, 0)).then(() => {
          this.saving = false;
          dialogService.close('searchScheduleDialog');
        }).catch(() => {
          this.saving = false;
        });
      };

      this.sendTest = () => {
        if (!this.schedule.id) {
          return;
        }
        this.sending = true;
        crmStatus({start: ts('Sending test email...'), success: ts('Test email sent')}, crmApi4('SearchSchedule', 'sendDue', {
          scheduleId: this.schedule.id,
          force: true
        })).then(() => {
          this.sending = false;
          crmApi4('SearchSchedule', 'get', {
            where: [['id', '=', this.schedule.id]]
          }, 0).then((updated) => {
            Object.assign(this.schedule, updated);
          });
        }).catch(() => {
          this.sending = false;
        });
      };

      this.deleteSchedule = () => {
        if (!this.schedule.id) {
          if (this.schedules.length) {
            this.selectSchedule(0);
          } else {
            dialogService.close('searchScheduleDialog');
          }
          return;
        }
        CRM.confirm({
          title: ts('Delete %1', {1: this.schedule.label}),
          message: ts('Are you sure you want to delete this schedule?')
        }).on('crmConfirm:yes', () => {
          $scope.$apply(() => {
            crmStatus({start: ts('Deleting...'), success: ts('Schedule deleted')}, crmApi4('SearchSchedule', 'delete', {
              where: [['id', '=', this.schedule.id]]
            })).then(() => {
              const idx = this.schedules.findIndex((s) => s.id === this.schedule.id);
              if (idx > -1) {
                this.schedules.splice(idx, 1);
              }
              if (this.schedules.length) {
                this.selectSchedule(0);
              } else {
                this.addSchedule();
              }
            });
          });
        });
      };

    }
  });

})(angular, CRM.$);
