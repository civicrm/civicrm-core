(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').controller('searchList', function($scope, savedSearches, crmApi4, searchMeta) {
    var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
      ctrl = $scope.$ctrl = this;
    $scope.formatDate = CRM.utils.formatDate;
    this.savedSearches = savedSearches;
    this.sortField = 'modified_date';
    this.sortDir = true;
    this.afformEnabled = CRM.crmSearchAdmin.afformEnabled;
    this.afformAdminEnabled = CRM.crmSearchAdmin.afformAdminEnabled;

    _.each(savedSearches, function(search) {
      search.entity_title = searchMeta.getEntity(search.api_entity).title_plural;
      search.afform_count = 0;
    });

    this.searchPath = CRM.url('civicrm/search');
    this.afformPath = CRM.url('civicrm/admin/afform');

    this.encode = function(params) {
      return encodeURI(angular.toJson(params));
    };

    // Change sort field/direction when clicking a column header
    this.sortBy = function(col) {
      ctrl.sortDir = ctrl.sortField === col ? !ctrl.sortDir : false;
      ctrl.sortField = col;
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
            var search = _.find(ctrl.savedSearches, {name: searchName[1]});
            if (search) {
              search.afform_count++;
              ctrl.afforms[searchName[1]] = ctrl.afforms[searchName[1]] || [];
              ctrl.afforms[searchName[1]].push({
                title: afform.title,
                name: afform.name,
                // FIXME: This is the view url, currently not exposed to the UI, as BS3 doesn't support submenus.
                url: afform.server_route ? CRM.url(afform.server_route) : null
              });
            }
          }
        });
      });
    };

  });

})(angular, CRM.$, CRM._);
