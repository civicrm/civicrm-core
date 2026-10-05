(function(angular, $) {
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
        $element.on('click', () => {
          const options = CRM.utils.adjustDialogDefaults({
            autoOpen: false,
            title: ($element.attr('title') || $element.text()).trim()
          });
          dialogService.open(this.popupName, this.popupTpl, this.popupData || {}, options)
            .then(() => $element.trigger('crmPopupFormSuccess'));
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
      controller: function($scope, $timeout) {
        this.buttons = [];

        this.close = (result) => {
          dialogService.close(this.name, result);
        };

        this.cancel = () => {
          dialogService.cancel(this.name);
        };

        this.loadButtons = () => {
          const buttons = this.buttons.map((crmDialogButton) => {
            const button = {
              click: () => {
                $scope.$apply(crmDialogButton.onClick);
              }
            };
            ['icons', 'text', 'disabled'].forEach((prop) => {
              if (crmDialogButton[prop] !== undefined) {
                button[prop] = crmDialogButton[prop];
              }
            });
            return button;
          });
          dialogService.setButtons(this.name, buttons);
        };

        $timeout(() => {
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
      this.$onInit = () => {
        this.crmDialog.buttons.push(this);
        $scope.$watch('$ctrl.disabled', this.crmDialog.loadButtons);
        $scope.$watch('$ctrl.text', this.crmDialog.loadButtons);
        $scope.$watch('$ctrl.icons', this.crmDialog.loadButtons);
      };
    }
  });

})(angular, CRM.$);
