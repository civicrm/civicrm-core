(function(angular, $, _) {

  angular.module('statuspage').controller('statuspageStatusPage',
    function($scope, crmApi, crmStatus, statusData) {
      $scope.ts = CRM.ts();
      $scope.help = CRM.help;
      $scope.formatDate = CRM.utils.formatDate;
      $scope.statuses = statusData.values;

      // Refresh the list. Optionally execute api calls first.
      function refresh(apiCalls) {
        apiCalls = (apiCalls || []).concat([['System', 'check', {sequential: 1}]]);
        $('#crm-status-list').block();
        crmApi(apiCalls, true)
          .then(function(result) {
            $scope.statuses = result[result.length - 1].values;
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
        ]);
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
              window.location = CRM.url(action.params.path, action.params.query, action.params.mode);
              break;

            case 'api3':
              refresh([action.params]);
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
