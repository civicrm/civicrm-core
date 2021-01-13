(function(angular, $, _) {
  "use strict";

  angular.module('afAdmin').controller('afAdminList', function($scope, afforms, crmApi4, crmStatus) {
    var ts = $scope.ts = CRM.ts(),
      ctrl = $scope.$ctrl = this;

    $scope.crmUrl = CRM.url;

    this.tabs = CRM.afAdmin.afform_type;

    this.afforms = _.transform(afforms, function(afforms, afform) {
      var type = afform.type || 'system';
      afforms[type] = afforms[type] || [];
      afforms[type].push(afform);
    }, {});

    $scope.$bindToRoute({
      expr: '$ctrl.tab',
      param: 'tab',
      format: 'raw',
      default: ctrl.tabs[0].name
    });

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
