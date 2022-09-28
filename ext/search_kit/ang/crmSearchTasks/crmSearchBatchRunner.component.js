(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchTasks').component('crmSearchBatchRunner', {
    bindings: {
      entity: '<',
      action: '@',
      ids: '<',
      params: '<',
      success: '&',
      error: '&'
    },
    templateUrl: '~/crmSearchTasks/crmSearchBatchRunner.html',
    controller: function($scope, $timeout, $interval, crmApi4) {
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this,
        currentBatch = 0,
        totalBatches,
        incrementer;

      this.progress = 0;

      // Number of records to process in each batch
      var BATCH_SIZE = 500,
        // Extimated number of seconds each batch will take (for auto-incrementing the progress bar)
        EST_BATCH_TIME = 5;

      this.$onInit = function() {
        totalBatches = Math.ceil(ctrl.ids.length / BATCH_SIZE);
        runBatch();
      };

      this.$onDestroy = function() {
        stopIncrementer();
      };

      function runBatch() {
        ctrl.first = currentBatch * BATCH_SIZE;
        ctrl.last = (currentBatch + 1) * BATCH_SIZE;
        if (ctrl.last > ctrl.ids.length) {
          ctrl.last = ctrl.ids.length;
        }
        var params = _.cloneDeep(ctrl.params);
        params.where = params.where || [];
        params.where.push(['id', 'IN', ctrl.ids.slice(ctrl.first, ctrl.last)]);
        crmApi4(ctrl.entity, ctrl.action, params).then(
          function(result) {
            stopIncrementer();
            ctrl.progress = Math.floor(100 * ++currentBatch / totalBatches);
            if (ctrl.last >= ctrl.ids.length) {
              $timeout(ctrl.success, 500);
            } else {
              runBatch();
            }
          }, function(error) {
            ctrl.error();
          });
        // Move the bar every second to simulate progress between batches
        incrementer = $interval(function(i) {
          var est = Math.floor(100 * (currentBatch + (i / EST_BATCH_TIME)) / totalBatches);
          ctrl.progress = est > 100 ? 100 : est;
        }, 1000, EST_BATCH_TIME);
      }

      function stopIncrementer() {
        if (angular.isDefined(incrementer)) {
          $interval.cancel(incrementer);
          incrementer = undefined;
        }
      }

    }
  });

})(angular, CRM.$, CRM._);
