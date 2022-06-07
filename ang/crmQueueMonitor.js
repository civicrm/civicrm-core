(function (angular, $, _) {
  "use strict";

  console.log('init crmQueueMonitor module');
  angular.module('crmQueueMonitor', CRM.angRequires('crmQueueMonitor'));

  // "crmQueueMonitor" displays the status of a queue
  // Example usage: <div crm-queue-monitor queue="foobar"></div>
  // If "queue" is omitted, then inherit `CRM.vars.crmQueueMonitor.default`.
  angular.module('crmQueueMonitor').component('crmQueueMonitor', {
    // templateUrl: '~/crmQueueMonitor/Monitor.html',
    template: '<div>TODO: Monitor "{{$ctrl.queue}}"</div>',
    bindings: {
      queue: '<'
    },
    controller: function($scope) {
      var ts = $scope.ts = CRM.ts(null),
        ctrl = this;

      console.log('init crmQueueMonitor component for ', ctrl.queue);

      // this.$onInit = function() {
      // };
    }
  });

})(angular, CRM.$, CRM._);
