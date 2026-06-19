(function(angular, $, _) {

  angular.module('crmDashboard').component('crmDashboard', {
    templateUrl: '~/crmDashboard/Dashboard.html',
    controller: function ($scope, $element, $timeout, crmApi4, crmUiHelp, dialogService, crmStatus) {
      const ts = $scope.ts = CRM.ts();
      this.columns = [[], [], [], []];
      this.sortableOptions = {
        connectWith: '.crm-dashboard-droppable',
        handle: '.crm-dashlet-header',
        tolerance: 'pointer',
        start: (event, ui) => {
          $('#civicrm-dashboard').addClass('crm-dashboard-dragging');
          $('.crm-dashboard-droppable').sortable('refresh');
        },
        stop: (event, ui) => {
          $('#civicrm-dashboard').removeClass('crm-dashboard-dragging');
        }
      };
      $scope.hs = crmUiHelp({file: 'CRM/Contact/Page/Dashboard'});

      this.$onInit = () => {
        // Load active dashlets from cache first for instant initial rendering
        const cachedDashlets = CRM.cache.get('dashboardDashlets');

        // Helper to populate active dashlet columns
        const loadActive = (activeDashlets) => {
          this.columns = [[], [], [], []];
          activeDashlets.forEach((dashlet) => {
            this.columns[dashlet['dashboard_contact.column_no']].push(dashlet);
          });
        };

        // Prepare API call in background to fetch updated layout and inactive list
        const apiCall = {
          initialize: ['DashboardContact', 'initialize', {}],
          dashlets: ['Dashboard', 'get', {
            select: ['*', 'dashboard_contact.id', 'dashboard_contact.contact_id', 'dashboard_contact.weight', 'dashboard_contact.column_no', 'dashboard_contact.is_active'],
            where: [
              ['domain_id', '=', 'current_domain'],
              ['is_active', '=', true]
            ],
            join: [
              ['DashboardContact AS dashboard_contact', 'LEFT', ['id', '=', 'dashboard_contact.dashboard_id'], ['dashboard_contact.contact_id', '=', '"user_contact_id"']]
            ],
            orderBy: {
              'dashboard_contact.weight': 'ASC'
            }
          }]
        };

        // Handle background API results and sync with cache/UI
        const handleResults = (apiResults) => {
          const activeFromApi = apiResults.dashlets.filter(d => d['dashboard_contact.is_active']);
          const cachedActive = CRM.cache.get('dashboardDashlets');

          if (this.isUserModified) {
            // User interacted in the meantime. Do not overwrite layout.
            // Populate inactive list based on current active columns.
            const currentActiveIds = new Set([].concat(...this.columns).map(d => d.id));
            const inactiveFromApi = apiResults.dashlets.filter(d => !currentActiveIds.has(d.id));
            inactiveFromApi.forEach(d => d['dashboard_contact.is_active'] = false);
            this.inactive = inactiveFromApi;
            this.pendingRemovals = [];
          }
          // No user interaction. Server data is canonical.
          else {
            // Use angular.toJson to ignore angular-specific fields (e.g. $$hashKey) during comparison
            if (!cachedActive || angular.toJson(activeFromApi) !== angular.toJson(cachedActive)) {
              this.isLoadingServerState = true;
              loadActive(activeFromApi);
              CRM.cache.set('dashboardDashlets', activeFromApi);
              this.inactive = apiResults.dashlets.filter(d => !d['dashboard_contact.is_active']);
              $scope.$evalAsync(() => {
                this.isLoadingServerState = false;
              });
            } else {
              // Cache matched. Populate inactive list safely without touching columns.
              this.inactive = apiResults.dashlets.filter(d => !d['dashboard_contact.is_active']);
            }
          }

          if (this.showInactive) {
            sortInactive();
          }

          // Open the inactive dashlets drawer automatically if there are no active dashlets
          const totalActive = this.columns.reduce((sum, col) => sum + col.length, 0);
          if (totalActive === 0) {
            $element.find('.crm-inactive-dashlet-fieldset').prop('open', true);
            this.onToggleInactive(true);
          }
        };

        // Fetch all dashlets in the background.
        if (cachedDashlets) {
          loadActive(cachedDashlets);
          // Cache is present so fetching dashlets is lower priority than rendering the dashboard – wait a sec.
          $timeout(() => crmApi4(apiCall).then(handleResults), 1000);
        } else {
          crmStatus({start: ts('Loading...'), success: ''}, crmApi4(apiCall).then(handleResults));
        }

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

        // Prevent details from opening if inactive data is still loading (spinner is active)
        $element.find('.crm-inactive-dashlet-fieldset summary').on('click', (event) => {
          if (!this.inactive) {
            event.preventDefault();
          }
        });
      };

      this.$onDestroy = () => {
        $element.find('.crm-inactive-dashlet-fieldset').off('toggle');
        $element.find('.crm-inactive-dashlet-fieldset summary').off('click');
      };

      const save = _.debounce(() => {
        $scope.$apply(() => {
          const toSave = [];
          // Include both resolved inactive dashlets and pending removals in deactivation
          const inactiveList = (this.inactive || []).concat(this.pendingRemovals || []);
          inactiveList.forEach((dashlet) => {
            dashlet['dashboard_contact.is_active'] = false;
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
              dashlet['dashboard_contact.is_active'] = true;
              dashlet['dashboard_contact.column_no'] = col;
              dashlet['dashboard_contact.weight'] = index;

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
              // Cache only the active dashlets
              const activeDashlets = [].concat(...this.columns);
              CRM.cache.set('dashboardDashlets', activeDashlets);
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
        this.isUserModified = true;
        const dashlet = this.columns[column][index];
        // If the background API has already loaded, push the removed dashlet to the inactive list
        if (this.inactive) {
          this.inactive.push(dashlet);
          // Place the dashlet back in the correct abc order
          sortInactive();
        } else {
          // If this.inactive is still undefined (data loading), track it in pendingRemovals
          // so it gets saved to the server and merged when the API resolves.
          this.pendingRemovals = this.pendingRemovals || [];
          this.pendingRemovals.push(dashlet);
        }
        // Remove it from the active columns
        this.columns[column].splice(index, 1);
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
        if (!angular.equals(oldVal, newVal)) {
          if (this.isLoadingServerState) {
            return;
          }
          this.isUserModified = true;
          save();
        }
      };

    }
  });

})(angular, CRM.$, CRM._);
