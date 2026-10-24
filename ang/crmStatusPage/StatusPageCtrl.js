(function(angular, $) {

  angular.module('crmStatusPage').controller('crmStatusPageCtrl',
    function($scope, crmApi, crmApi4, crmStatus) {
      $scope.ts = CRM.ts();
      $scope.help = CRM.help;
      $scope.formatDate = CRM.utils.formatDate;
      $scope.$ctrl = this;

      // Refresh the list. Optionally execute api calls first.
      const refresh = (apiCalls, title) => {
        title = title || 'Untitled operation';
        apiCalls = (apiCalls || []).concat([['System', 'check', {
          select: ['*', 'severity_id:name'],
          orderBy: {severity_id: 'DESC'}
        }]]);
        $('#crm-status-list').block();
        crmApi4(apiCalls)
          .then((results) => {
            this.statuses = results[results.length - 1];
            results.forEach((result) => {
              (result.errors ?? []).forEach((error) => {
                CRM.alert(error.message, error.title, 'error');
              });
            });
            $('#crm-status-list').unblock();
          })
          .catch((error) => {
            var error_message = ts(error.error_message || 'Unknown error');
            CRM.alert(error_message, ts('Operation failed: ' + title), 'error');
            $('#crm-status-list').unblock();
          });
      };

      // updates a status preference and refreshes status data
      $scope.setPref = (status, until, visible) => {
        refresh([
          ['StatusPreference', 'save', {
            records: [{
              name: status.name,
              ignore_severity: visible ? 0 : status.severity_id,
              hush_until: until
            }]
          }]
        ], 'Set preference');
      };

      $scope.countVisible = (visibility) => {
        return this.statuses?.filter((s) => s.is_visible === visibility && s.severity_id >= 2)?.length;
      };

      this.doAction = (action) => {
        function run() {
          switch (action.type) {
            case 'href':
              window.location = action.params.url ? action.params.url : CRM.url(action.params.path, action.params.query, action.params.mode);
              break;

            case 'api4':
              refresh([action.params], action.title);
              break;

            case 'api3':
              $('#crm-status-list').block();
              crmApi([action.params], true).then(() => refresh());
              break;
          }
        }

        if (action.confirm) {
          CRM.confirm({
            title: action.title,
            message: action.confirm
          }).on('crmConfirm:yes', run);
        } else {
          run();
        }
      };

      refresh();
    });

})(angular, CRM.$);
