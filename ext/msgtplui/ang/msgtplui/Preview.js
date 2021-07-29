(function(angular, $, _) {

  angular.module('msgtplui').controller('MsgtpluiPreviewCtrl', function($scope, crmUiHelp, crmStatus, crmApi4, $timeout, $q) {
    var ts = $scope.ts = CRM.ts('msgtplui');
    var hs = $scope.hs = crmUiHelp({file: 'CRM/Msgtplui/msgtplui'}); // See: templates/CRM/Msgtplui/msgtplui.hlp

    var $ctrl = this, model = $scope.model;

    $ctrl.exampleId = parseInt(_.findKey(model.examples, {name: model.exampleName}));
    $ctrl.revisionId = parseInt(_.findKey(model.revisions, {name: model.revisionName}));
    $ctrl.formatId = parseInt(_.findKey(model.formats, {name: model.formatName}));
    $ctrl.cycle = function(idFld, listFld, delta){
      $ctrl[idFld] = ($ctrl[idFld] + delta) % model[listFld].length;
    };

    var lastId = null;
    var update = function update() {
      var id = $ctrl.revisionId + ':' + $ctrl.exampleId;
      if (lastId === id) return;
      lastId = id;

      //   $ctrl.preview = model.revisions[$ctrl.revisionId].rec;
      $ctrl.preview = {loading: true};
      var getting = crmApi4('WorkflowMessageExample', 'get', {
        where: [["name", "=", model.examples[$ctrl.exampleId].name]],
        select: ['workflow', 'data'],
        chain: {
          "render": ["WorkflowMessage", "render", {
            "workflow": "$workflow",
            "values": "$data.modelProps",
            "messageTemplate": model.revisions[$ctrl.revisionId].rec
          }]
        }
      }).then(function(response) {
        $ctrl.preview = response[0].render[0];
      }, function(failure) {
        $ctrl.preview = {};
      });
      return crmStatus({start: ts('Rendering...'), success: ''}, getting);
    };

    $scope.$watch('$ctrl.revisionId', update);
    $scope.$watch('$ctrl.formatId', update);
    $scope.$watch('$ctrl.exampleId', update);
    update();
  });

})(angular, CRM.$, CRM._);
