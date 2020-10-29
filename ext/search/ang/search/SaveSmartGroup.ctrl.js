(function(angular, $, _) {
  "use strict";

  angular.module('search').controller('SaveSmartGroup', function ($scope, $element, $timeout, crmApi4, dialogService, searchMeta) {
    var ts = $scope.ts = CRM.ts(),
      model = $scope.model,
      joins = _.pluck((model.api_params.join || []), 0),
      entityCount = {};
    $scope.groupEntityRefParams = {
      entity: 'Group',
      api: {
        params: {is_hidden: 0, is_active: 1, 'saved_search_id.api_entity': model.api_entity},
        extra: ['saved_search_id', 'description', 'visibility', 'group_type']
      },
      select: {
        allowClear: true,
        minimumInputLength: 0,
        placeholder: ts('Select existing group')
      }
    };
    // Find all possible search columns that could serve as contact_id for the smart group
    $scope.columns = _.transform([model.api_entity].concat(joins), function(columns, joinExpr) {
      var joinName = joinExpr.split(' AS '),
        entityName = joinName[0],
        entity = searchMeta.getEntity(entityName),
        prefix = joinName[1] ? joinName[1] + '.' : '';
      _.each(entity.fields, function(field) {
        if ((entityName === 'Contact' && field.name === 'id') || field.fk_entity === 'Contact') {
          columns.push({
            id: prefix + field.name,
            text: entity.title_plural + (entityCount[entityName] ? ' ' + entityCount[entityName] : '') + ': ' + field.label,
            icon: entity.icon
          });
        }
      });
      entityCount[entityName] = 1 + (entityCount[entityName] || 1);
    });

    if (!$scope.columns.length) {
      CRM.alert(ts('Cannot create smart group; search does not include any contacts.'), ts('Error'));
      $timeout(function() {
        dialogService.cancel('saveSearchDialog');
      });
      return;
    }

    // Pick the first applicable column for contact id
    model.api_params.select.unshift(_.intersection(model.api_params.select, _.pluck($scope.columns, 'id'))[0] || $scope.columns[0].id);

    if (!CRM.checkPerm('administer reserved groups')) {
      $scope.groupEntityRefParams.api.params.is_reserved = 0;
    }
    $scope.perm = {
      administerReservedGroups: CRM.checkPerm('administer reserved groups')
    };
    $scope.groupFields = _.indexBy(_.find(CRM.vars.search.schema, {name: 'Group'}).fields, 'name');
    $element.on('change', '#api-save-search-select-group', function() {
      if ($(this).val()) {
        $scope.$apply(function() {
          var group = $('#api-save-search-select-group').select2('data').extra;
          model.saved_search_id = group.saved_search_id;
          model.description = group.description || '';
          model.group_type = group.group_type || [];
          model.visibility = group.visibility;
        });
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
      model.api_params.select = _.unique(model.api_params.select);
      var savedSearch = {
        api_entity: model.api_entity,
        api_params: model.api_params
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
