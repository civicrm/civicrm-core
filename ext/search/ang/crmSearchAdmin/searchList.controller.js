(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').controller('searchList', function($scope, savedSearches, crmApi4) {
    var ts = $scope.ts = CRM.ts(),
      ctrl = $scope.$ctrl = this;
    this.savedSearches = savedSearches;
    this.entityTitles = _.transform(CRM.vars.search.schema, function(titles, entity) {
      titles[entity.name] = entity.title_plural;
    }, {});

    this.searchPath = window.location.href.split('#')[0].replace('civicrm/admin/search', 'civicrm/search');

    this.encode = function(params) {
      return encodeURI(angular.toJson(params));
    };

    this.deleteSearch = function(search) {
      var index = _.findIndex(savedSearches, {id: search.id});
      if (index > -1) {
        crmApi4([
          ['Group', 'delete', {where: [['saved_search_id', '=', search.id]]}],
          ['SavedSearch', 'delete', {where: [['id', '=', search.id]]}]
        ]);
        savedSearches.splice(index, 1);
      }
    };
  });

})(angular, CRM.$, CRM._);
