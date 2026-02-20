(function(angular, $, _) {
  "use strict";

  // Note: We're not using CRM.angRequires here to avoid circular dependencies with search display types. See crmSearchDisplay.ang.php
  angular.module('crmSearchDisplay', ['api4', 'ngSanitize']);

})(angular, CRM.$, CRM._);
