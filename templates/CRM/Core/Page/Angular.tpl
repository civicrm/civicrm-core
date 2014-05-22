{literal}
<div ng-app="crmApp">
  <div ng-view></div>
</div>

<script type="text/javascript">
  (function() {
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
        return CRM.api3(entity, action, eval('('+angular.toJson(params)+')'), message);
      };
    });
  })();
</script>

{/literal}