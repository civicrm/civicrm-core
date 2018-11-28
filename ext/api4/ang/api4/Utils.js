(function(angular, $, _) {

  angular.module('api4').factory('crmApi4', function($q) {
    var crmApi4 = function(entity, action, params, message) {
      // JSON serialization in CRM.api4 is not aware of Angular metadata like $$hash, so use angular.toJson()
      var deferred = $q.defer();
      var p;
      var backend = crmApi4.backend || CRM.api4;
      if (_.isObject(entity)) {
        // eval content is locally generated.
        /*jshint -W061 */
        p = backend(eval('('+angular.toJson(entity)+')'), action);
      } else {
        // eval content is locally generated.
        /*jshint -W061 */
        p = backend(entity, action, eval('('+angular.toJson(params)+')'), message);
      }
      p.then(
        function(result) {
          deferred.resolve(result);
        },
        function(error) {
          deferred.reject(error);
        }
      );
      return deferred.promise;
    };
    crmApi4.backend = null;
    crmApi4.val = function(value) {
      var d = $.Deferred();
      d.resolve(value);
      return d.promise();
    };
    return crmApi4;
  });

})(angular, CRM.$, CRM._);
