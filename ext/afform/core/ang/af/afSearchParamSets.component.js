(function(angular, $, _) {
  "use strict";
  angular.module('af').component('afSearchParamSets', {
    require: {
      afFieldset: '^^'
    },
    templateUrl: '~/af/afSearchParamSets.html',
    controller: function($scope, $element, crmApi4, $window, $location) {
      const ts = $scope.ts = CRM.ts('org.civicrm.afform');

      // local model of saved records
      this.savedSets = [];

      this.$onInit = () => {
        this.formName = this.afFieldset.getFormName();
        this.saveDialog.reset();

        this.fetchSearchParamSets();
      };

      this.fetchSearchParamSets = () => crmApi4('SearchParamSet', 'get', {
          select: ['label', 'filters', 'columns', 'created_by.display_name', 'created_date'],
          where: [['afform_name', '=', this.formName]],
          orderBy: {'label': 'ASC'}
        })
        .then((results) => this.savedSets = results.map((paramSet) => {
          // format date or time
          const created = new Date(paramSet.created_date);
          const createdDate = created.toLocaleDateString();
          const isToday = createdDate === (new Date()).toLocaleDateString();
          paramSet.created_date_or_time = isToday ? ts('%1 today', {1: created.toLocaleTimeString()}) : createdDate;
          return paramSet;
        }))
        .catch(() => this.savedSets = [{id: '', label: ts('Error fetching Saved Searches')}]);

      this.applySearchParamSet = (id) => $window.location.hash = `#?_s=${id ? id : 0}`;

      this.getSetSearchParamSet = (value) => {
        if (value !== undefined) {
          this.applySearchParamSet(value);
          return '';
        }
        const selected = this.getSelectedParamSet();
        return (selected && selected.id) ? `${selected.id}` : '';
      };

      this.getSelectedParamSet = () => {
        const hashParams = new URLSearchParams($window.location.hash.slice(1));
        const urlValue = parseInt(hashParams.get('_s'));
        return this.savedSets.find((searchParamSet) => searchParamSet.id === urlValue);
      };

      this.getCurrentParams = () => {
        if (!this.afFieldset) {
          return {};
        }
        const params = {};

        params.filters = this.afFieldset.getFilterValues();

        params.columns = {};

        // find column toggle settings for any table search displays
        // note 1: there can be multiple tables inside the same af-fieldset
        // so we store columns keyed by search + display name
        // note 2: this is unpleasant angular, but only reading data so
        // shouldn't cause too many problems
        $element.closest('[af-fieldset]').find('crm-search-display-table').each((i, $table) => {
          const tableCtrl = angular.element($table).controller('crmSearchDisplayTable');
          if (!tableCtrl) {
            return;
          }
          const columns = tableCtrl.getToggledColumns();
          if (!Object.keys(columns).length) {
            return;
          }
          params.columns[tableCtrl.getSearchDisplayKey()] = columns;
        });
        return params;
      };

      this.renderSearchParamSetDescription = (paramSet) => ts('Created %1 by %2', {
        1: paramSet.created_date_or_time,
        2: (paramSet['created_by.display_name'] ? paramSet['created_by.display_name'] : 'UNKNOWN')
      });

      this.renderSearchParamSetDetails = (paramSet) => {
        const rendered = {};

        if (paramSet.filters) {
          const fieldMeta = this.afFieldset.getFieldMeta();

          Object.keys(paramSet.filters).forEach((key) => {
            const defn = fieldMeta[key];
            const rawValue = paramSet.filters[key];

            const formatValue = (v) => {
              // if an array, format items and concat
              if (v && v.map) {
                return v.map(formatValue).join(' OR ');
              }
              if (defn.options && defn.options.length) {
                const selected = defn.options.find((o) => o.id == v);
                if (selected) {
                  return selected.label;
                }
              }
              switch (typeof v) {
                case 'number':
                case 'string':
                  return v;
              }
              return Object.entries(v)
                .filter((e) => e[1])
                .map((e) => `${e[0]} ${e[1]}`)
                .join(' AND ');
            };
            rendered[defn.label] = formatValue(rawValue);
          });
        }

        if (paramSet.columns) {
          const displayKeys = Object.keys(paramSet.columns);
          if (displayKeys.length > 1)
            displayKeys.forEach((displayKey) => {
              // TODO: how to get search label here
              const label = ts('%1 columns', {1: displayKey});
              const columns = Object.values(paramSet.columns[displayKey]).join(', ');
              rendered[label] = columns;
            });
          else if (displayKeys.length === 1) {
            // most of the time there is only one display
            const displayKey = displayKeys[0];
            rendered[ts('Columns')] = Object.values(paramSet.columns[displayKey]).join(', ');
          }
        }

        return rendered;
      };

      this.saveDialog = {
        open: () => $element[0].querySelector('dialog.af-search-param-set-new').showModal(),
        close: () => $element[0].querySelector('dialog.af-search-param-set-new').close(),
        reset: () => {
          this.saveDialog.label = '';
          this.saveDialog.inProgress = false;
        },
        canOpen: () => {
          const params = this.getCurrentParams();
          if (Object.keys(params.filters).length) {
            return true;
          }
          if (Object.keys(params.columns).length) {
            return true;
          }
          return false;
        },
        canSave: () => !this.saveDialog.inProgress && this.saveDialog.label && Object.keys(this.getCurrentParams()).length,
        save: () => {
          if (!this.saveDialog.canSave()) {
            return;
          }

          this.saveDialog.inProgress = true;

          const current = this.getCurrentParams();

          const values = {
            afform_name: this.formName,
            filters: current.filters,
            columns: current.columns,
            label: this.saveDialog.label,
            created_by: CRM.config.cid,
          };

          let newId = null;

          return crmApi4('SearchParamSet', 'create', {
            values: values
          })
          .then((result) => newId = (result && result[0]) ? result[0].id : null)
          .then(() => this.fetchSearchParamSets())
          .then(() => this.applySearchParamSet(newId))
          .then(() => this.saveDialog.close())
          .catch((error) => {
            const errorMessage = (error && error.error_message) ? error.error_message : ts('Unknown error');
            CRM.alert(ts('Error saving filters: %1', {1: errorMessage}));
          })
          .finally(() => this.saveDialog.reset());
        }
      };

      this.manageDialog = {
        open: () => $element[0].querySelector('dialog.af-search-param-sets-manage').showModal(),
        close: () => $element[0].querySelector('dialog.af-search-param-sets-manage').close(),
        deleteItem: (searchParamSet) => {
          crmApi4('SearchParamSet', 'delete', {
            where: [['id', '=', searchParamSet.id]]
          })
          .then(() => this.fetchSearchParamSets());
        },
      };

      this.updateDialog = {
        id: null,
        label: '',
        open: () => {
          const selected = this.getSelectedParamSet();
          this.updateDialog.id = selected.id;
          this.updateDialog.oldLabel = selected.label;
          this.updateDialog.newLabel = selected.label;
          this.updateDialog.valueComparison = this.updateDialog.getValueComparison();
          $element[0].querySelector('dialog.af-search-param-set-update').showModal();
        },
        close: () => $element[0].querySelector('dialog.af-search-param-set-update').close(),
        inProgress: false,
        canOpen: () => {
          const selected = this.getSelectedParamSet();
          if (!selected) {
            return false;
          }
          // if there are changes from saved we can update
          const newParams = this.getCurrentParams();
          if (JSON.stringify(selected.filters) !== JSON.stringify(newParams.filters)) {
            return true;
          }
          if (JSON.stringify(selected.columns) !== JSON.stringify(newParams.columns)) {
            return true;
          }
          return false;
        },
        canUpdate: () => this.updateDialog.id && this.updateDialog.newLabel,
        update: () => {
          if (!this.updateDialog.canUpdate()) {
            return;
          }

          this.updateDialog.inProgress = true;

          const newParams = this.getCurrentParams();

          crmApi4('SearchParamSet', 'update', {
            where: [['id', '=', this.updateDialog.id]],
            values: {
              label: this.updateDialog.newLabel,
              filters: newParams.filters,
              columns: newParams.columns
            }
          })
          .then(() => this.fetchSearchParamSets())
          .then(() => this.updateDialog.inProgress = false)
          .then(() => this.updateDialog.close());
        },
        getValueComparison: () => {
          const current = this.getSelectedParamSet();
          const oldValues = this.renderSearchParamSetDetails(current);
          const newValues = this.renderSearchParamSetDetails(this.getCurrentParams());

          const oldKeys = Object.keys(oldValues);
          const newKeys = Object.keys(newValues);

          const comparison = {};

          // only set in oldValues
          oldKeys.filter((key) => !newKeys.includes(key)).forEach((key) => comparison[key] = [oldValues[key], ts('[none]')]);

          // keys in both
          oldKeys.filter((key) => newKeys.includes(key)).forEach((key) => {
            if (newValues[key] === oldValues[key]) {
              // no change, return single item array
              comparison[key] = [oldValues[key]];
              return;
            }
            comparison[key] = [oldValues[key], newValues[key]];
          });
          // only set in newValues
          newKeys.filter((key) => !oldKeys.includes(key)).forEach((key) => comparison[key] = [ts('[none]'), newValues[key]]);

          return comparison;
        }
      };
    }
  });
})(angular, CRM.$, CRM._);
