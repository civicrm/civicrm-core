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
  })();
</script>

{/literal}