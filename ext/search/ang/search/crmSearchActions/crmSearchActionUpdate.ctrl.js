(function(angular, $, _) {
  "use strict";

  angular.module('search').controller('crmSearchActionUpdate', function ($scope, $timeout, crmApi4, dialogService, searchMeta) {
    var ts = $scope.ts = CRM.ts(),
      model = $scope.model,
      ctrl = $scope.$ctrl = this;

    this.entity = searchMeta.getEntity(model.entity);
    this.values = [];
    this.add = null;

    function fieldInUse(fieldName) {
      return _.includes(_.collect(ctrl.values, 0), fieldName);
    }

    this.updateField = function(index) {
      // Debounce the onchange event using timeout
      $timeout(function() {
        if (!ctrl.values[index][0]) {
          ctrl.values.splice(index, 1);
        }
      });
    };

    this.addField = function() {
      // Debounce the onchange event using timeout
      $timeout(function() {
        if (ctrl.add) {
          ctrl.values.push([ctrl.add, '']);
        }
        ctrl.add = null;
      });
    };

    this.availableFields = function() {
      var results = _.transform(ctrl.entity.fields, function(result, item) {
        var formatted = {id: item.name, text: item.title, description: item.description};
        if (fieldInUse(item.name)) {
          formatted.disabled = true;
        }
        if (item.name !== 'id') {
          result.push(formatted);
        }
      }, []);
      return {results: results};
    };

    this.cancel = function() {
      dialogService.cancel('crmSearchAction');
    };

    this.save = function() {
      crmApi4(model.entity, 'Update', {
        where: [['id', 'IN', model.ids]],
        values: _.zipObject(ctrl.values)
      }).then(function() {
        dialogService.close('crmSearchAction');
      });
    };

  });
})(angular, CRM.$, CRM._);
