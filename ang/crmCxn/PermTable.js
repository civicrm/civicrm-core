(function(angular, $, _) {

  // This directive formats the data in appMeta.perm as a nice table.
  // example: <div crm-cxn-perm-table="{perm: cxn.app_meta.perm}"></div>
  angular.module('crmCxn').directive('crmCxnPermTable', function crmCxnPermTable() {
    return {
      restrict: 'EA',
      scope: {
        crmCxnPermTable: '='
      },
      templateUrl: '~/crmCxn/PermTable.html',
      link: function(scope, element, attrs) {
        scope.ts = CRM.ts(null);
        scope.hasRequiredFilters = function(api) {
          return !_.isEmpty(api.required);
        };
        scope.isString = function(v) {
          return _.isString(v);
        };
        scope.apiExplorerUrl = CRM.url('civicrm/api');
        scope.$watch('crmCxnPermTable', function(crmCxnPermTable){
          scope.perm = crmCxnPermTable.perm;
        });
      }
    };
  });
})(angular, CRM.$, CRM._);
