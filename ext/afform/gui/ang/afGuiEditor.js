(function(angular, $, _) {
  angular.module('afGuiEditor', CRM.angRequires('afGuiEditor'));

  angular.module('afGuiEditor').directive('afGuiEditor', function(crmApi4) {
    return {
      restrict: 'A',
      //require: 'ngModel',
      templateUrl: '~/afGuiEditor/main.html',
      scope: {
        afGuiEditor: '='
      },
      link: function($scope, $el, $attr) {
        $scope.ts = CRM.ts();
        $scope.afform = null;
        $scope.selectedEntity = null;
        $scope.meta = CRM.afformAdminData;
        var newForm = {
          title: ts('Untitled Form'),
          layout: {
            '#tag': 'af-form',
            ctrl: 'modelListCtrl',
            '#children': [
              {
                '#tag': 'af-entity',
                type: 'Contact',
                data: {
                  contact_type: 'Individual'
                },
                name: 'Contact1',
                label: 'Contact 1',
                'url-autofill': '1',
                autofill: 'user'
              }
            ]
          }
        };
        if ($scope.afGuiEditor.name) {
          crmApi4('Afform', 'get', {where: [['name', '=', $scope.afGuiEditor.name]]}, 0)
            .then(initialize);
        }
        else {
          initialize(newForm);
        }

        function initialize(afform) {
          // Todo - show error msg if form is not found
          $scope.afform = afform;
          $scope.entities = getTags(afform.layout, 'af-entity', 'name');
        }

        $scope.addEntity = function(entityType) {
          var existingEntitiesofThisType = _.map(_.filter($scope.entities, {type: entityType}), 'name'),
            num = existingEntitiesofThisType.length + 1;
          // Give this new entity a unique name
          while (_.contains(existingEntitiesofThisType, entityType + num)) {
            num++;
          }
          $scope.entities[entityType + num] = {
            '#tag': 'af-entity',
            type: entityType,
            name: entityType + num,
            label: entityType + ' ' + num
          };
          $scope.afform.layout['#children'].push($scope.entities[entityType + num]);
          $scope.selectEntity(entityType + num);
        };

        $scope.removeEntity = function(entityName) {
          delete $scope.entities[entityName];
          $scope.afform.layout['#children'].splice(_.findIndex($scope.afform.layout['#children'], {'#tag': 'af-entity', name: entityName}), 1);
          $scope.selectEntity(null);
        };

        $scope.selectEntity = function(entityName) {
          $scope.selectedEntity = entityName;
        };

      }
    };
  });

  function getTags(collection, tagName, indexBy) {
    var items = [];
    _.each(collection, function(item) {
      if (item && typeof item === 'object') {
        if (item['#tag'] === tagName) {
          items.push(item);
        }
        var childTags = item['#children'] ? getTags(item['#children'], tagName) : [];
        if (childTags.length) {
          Array.prototype.push.apply(items, childTags);
        }
      }
    });
    return indexBy ? _.indexBy(items, indexBy) : items;
  }

})(angular, CRM.$, CRM._);
