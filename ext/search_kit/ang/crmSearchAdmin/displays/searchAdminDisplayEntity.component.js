(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('searchAdminDisplayEntity', {
    bindings: {
      display: '<',
      apiEntity: '<',
      apiParams: '<'
    },
    require: {
      parent: '^crmSearchAdminDisplay'
    },
    templateUrl: '~/crmSearchAdmin/displays/searchAdminDisplayEntity.html',
    controller: function($scope, crmApi4) {
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this;

      this.$onInit = function () {
        ctrl.jobFrequency = CRM.crmSearchAdmin.jobFrequency;
        if (!ctrl.display.settings) {
          ctrl.display.settings = {
            sort: ctrl.parent.getDefaultSort()
          };
        }
        if (ctrl.display.id && !ctrl.display._job) {
          crmApi4({
            ref: ['SK_' + ctrl.display.name, 'getRefreshDate', {}, 0],
            job: ['Job', 'get', {where: [['api_entity', '=', 'SK_' + ctrl.display.name,], ['api_action', '=', 'refresh']]}, 0],
          }).then(function(result) {
            ctrl.display._refresh_date = result.ref.refresh_date ? CRM.utils.formatDate(result.ref.refresh_date, null, true) : ts('never');
            if (result.job && result.job.id) {
              ctrl.display._job = result.job;
            } else {
              ctrl.display._job = defaultJobParams();
            }
          });
        }
        if (!ctrl.display.id && !ctrl.display._job) {
          ctrl.display._job = defaultJobParams();
        }
        ctrl.parent.initColumns({label: true});
      };

      function defaultJobParams() {
        return {
          parameters: 'version=4',
          is_active: false,
          run_frequency: 'Hourly',
        };
      }

      $scope.$watch('$ctrl.display.name', function(newVal, oldVal) {
        if (!newVal) {
          newVal = ctrl.display.label;
        }
        if (newVal !== oldVal) {
          ctrl.display.name = _.capitalize(_.camelCase(newVal));
        }
      });

    }
  });

})(angular, CRM.$, CRM._);
