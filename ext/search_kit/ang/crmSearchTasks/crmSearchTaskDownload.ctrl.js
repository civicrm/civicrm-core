(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchTasks').controller('crmSearchTaskDownload', function($scope, $http, searchTaskBaseTrait, $timeout, $interval) {
    var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
      // Combine this controller with model properties (ids, entity, entityInfo) and searchTaskBaseTrait
      ctrl = angular.extend(this, $scope.model, searchTaskBaseTrait);

    this.entityTitle = this.getEntityTitle();
    this.format = 'csv';
    this.progress = null;

    this.download = function() {
      ctrl.progress = 0;
      // Hide dialog-titlebar buttons so the user doesn't close the dialog
      $('div.ui-dialog').last().find('.ui-dialog-titlebar .ui-button').hide();
      // Show the user something is happening (even though it doesn't accurately reflect progress)
      var incrementer = $interval(function() {
        if (ctrl.progress < 90) {
          ctrl.progress += 10;
        }
      }, 1000);
      var apiParams = ctrl.taskManager.getApiParams();
      delete apiParams.return;
      delete apiParams.limit;
      apiParams.filters.id = ctrl.ids || null;
      apiParams.format = ctrl.format;
      // Use AJAX to fetch file with arrayBuffer
      var httpConfig = {
        responseType: 'arraybuffer',
        headers: {'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded'}
      };
      $http.post(CRM.url('civicrm/ajax/api4/SearchDisplay/download'), $.param({
        params: JSON.stringify(apiParams)
      }), httpConfig)
        .then(function(response) {
          $interval.cancel(incrementer);
          ctrl.progress = 100;
          // Convert arrayBuffer response to blob
          var blob = new Blob([response.data], {
            type: response.headers('Content-Type')
          }),
            a = document.createElement("a"),
            url = a.href = window.URL.createObjectURL(blob),
            fileName = getFileNameFromHeader(response.headers('Content-Disposition'));
          a.download = fileName;
          // Trigger file download
          a.click();
          // Free browser memory
          window.URL.revokeObjectURL(url);
          $timeout(function() {
            CRM.alert(ts('%1 has been downloaded to your computer.', {1: fileName}), ts('Download Complete'), 'success');
            // This action does not update data so don't trigger a refresh
            ctrl.cancel();
          }, 1000);
        });
    };

    // Parse and decode fileName from Content-Disposition header
    function getFileNameFromHeader(contentDisposition) {
      var utf8FilenameRegex = /filename\*=utf-8''([\w%\-\.]+)(?:; ?|$)/i,
        asciiFilenameRegex = /filename=(["']?)(.*?[^\\])\1(?:; ?|$)/;

      if (contentDisposition && contentDisposition.length) {
        if (utf8FilenameRegex.test(contentDisposition)) {
          return decodeURIComponent(utf8FilenameRegex.exec(contentDisposition)[1]);
        } else {
          var matches = asciiFilenameRegex.exec(contentDisposition);
          if (matches != null && matches[2]) {
            return matches[2];
          }
        }
      }
      // Fallback in case header could not be parsed
      return ctrl.entityTitle + '.' + ctrl.format;
    }

  });
})(angular, CRM.$, CRM._);
