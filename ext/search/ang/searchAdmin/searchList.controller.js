(function(angular, $, _) {
  "use strict";

  angular.module('searchAdmin').controller('searchList', function($scope, savedSearches) {
    var ts = $scope.ts = CRM.ts(),
      ctrl = $scope.$ctrl = this;
    this.savedSearches = savedSearches;
    this.entityTitles = _.transform(CRM.vars.search.schema, function(titles, entity) {
      titles[entity.name] = entity.titlePlural;
    }, {});
  });

})(angular, CRM.$, CRM._);
