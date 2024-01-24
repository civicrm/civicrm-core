(function(angular, $, _) {

  angular.module('crmStatusPage').controller('crmStatusPageCtrl',
    function($scope, crmApi, crmStatus, statusData) {
      $scope.ts = CRM.ts();
      $scope.help = CRM.help;
      $scope.formatDate = CRM.utils.formatDate;
      $scope.statuses = statusData.values;

      // Refresh the list. Optionally execute api calls first.
      function refresh(apiCalls, title) {
        title = title || 'Untitled operation';
        apiCalls = (apiCalls || []).concat([['System', 'check', {sequential: 1, options: {limit: 0, sort: 'severity_id DESC'}}]]);
        $('#crm-status-list').block();
        crmApi(apiCalls, true)
          .then(function(results) {
            $scope.statuses = results[results.length - 1].values;
            results.forEach(function(result) {
              if (result.is_error) {
                var error_message = ts(result.error_message);
                if (typeof(result.debug_information) !== 'undefined') {
                  error_message += '<div class="status-debug-information">' +
                      '<b>' + ts('Debug information') + ':</b><br>' +
                      result.debug_information + '</div>';
                }
                CRM.alert(error_message, ts('Operation failed: ' + title), 'error');
                }
              });
            $('#crm-status-list').unblock();
          });
      }

      // updates a status preference and refreshes status data
      $scope.setPref = function(status, until, visible) {
        refresh([
          ['StatusPreference', 'create', {
            name: status.name,
            ignore_severity: visible ? 0 : status.severity,
            hush_until: until
          }]
        ], 'Set preference');
      };

      $scope.countVisible = function(visibility) {
        return _.filter($scope.statuses, function(s) {
          return s.is_visible == visibility && s.severity_id >= 2;
        }).length;
      };

      $scope.doAction = function(action) {
        function run() {
          switch (action.type) {
            case 'href':
              window.location = action.params.url ? action.params.url : CRM.url(action.params.path, action.params.query, action.params.mode);
              break;

            case 'api3':
              refresh([action.params], action.title);
              break;

            case 'api4':
              $('#crm-status-list').block();
              CRM.api4([action.params]).then(() => refresh());
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
    });

})(angular, CRM.$, CRM._);
