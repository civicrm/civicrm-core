(function(angular, $, _) {
  "use strict";

  angular.module('afAdmin').controller('afAdminList', function($scope, afforms, crmApi4, crmStatus) {
    var ts = $scope.ts = CRM.ts(),
      ctrl = $scope.$ctrl = this;

    $scope.crmUrl = CRM.url;

    this.tabs = CRM.afAdmin.afform_type;
    $scope.types = _.indexBy(ctrl.tabs, 'name');
    _.each(['form', 'block', 'search'], function(type) {
      if ($scope.types[type]) {
        $scope.types[type].options = [];
        if (type === 'form') {
          $scope.types.form.default = '#create/form/Individual';
        }
      }
    });

    this.afforms = _.transform(afforms, function(afforms, afform) {
      afform.type = afform.type || 'system';
      afforms[afform.type] = afforms[afform.type] || [];
      afforms[afform.type].push(afform);
    }, {});

    $scope.$bindToRoute({
      expr: '$ctrl.tab',
      param: 'tab',
      format: 'raw',
      default: ctrl.tabs[0].name
    });

    this.createLinks = function() {
      ctrl.searchCreateLinks = '';
      if ($scope.types[ctrl.tab].options.length) {
        return;
      }
      var links = [];

      if (ctrl.tab === 'form') {
        _.each(CRM.afGuiEditor.entities, function(entity, name) {
          if (entity.defaults) {
            links.push({
              url: '#create/form/' + name,
              label: entity.label,
              icon: entity.icon
            });
          }
        });
        $scope.types.form.options = _.sortBy(links, 'Label');
      }

      if (ctrl.tab === 'block') {
        _.each(CRM.afGuiEditor.entities, function(entity, name) {
          if (entity.defaults) {
            links.push({
              url: '#create/block/' + name,
              label: entity.label,
              icon: entity.icon
            });
          }
        });
        $scope.types.block.options = _.sortBy(links, 'Label');
      }

      if (ctrl.tab === 'search') {
        crmApi4('SearchDisplay', 'get', {
          select: ['name', 'label', 'type:icon', 'saved_search.name', 'saved_search.label']
        }).then(function(searchDisplays) {
          _.each(searchDisplays, function(searchDisplay) {
            links.push({
              url: '#create/search/' + searchDisplay['saved_search.name'] + '.' + searchDisplay.name,
              label: searchDisplay['saved_search.label'] + ': ' + searchDisplay.label,
              icon: searchDisplay['type:icon']
            });
          });
          $scope.types.search.options = _.sortBy(links, 'Label');
        });
      }
    };

    this.revert = function(afform) {
      var index = _.findIndex(ctrl.afforms[ctrl.tab], {name: afform.name});
      if (index > -1) {
        var apiOps = [['Afform', 'revert', {where: [['name', '=', afform.name]]}]];
        if (afform.has_base) {
          apiOps.push(['Afform', 'get', {
            where: [['name', '=', afform.name]],
            select: ['name', 'title', 'type', 'is_public', 'server_route', 'has_local', 'has_base']
          }, 0]);
        }
        var apiCall = crmStatus(
          afform.has_base ? {start: ts('Reverting...')} : {start: ts('Deleting...'), success: ts('Deleted')},
          crmApi4(apiOps)
        );
        if (afform.has_base) {
          afform.has_local = false;
          apiCall.then(function(result) {
            ctrl.afforms[ctrl.tab][index] = result[1];
          });
        } else {
          ctrl.afforms[ctrl.tab].splice(index, 1);
        }
      }
    };
  });

})(angular, CRM.$, CRM._);
