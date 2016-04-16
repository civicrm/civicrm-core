(function(angular, $, _) {

  // Controller for the "Save Message Template" dialog
  // Scope members:
  //   - [input] "model": Object
  //     - "selected_id": int
  //     - "tpl": Object
  //       - "msg_subject": string
  //       - "msg_text": string
  //       - "msg_html": string
  angular.module('crmMailing').controller('SaveMsgTemplateDialogCtrl', function SaveMsgTemplateDialogCtrl($scope, crmMsgTemplates, dialogService) {
    var ts = $scope.ts = CRM.ts(null);
    $scope.saveOpt = {mode: '', newTitle: ''};
    $scope.selected = null;

    $scope.save = function save() {
      var tpl = _.extend({}, $scope.model.tpl);
      switch ($scope.saveOpt.mode) {
        case 'add':
          tpl.msg_title = $scope.saveOpt.newTitle;
          break;
        case 'update':
          tpl.id = $scope.selected.id;
          tpl.msg_title = $scope.selected.msg_title;
          break;
        default:
          throw 'SaveMsgTemplateDialogCtrl: Unrecognized mode: ' + $scope.saveOpt.mode;
      }
      return crmMsgTemplates.save(tpl)
        .then(function (item) {
          CRM.status(ts('Saved'));
          return item;
        });
    };

    function scopeApply(f) {
      return function () {
        var args = arguments;
        $scope.$apply(function () {
          f.apply(args);
        });
      };
    }

    function init() {
      crmMsgTemplates.get($scope.model.selected_id).then(
        function (tpl) {
          $scope.saveOpt.mode = 'update';
          $scope.selected = tpl;
        },
        function () {
          $scope.saveOpt.mode = 'add';
          $scope.selected = null;
        }
      );
      // When using dialogService with a button bar, the major button actions
      // need to be registered with the dialog widget (and not embedded in
      // the body of the dialog).
      var buttons = [
        {
          text: ts('Save'),
          icons: {primary: 'fa-check'},
          click: function () {
            $scope.save().then(function (item) {
              dialogService.close('saveTemplateDialog', item);
            });
          }
        },
        {
          text: ts('Cancel'),
          icons: {primary: 'fa-times'},
          click: function () {
            dialogService.cancel('saveTemplateDialog');
          }
        }
      ];
      dialogService.setButtons('saveTemplateDialog', buttons);
    }

    setTimeout(scopeApply(init), 0);
  });

})(angular, CRM.$, CRM._);
