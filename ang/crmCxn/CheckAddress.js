(function (angular, $, _) {

  angular.module('crmCxn').factory('crmCxnCheckAddr', function($q, $timeout) {
    var TIMEOUT = 6000, CHECK_ADDR = 'https://mycivi.org/check-addr';
    return function(url) {
      var dfr = $q.defer(), result = null;

      function onErr() {
        if (result !== null) return;
        result = {url: url, valid: false};
        dfr.resolve(result);
      }

      $.ajax({
        url: CHECK_ADDR,
        data: {url: url},
        jsonp: "callback",
        dataType: "jsonp"
      }).fail(onErr)
        .done(function(response) {
          if (result !== null) return;
          result = {url: url, valid: response.result};
          dfr.resolve(result);
        }
      );
      // JSONP may not provide errors directly.
      $timeout(onErr, TIMEOUT);

      return dfr.promise;
    };
  });

})(angular, CRM.$, CRM._);
