(function(angular, $, _) {
  "use strict";

  angular.module('crmDialog', CRM.angRequires('crmDialog'));

  // Ex: <div crm-dialog="myDialogName"> ... <button ng-click="$dialog.cancel()">Cancel</button> ... </div>
  // Ex: <div crm-dialog="myDialogName"> ... <button ng-click="$dialog.close(outputData)">Close</button> ... </div>
  // Ex: <div crm-dialog="myDialogName"> ... <crm-dialog-button text="'Close'" on-click="$dialog.close()" /> ... </div>
  angular.module('crmDialog').directive('crmDialog', function(dialogService) {
    return {
      restrict: 'A',
      controllerAs: '$dialog',
      controller: function($scope, $parse, $timeout) {
        var $dialog = this;
        $dialog.buttons = [];

        $dialog.close = function (result) {
          dialogService.close($dialog.name, result);
        };

        $dialog.cancel = function (result) {
          dialogService.cancel($dialog.name);
        };

        $dialog.loadButtons = function() {
          var buttons = [];
          angular.forEach($dialog.buttons, function (crmDialogButton) {
            var button = _.pick(crmDialogButton, ['id', 'icons', 'text']);
            button.click = function () {
              crmDialogButton.onClick();
            };
            buttons.push(button);
          });
          dialogService.setButtons($dialog.name, buttons);
          $dialog.toggleButtons();
        };

        $dialog.toggleButtons = function() {
          angular.forEach($dialog.buttons, function (crmDialogButton) {
            $('#' + crmDialogButton.id).prop('disabled', crmDialogButton.disabled);
          });
        };

        $timeout(function(){
          $dialog.loadButtons();
          $('.ui-dialog:last input:not([disabled]):not([type="submit"]):first').focus();
        });

      },
      link: function(scope, element, attrs, controller) {
        controller.name = attrs.crmDialog;
        scope[attrs.crmDialog] = controller;
      }
    };
  });

  var idNum = 1;

  // Ex: <crm-dialog-button text="ts('Do it')" icons="{primary: 'fa-foo'}" on-click="doIt()" />
  angular.module('crmDialog').component('crmDialogButton', {
    bindings: {
      disabled: '<',
      icons: '<',
      text: '<',
      onClick: '&'
    },
    require: {
      crmDialog: '?^^crmDialog'
    },
    controller: function($scope, $element, dialogService, $timeout) {
      var ts = $scope.ts = CRM.ts('crmDialog'), $ctrl = this;
      $ctrl.$onInit = function() {
        $ctrl.crmDialog.buttons.push(this);
      };
      $ctrl.id = 'crmDialogButton_' + (idNum++);

      $scope.$watch('$ctrl.disabled', function(){
        $ctrl.crmDialog.toggleButtons();
      });
    }
  });

})(angular, CRM.$, CRM._);
