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
          select: ['label', 'filters', 'created_by.display_name', 'created_date'],
          where: [['afform_name', '=', this.formName]],
          orderBy: {'label': 'ASC'}
        })
        .then((results) => this.savedSets = results)
        .catch(() => this.savedSets = [{id: '', label: ts('Error fetching Saved Searches')}]);

      this.applySearchParamSet = (id) => $window.location.hash = id ? `#?_s=${id}` : '';

      this.getSetSearchParamSet = (value) => {
        if (value !== undefined) {
          this.applySearchParamSet(value);
          return '';
        }
        const searchParamSet = this.getCurrentSearchParamSet();
        return (searchParamSet && searchParamSet.id) ? `${searchParamSet.id}` : '';
      };

      this.getCurrentSearchParamSet = () => {
        const hashParams = new URLSearchParams($window.location.hash.slice(1));
        const urlValue = parseInt(hashParams.get('_s'));
        return this.savedSets.find((searchParamSet) => searchParamSet.id === urlValue);
      };

      this.getCurrentFilterValues = () => this.afFieldset ? this.afFieldset.getFilterValues() : {};

      this.renderSearchParamSetFilters = (values) => {
        if (!values) {
          return;
        }

        const fieldMeta = this.afFieldset.getFieldMeta();

        const rendered = {};

        Object.keys(values).forEach((key) => {
          const defn = fieldMeta[key];
          const rawValue = values[key];

          const formatValue = (v) => {
            if (defn.options && defn.options.length) {
              const selected = defn.options.find((o) => o.id == v);
              if (selected) {
                return selected.label;
              }
            }
            return v;
          };

          const value = (rawValue && rawValue.map) ? rawValue.map(formatValue).join(', ') : formatValue(rawValue);

          rendered[defn.label] = value;
        });

        return rendered;
      };

      this.saveDialog = {
        open: () => $element[0].querySelector('dialog.af-search-param-set-new').showModal(),
        close: () => $element[0].querySelector('dialog.af-search-param-set-new').close(),
        reset: () => {
          this.saveDialog.label = ts('New search');
          this.saveDialog.inProgress = false;
        },
        canOpen: () => Object.keys(this.getCurrentFilterValues()).length,
        canSave: () => !this.saveDialog.inProgress && this.saveDialog.label && Object.keys(this.getCurrentFilterValues()).length,
        save: () => {
          if (!this.saveDialog.canSave()) {
            return;
          }

          this.saveDialog.inProgress = true;

          const values = {
            afform_name: this.formName,
            filters: this.getCurrentFilterValues(),
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
          const current = this.getCurrentSearchParamSet();
          this.updateDialog.id = current.id;
          this.updateDialog.oldLabel = current.label;
          this.updateDialog.newLabel = current.label;
          this.updateDialog.valueComparison = this.updateDialog.getValueComparison();
          $element[0].querySelector('dialog.af-search-param-set-update').showModal();
        },
        close: () => $element[0].querySelector('dialog.af-search-param-set-update').close(),
        inProgress: false,
        canOpen: () => {
          const current = this.getCurrentSearchParamSet();
          if (!current) {
            return false;
          }
          const newValues = this.getCurrentFilterValues();
          if (!Object.keys(newValues).length) {
            return false;
          }
          // if no changes from saved then nothing to update
          if (JSON.stringify(current.filters) === JSON.stringify(newValues)) {
            return false;
          }
          return true;
        },
        canUpdate: () => this.updateDialog.id && this.updateDialog.newLabel,
        update: () => {
          if (!this.updateDialog.canUpdate()) {
            return;
          }

          this.updateDialog.inProgress = true;

          const newValues = this.getCurrentFilterValues();

          crmApi4('SearchParamSet', 'update', {
            where: [['id', '=', this.updateDialog.id]],
            values: {
              label: this.updateDialog.newLabel,
              filters: newValues
            }
          })
          .then(() => this.fetchSearchParamSets())
          .then(() => this.updateDialog.inProgress = false)
          .then(() => this.updateDialog.close());
        },
        getValueComparison: () => {
          console.log(this.getCurrentSearchParamSet());
          const oldValues = this.renderSearchParamSetFilters(this.getCurrentSearchParamSet().filters);
          const newValues = this.renderSearchParamSetFilters(this.getCurrentFilterValues());

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
