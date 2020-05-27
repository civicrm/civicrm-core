(function(angular, $, _) {

  // This directive formats the data in appMeta as a nice table.
  // example: <div crm-cxn-perm-table="{appMeta: cxn.app_meta}"></div>
  angular.module('crmCxn').directive('crmCxnAdvTable', function crmCxnAdvTable() {
    return {
      restrict: 'EA',
      scope: {
        crmCxnAdvTable: '='
      },
      templateUrl: '~/crmCxn/AdvTable.html',
      link: function(scope, element, attrs) {
        scope.ts = CRM.ts(null);
        scope.$watch('crmCxnAdvTable', function(crmCxnAdvTable){
          scope.appMeta = crmCxnAdvTable.appMeta;
        });
      }
    };
  });
})(angular, CRM.$, CRM._);
