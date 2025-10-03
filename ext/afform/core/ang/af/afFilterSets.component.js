(function(angular, $, _) {
  "use strict";
  angular.module('af').component('afFilterSets', {
    require: {
      afFieldset: '^^'
    },
    templateUrl: '~/af/afFilterSets.html',
    controller: function($scope, $element, crmApi4, $window, $location) {
      const ts = $scope.ts = CRM.ts('org.civicrm.afform');

      // local model of filter set options
      this.options = [];

      this.$onInit = () => {
        this.formName = this.afFieldset.getFormName();
        this.saveDialog.reset();

        this.fetchFilterSets()
      };

      this.fetchFilterSets = () => crmApi4('AfformFilterSet', 'get', {
          select: ['label', 'filters', 'created_by.display_name', 'created_date'],
          where: [['afform_name', '=', this.formName]],
          orderBy: {'label': 'ASC'}
        })
        .then((results) => this.options = results)
        .catch(() => this.options = [{id: '', label: ts('fetching filter sets failed')}])

      this.applyFilterSet = (id) => {
        $window.location.hash = id ? `#?_filterset=${id}` : '';
      }

      this.getSetFilterSet = (value) => {
        if (value !== undefined) {
          this.applyFilterSet(value);
          return '';
        }
        const filterSet = this.getCurrentFilterSet();
        return (filterSet && filterSet.id) ? `${filterSet.id}` : '';
      }

      this.getCurrentFilterSet = () => {
        const hashParams = new URLSearchParams($window.location.hash.slice(1));
        const urlValue = parseInt(hashParams.get('_filterset'));
        return this.options.find((filterSet) => filterSet.id === urlValue);
      }

      this.getCurrentFilters = () => this.afFieldset ? this.afFieldset.getFilterValues() : {};

      this.saveDialog = {
        open: () => $element[0].querySelector('dialog.af-filter-sets-new').showModal(),
        close: () => $element[0].querySelector('dialog.af-filter-sets-new').close(),
        reset: () => {
          this.saveDialog.label = ts('New filter set');
          this.saveDialog.inProgress = false;
        },
        canOpen: () => Object.keys(this.getCurrentFilters()).length,
        canSave: () => !this.saveDialog.inProgress && this.saveDialog.label && Object.keys(this.getCurrentFilters()).length,
        save: () => {
          if (!this.saveDialog.canSave()) {
            return;
          }

          this.saveDialog.inProgress = true;

          const values = {
            afform_name: this.formName,
            filters: this.getCurrentFilters(),
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
      }



    }
  });
})(angular, CRM.$, CRM._);
