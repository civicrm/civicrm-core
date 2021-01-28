(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').controller('searchList', function($scope, savedSearches, crmApi4) {
    var ts = $scope.ts = CRM.ts(),
      ctrl = $scope.$ctrl = this;
    this.savedSearches = savedSearches;
    this.afformEnabled = CRM.crmSearchAdmin.afformEnabled;
    this.afformAdminEnabled = CRM.crmSearchAdmin.afformAdminEnabled;

    this.entityTitles = _.transform(CRM.crmSearchAdmin.schema, function(titles, entity) {
      titles[entity.name] = entity.title_plural;
    }, {});

    this.searchPath = CRM.url('civicrm/search');
    this.newFormPath = CRM.url('civicrm/admin/afform');

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

    this.loadAfforms = function() {
      if (ctrl.afforms || ctrl.afforms === null) {
        return;
      }
      ctrl.afforms = null;
      crmApi4('Afform', 'get', {
        select: ['layout', 'name', 'title', 'server_route'],
        where: [['type', '=', 'search']],
        layoutFormat: 'html'
      }).then(function(afforms) {
        ctrl.afforms = {};
        _.each(afforms, function(afform) {
          var searchName = afform.layout.match(/<crm-search-display-[^>]+search-name[ ]*=[ ]*['"]([^"']+)/);
          if (searchName) {
            ctrl.afforms[searchName[1]] = ctrl.afforms[searchName[1]] || [];
            ctrl.afforms[searchName[1]].push({
              title: afform.title,
              url: afform.server_route ? CRM.url(afform.server_route) : null
            });
          }
        });
      });
    };

  });

})(angular, CRM.$, CRM._);
