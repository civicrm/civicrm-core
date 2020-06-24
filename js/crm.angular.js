(function (angular, $, _) {
  // DEPRECATED: A variant of angular.module() which uses a dependency list provided by the server.
  // REMOVE circa v4.7.22.
  angular.crmDepends = function crmDepends(name) {
    return angular.module(name, CRM.angRequires(name));
  };
})(angular, CRM.$, CRM._);
