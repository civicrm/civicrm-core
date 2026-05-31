(function(angular, $, _) {

  angular.module('crmDashboard').component('crmDashboard', {
    templateUrl: '~/crmDashboard/Dashboard.html',
    controller: function ($scope, $element, crmApi4, crmUiHelp, dialogService, crmStatus) {
      const ts = $scope.ts = CRM.ts();
      this.columns = [[], [], [], []];
      this.inactive = [];
      this.sortableOptions = {
        connectWith: '.crm-dashboard-droppable',
        handle: '.crm-dashlet-header',
        tolerance: 'pointer',
        start: (event, ui) => {
          $('.crm-dashboard-droppable').sortable('refresh');
        }
      };
      $scope.hs = crmUiHelp({file: 'CRM/Contact/Page/Dashboard'});

      this.$onInit = () => {
        // Sort dashlets into columns
        CRM.crmDashboard.dashlets.forEach((dashlet) => {
          if (dashlet['dashboard_contact.is_active']) {
            this.columns[dashlet['dashboard_contact.column_no']].push(dashlet);
          } else {
            this.inactive.push(dashlet);
          }
        });

        $scope.$watchCollection('$ctrl.columns[0]', onChange);
        $scope.$watchCollection('$ctrl.columns[1]', onChange);
        $scope.$watchCollection('$ctrl.columns[2]', onChange);
        $scope.$watchCollection('$ctrl.columns[3]', onChange);

        // Listen for toggle events on the details element
        $element.find('.crm-inactive-dashlet-fieldset').on('toggle', (event) => {
          $scope.$apply(() => {
            this.onToggleInactive(event.target.open);
          });
        });

        const totalActive = this.columns.reduce((sum, col) => sum + col.length, 0);
        if (totalActive === 0) {
          $element.find('.crm-inactive-dashlet-fieldset').prop('open', true);
          this.onToggleInactive(true);
        }
      };

      this.$onDestroy = () => {
        $element.find('.crm-inactive-dashlet-fieldset').off('toggle');
      };

      const save = _.debounce(() => {
        $scope.$apply(() => {
          const toSave = [];
          this.inactive.forEach((dashlet) => {
            if (dashlet['dashboard_contact.id']) {
              toSave.push({
                dashboard_id: dashlet.id,
                id: dashlet['dashboard_contact.id'],
                is_active: false
              });
            }
          });
          this.columns.forEach((dashlets, col) => {
            dashlets.forEach((dashlet, index) => {
              const item = {
                dashboard_id: dashlet.id,
                is_active: true,
                column_no: col,
                weight: index
              };
              if (dashlet['dashboard_contact.id']) {
                item.id = dashlet['dashboard_contact.id'];
              }
              toSave.push(item);
            });
          });
          crmStatus({}, crmApi4('DashboardContact', 'save', {
            records: toSave,
            defaults: {contact_id: 'user_contact_id'}
          }, 'dashboard_id'))
            .then((results) => {
              this.columns.forEach((dashlets) => {
                dashlets.forEach((dashlet) => {
                  dashlet['dashboard_contact.id'] = results[dashlet.id].id;
                });
              });
            });
        });
      }, 2000);

      // Sort inactive dashlets by label. This makes them easier to find if there is a large number.
      const sortInactive = () => {
        this.inactive = this.inactive.slice().sort((a, b) => a.label.localeCompare(b.label));
      };

      // Handle disclosure element toggle
      this.onToggleInactive = (isOpen) => {
        this.showInactive = isOpen;
        if (isOpen) {
          // Ensure inactive dashlets are sorted before showing them
          sortInactive();
        }
      };

      this.filterApplies = (dashlet) => {
        return !this.filterInactive || dashlet.label.toLowerCase().includes(this.filterInactive.toLowerCase());
      };

      this.removeDashlet = (column, index) => {
        this.inactive.push(this.columns[column][index]);
        this.columns[column].splice(index, 1);
        // Place the dashlet back in the correct abc order
        sortInactive();
      };

      this.deleteDashlet = (index) => {
        crmStatus(
          {start: ts('Deleting'), success: ts('Deleted')},
          crmApi4('Dashboard', 'delete', {where: [['id', '=', this.inactive[index].id]]})
        );
        this.inactive.splice(index, 1);
      };

      this.showFullscreen = (dashlet) => {
        this.fullscreenDashlet = dashlet.name;
        const options = CRM.utils.adjustDialogDefaults({
          width: '90%',
          height: '90%',
          autoOpen: false,
          title: dashlet.label
        });
        dialogService.open('fullscreenDashlet', '~/crmDashboard/FullscreenDialog.html', dashlet, options)
          .then(() => {
            this.fullscreenDashlet = null;
          }, () => {
            this.fullscreenDashlet = null;
          });
      };

      const onChange = (newVal, oldVal) => {
        if (oldVal !== newVal) {
          save();
        }
      };

    }
  });

})(angular, CRM.$, CRM._);
