(function (angular, $, _) {
  angular.module('md5', CRM.angRequires('md5'));
  angular.module('md5').service('md5', function() {
    return (string) => window.SparkMD5.hash(string);
  });
})(angular, CRM.$, CRM._);
