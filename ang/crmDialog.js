(function(angular, $, _) {
  "use strict";

  angular.module('crmDialog', CRM.angRequires('crmDialog'));

  // Convenience binding to automatically launch a dialog when clicking an element
  // Ex: <button type="button" crm-dialog-popup="myDialogName" popup-tpl="~/myExt/MyDialogTpl.html" popup-data="{foo: bar}"
  angular.module('crmDialog').directive('crmDialogPopup', function(dialogService) {
    return {
      restrict: 'A',
      bindToController: {
        popupTpl: '@',
        popupName: '@crmDialogPopup',
        popupData: '<'
      },
      controller: function($scope, $element) {
        var ctrl = this;
        $element.on('click', function() {
          var options = CRM.utils.adjustDialogDefaults({
            autoOpen: false,
            title: _.trim($element.attr('title') || $element.text())
          });
          dialogService.open(ctrl.popupName, ctrl.popupTpl, ctrl.popupData || {}, options)
            .then(function(success) {
              $element.trigger('crmPopupFormSuccess');
            });
        });
      }
    };
  });

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

        $dialog.close = function(result) {
          dialogService.close($dialog.name, result);
        };

        $dialog.cancel = function() {
          dialogService.cancel($dialog.name);
        };

        $dialog.loadButtons = function() {
          var buttons = [];
          angular.forEach($dialog.buttons, function (crmDialogButton) {
            var button = _.pick(crmDialogButton, ['icons', 'text', 'disabled']);
            button.click = function() {
              $scope.$apply(crmDialogButton.onClick);
            };
            buttons.push(button);
          });
          dialogService.setButtons($dialog.name, buttons);
        };

        $timeout(function() {
          $('.ui-dialog:last input:not([disabled]):not([type="submit"]):first').focus();
        });

      },
      link: function(scope, element, attrs, controller) {
        controller.name = attrs.crmDialog;
        scope[attrs.crmDialog] = controller;
      }
    };
  });

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
    controller: function($scope) {
      var $ctrl = this;
      $ctrl.$onInit = function() {
        $ctrl.crmDialog.buttons.push(this);
        $scope.$watch('$ctrl.disabled', $ctrl.crmDialog.loadButtons);
        $scope.$watch('$ctrl.text', $ctrl.crmDialog.loadButtons);
        $scope.$watch('$ctrl.icons', $ctrl.crmDialog.loadButtons);
      };
    }
  });

})(angular, CRM.$, CRM._);
