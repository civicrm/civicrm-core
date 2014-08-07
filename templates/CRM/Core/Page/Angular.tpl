{literal}
<div ng-app="crmApp">
  <div ng-view></div>
</div>

<script type="text/javascript">
  (function(angular, _) {
    var crmApp = angular.module('crmApp', CRM.angular.modules);
    crmApp.config(['$routeProvider',
      function($routeProvider) {
        $routeProvider.otherwise({
          template: ts('Unknown path')
        });
      }
    ]);
    crmApp.factory('crmApi', function(){
      return function(entity, action, params, message) {
        // JSON serialization in CRM.api3 is not aware of Angular metadata like $$hash
        if (_.isObject(entity)) {
          return CRM.api3(eval('('+angular.toJson(entity)+')'), message);
        } else {
          return CRM.api3(entity, action, eval('('+angular.toJson(params)+')'), message);
        }
      };
    });
  })(angular, CRM._);
</script>

{/literal}