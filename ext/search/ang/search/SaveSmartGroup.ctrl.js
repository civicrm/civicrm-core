(function(angular, $, _) {
  "use strict";

  angular.module('search').controller('SaveSmartGroup', function ($scope, crmApi4, dialogService) {
    var ts = $scope.ts = CRM.ts(),
      model = $scope.model;
    $scope.groupEntityRefParams = {
      entity: 'Group',
      api: {
        params: {is_hidden: 0, is_active: 1, 'saved_search_id.api_entity': model.entity},
        extra: ['saved_search_id', 'description', 'visibility', 'group_type']
      },
      select: {
        allowClear: true,
        minimumInputLength: 0,
        placeholder: ts('Select existing group')
      }
    };
    if (!CRM.checkPerm('administer reserved groups')) {
      $scope.groupEntityRefParams.api.params.is_reserved = 0;
    }
    $scope.perm = {
      administerReservedGroups: CRM.checkPerm('administer reserved groups')
    };
    $scope.groupFields = _.indexBy(_.find(CRM.vars.search.schema, {name: 'Group'}).fields, 'name');
    $scope.$watch('model.id', function (id) {
      if (id) {
        _.assign(model, $('#api-save-search-select-group').select2('data').extra);
      }
    });
    $scope.cancel = function () {
      dialogService.cancel('saveSearchDialog');
    };
    $scope.save = function () {
      $('.ui-dialog:visible').block();
      var group = model.id ? {id: model.id} : {title: model.title};
      group.description = model.description;
      group.visibility = model.visibility;
      group.group_type = model.group_type;
      group.saved_search_id = '$id';
      var savedSearch = {
        api_entity: model.entity,
        api_params: model.params
      };
      if (group.id) {
        savedSearch.id = model.saved_search_id;
      }
      crmApi4('SavedSearch', 'save', {records: [savedSearch], chain: {group: ['Group', 'save', {'records': [group]}]}})
        .then(function (result) {
          dialogService.close('saveSearchDialog', result[0]);
        });
    };
  });
})(angular, CRM.$, CRM._);
