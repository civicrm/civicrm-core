(function(angular, $, _) {
  "use strict";

  // Declare module
  angular.module('crmSearchTasks', CRM.angRequires('crmSearchTasks'))

    // Reformat an array of objects for compatibility with select2
    // Todo this probably belongs in core
    .factory('formatForSelect2', function() {
      return function(input, key, label, extra) {
        return _.transform(input, function(result, item) {
          var formatted = {id: item[key], text: item[label]};
          if (extra) {
            _.merge(formatted, _.pick(item, extra));
          }
          result.push(formatted);
        }, []);
      };
    });

})(angular, CRM.$, CRM._);
