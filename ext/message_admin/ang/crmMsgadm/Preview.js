(function(angular, $, _) {

  angular.module('crmMsgadm').controller('MsgtpluiPreviewCtrl', function($scope, crmUiHelp, crmStatus, crmApi4, crmUiAlert, $timeout, $q, dialogService, $location) {
    var ts = $scope.ts = CRM.ts('crmMsgadm');
    var hs = $scope.hs = crmUiHelp({file: 'CRM/MessageAdmin/crmMsgadm'}); // See: templates/CRM/MessageAdmin/crmMsgadm.hlp

    var $ctrl = this, model = $scope.model;
    var args = $location.search();
    if (args.lang) {
      $ctrl.lang = args.lang;
    }

    $ctrl.exampleId = parseInt(_.findKey(model.examples, {name: model.exampleName}));
    $ctrl.revisionId = parseInt(_.findKey(model.revisions, {name: model.revisionName}));
    $ctrl.formatId = parseInt(_.findKey(model.formats, {name: model.formatName}));
    $ctrl.cycle = function(idFld, listFld, delta){
      $ctrl[idFld] = ($ctrl[idFld] + delta + model[listFld].length) % model[listFld].length;
    };

    $ctrl.adhocExample = {};
    $ctrl.isAdhocExample = false;
    $ctrl.toggleAdhoc = function(value){
      $ctrl.isAdhocExample = !$ctrl.isAdhocExample;
      $ctrl.adhocExampleJson = angular.toJson(model.examples[$ctrl.exampleId], 2);
    };

    $ctrl.inspectExample = function() {
      var dlgModel = {
        title: '',
        data: {}
      };
      var dlgOptions = CRM.utils.adjustDialogDefaults({
        dialogClass: 'crm-msgadm-dialog',
        autoOpen: false,
        height: '80%',
        width: '80%'
      });

      dlgModel.refresh = function(){
        return crmApi4('ExampleData', 'get', {
          where: [["name", "=", model.examples[$ctrl.exampleId].name]],
          language: $ctrl.lang,
          select: ['name', 'file', 'title', 'data']
        }).then(function(response){
          dlgModel.title = ts('Example: %1', {1: response[0].title || response[0].name});
          dlgModel.data = response[0];
          if (model.filterData && dlgModel.data.data) {
            dlgModel.data['data(filtered)'] = model.filterData(angular.copy(dlgModel.data.data));
          }
        });
      };

      dlgModel.refresh().then(function(){
        return dialogService.open('inspectExampleDlg', '~/crmMsgadm/InspectExample.html', dlgModel, dlgOptions)
          // Nothing to do but hide warnings.
          .then(forceUpdate, forceUpdate);
      });
    };

    function requestAdhocExample() {
      try {
        return $q.resolve(JSON.parse($ctrl.adhocExampleJson.data));
      }
      catch (err) {
        return $q.reject(ts('Malformed JSON example'));
      }
    }

    function requestStoredExample() {
      return crmApi4('ExampleData', 'get', {
        where: [["name", "=", model.examples[$ctrl.exampleId].name]],
        language: $ctrl.lang,
        select: ['data']
      }).then(function(response) {
        return response[0].data;
      });
    }

    var lastId = null;
    var update = function update() {
      var id = $ctrl.revisionId + ':' + $ctrl.exampleId;
      if (lastId === id) return;
      lastId = id;

      //   $ctrl.preview = model.revisions[$ctrl.revisionId].rec;
      $ctrl.preview = {loading: true};
      var rendering = $ctrl.isAdhocExample ? requestAdhocExample() : requestStoredExample();
      rendering.then(function(exampleData) {
        var filteredData = model.filterData ? model.filterData(exampleData) : exampleData;
        return crmApi4('WorkflowMessage', 'render', {
          language: $ctrl.lang,
          workflow: filteredData.workflow,
          values: filteredData.modelProps,
          messageTemplate: model.revisions[$ctrl.revisionId].rec
        });
      }).then(function(response) {
        $ctrl.preview = response[0];
      }, function(failure) {
        $ctrl.preview = {};
        crmUiAlert({title: ts('Render failed'), text: failure.error_message, type: 'error'});
      });
      return crmStatus({start: ts('Rendering...'), success: ''}, rendering);
    };
    function forceUpdate() {
      lastId = null;
      return update();
    }

    $scope.$watch('$ctrl.revisionId', update);
    $scope.$watch('$ctrl.formatId', update);
    $scope.$watch('$ctrl.exampleId', update);
    update();
  });

})(angular, CRM.$, CRM._);
