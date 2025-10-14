(function(angular, $, _) {
  "use strict";
  angular.module('af').component('afFilterSets', {
    require: {
      afFieldset: '^^'
    },
    templateUrl: '~/af/afFilterSets.html',
    controller: function($scope, $element, crmApi4, $window, $location) {
      const ts = $scope.ts = CRM.ts('org.civicrm.afform');

      // local model of filter set savedSets
      this.savedSets = [];

      this.$onInit = () => {
        this.formName = this.afFieldset.getFormName();
        this.saveDialog.reset();

        this.fetchFilterSets();
      };

      this.fetchFilterSets = () => crmApi4('AfformFilterSet', 'get', {
          select: ['label', 'filters', 'created_by.display_name', 'created_date'],
          where: [['afform_name', '=', this.formName]],
          orderBy: {'label': 'ASC'}
        })
        .then((results) => this.savedSets = results)
        .catch(() => this.savedSets = [{id: '', label: ts('fetching filter sets failed')}]);

      this.applyFilterSet = (id) => $window.location.hash = id ? `#?_filterset=${id}` : '';

      this.getSetFilterSet = (value) => {
        if (value !== undefined) {
          this.applyFilterSet(value);
          return '';
        }
        const filterSet = this.getCurrentFilterSet();
        return (filterSet && filterSet.id) ? `${filterSet.id}` : '';
      };

      this.getCurrentFilterSet = () => {
        const hashParams = new URLSearchParams($window.location.hash.slice(1));
        const urlValue = parseInt(hashParams.get('_filterset'));
        return this.savedSets.find((filterSet) => filterSet.id === urlValue);
      };

      this.getCurrentFilterValues = () => this.afFieldset ? this.afFieldset.getFilterValues() : {};

      this.getFieldsetMeta = () => {
        const meta = {};
        const fieldElements = $element[0].closest('[af-fieldset]').querySelectorAll('af-field');
        fieldElements.forEach((field) => {
          const name = field.getAttribute('name');
          const defn = angular.element(field).controller('afField').defn;
          meta[name] = defn;
        });
        return meta;
      };

      this.renderFilterSetValues = (values) => {
        const meta = this.getFieldsetMeta();

        const rendered = {};

        Object.keys(values).forEach((key) => {
          const defn = meta[key];
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
        open: () => $element[0].querySelector('dialog.af-filter-sets-new').showModal(),
        close: () => $element[0].querySelector('dialog.af-filter-sets-new').close(),
        reset: () => {
          this.saveDialog.label = ts('New filter set');
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

          return crmApi4('AfformFilterSet', 'create', {
            values: values
          })
          .then((result) => newId = (result && result[0]) ? result[0].id : null)
          .then(() => this.fetchFilterSets())
          .then(() => this.applyFilterSet(newId))
          .then(() => this.saveDialog.close())
          .catch((error) => {
            const errorMessage = (error && error.error_message) ? error.error_message : ts('Unknown error');
            CRM.alert(ts('Error saving filters: %1', {1: errorMessage}));
          })
          .finally(() => this.saveDialog.reset());
        }
      };

      this.manageDialog = {
        open: () => $element[0].querySelector('dialog.af-filter-sets-manage').showModal(),
        close: () => $element[0].querySelector('dialog.af-filter-sets-manage').close(),
        deleteItem: (filterSet) => {
          crmApi4('AfformFilterSet', 'delete', {
            where: [['id', '=', filterSet.id]]
          })
          .then(() => this.fetchFilterSets());
        },
        itemDescription: (filterSet) => ts('Created %1 by %2', {
          1: filterSet.created_date,
          2: (filterSet['created_by.display_name'] ? filterSet['created_by.display_name'] : 'UNKNOWN')
        }),
      };

      this.updateDialog = {
        id: null,
        label: '',
        open: () => {
          const current = this.getCurrentFilterSet();
          this.updateDialog.id = current.id;
          this.updateDialog.label = current.label;
          $element[0].querySelector('dialog.af-filter-set-update').showModal();
        },
        close: () => $element[0].querySelector('dialog.af-filter-set-update').close(),
        inProgress: false,
        canOpen: () => {
          const saved = this.getCurrentFilterSet();
          if (!saved) {
            return false;
          }
          const currentValues = this.getCurrentFilterValues();
          if (JSON.stringify(saved.filters) === JSON.stringify(currentValues)) {
            return false;
          }
          return true;
        },
        canUpdate: () => this.updateDialog.id && this.updateDialog.label,
        update: () => {
          if (!this.updateDialog.canUpdate()) {
            return;
          }

          this.updateDialog.inProgress = true;

          const newValues = this.getCurrentFilterValues();

          crmApi4('AfformFilterSet', 'update', {
            where: [['id', '=', this.updateDialog.id]],
            values: {
              label: this.updateDialog.label,
              filters: newValues
            }
          })
          .then(() => this.fetchFilterSets())
          .then(() => this.updateDialog.inProgress = false)
          .then(() => this.updateDialog.close());
        }
      };
    }
  });
})(angular, CRM.$, CRM._);
