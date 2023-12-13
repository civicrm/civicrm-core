(function(angular, $, _) {

  angular.module('crmDashboard').component('crmDashboard', {
    templateUrl: '~/crmDashboard/Dashboard.html',
    controller: function ($scope, $element, crmApi4, crmUiHelp, dialogService, crmStatus) {
      var ts = $scope.ts = CRM.ts(),
        ctrl = this;
      this.columns = [[], []];
      this.inactive = [];
      this.contactDashlets = {};
      this.sortableOptions = {
        connectWith: '.crm-dashboard-droppable',
        handle: '.crm-dashlet-header'
      };
      $scope.hs = crmUiHelp({file: 'CRM/Contact/Page/Dashboard'});

      this.$onInit = function() {
        // Sort dashlets into columns
        _.each(CRM.crmDashboard.dashlets, function(dashlet) {
          if (dashlet['dashboard_contact.is_active']) {
            ctrl.columns[dashlet['dashboard_contact.column_no']].push(dashlet);
          } else {
            ctrl.inactive.push(dashlet);
          }
        });

        $scope.$watchCollection('$ctrl.columns[0]', onChange);
        $scope.$watchCollection('$ctrl.columns[1]', onChange);
      };

      var save = _.debounce(function() {
        $scope.$apply(function() {
          var toSave = [];
          _.each(ctrl.inactive, function(dashlet) {
            if (dashlet['dashboard_contact.id']) {
              toSave.push({
                dashboard_id: dashlet.id,
                id: dashlet['dashboard_contact.id'],
                is_active: false
              });
            }
          });
          _.each(ctrl.columns, function(dashlets, col) {
            _.each(dashlets, function(dashlet, index) {
              var item = {
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
            .then(function(results) {
              _.each(ctrl.columns, function(dashlets) {
                _.each(dashlets, function(dashlet) {
                  dashlet['dashboard_contact.id'] = results[dashlet.id].id;
                });
              });
            });
        });
      }, 2000);

      // Sort inactive dashlets by label. This makes them easier to find if there is a large number.
      function sortInactive() {
        ctrl.inactive = _.sortBy(ctrl.inactive, 'label');
      }

      // Show/hide inactive dashlets
      this.toggleInactive = function() {
        // Ensure inactive dashlets are sorted before showing them
        sortInactive();
        ctrl.showInactive = !ctrl.showInactive;
      };

      this.filterApplies = function(dashlet) {
        return !ctrl.filterInactive || _.includes(dashlet.label.toLowerCase(), ctrl.filterInactive.toLowerCase());
      };

      this.removeDashlet = function(column, index) {
        ctrl.inactive.push(ctrl.columns[column][index]);
        ctrl.columns[column].splice(index, 1);
        // Place the dashlet back in the correct abc order
        sortInactive();
      };

      this.deleteDashlet = function(index) {
        crmStatus(
          {start: ts('Deleting'), success: ts('Deleted')},
          crmApi4('Dashboard', 'delete', {where: [['id', '=', ctrl.inactive[index].id]]})
        );
        ctrl.inactive.splice(index, 1);
      };

      this.showFullscreen = function(dashlet) {
        ctrl.fullscreenDashlet = dashlet.name;
        var options = CRM.utils.adjustDialogDefaults({
          width: '90%',
          height: '90%',
          autoOpen: false,
          title: dashlet.label
        });
        dialogService.open('fullscreenDashlet', '~/crmDashboard/FullscreenDialog.html', dashlet, options)
          .then(function() {
            ctrl.fullscreenDashlet = null;
          }, function() {
            ctrl.fullscreenDashlet = null;
          });
      };

      function onChange(newVal, oldVal) {
        if (oldVal !== newVal) {
          save();
        }
      }

    }
  });

})(angular, CRM.$, CRM._);
