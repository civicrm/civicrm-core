// https://civicrm.org/licensing
(function(angular, $, _) {
  "use strict";

  var richtextId = 0;

  angular.module('afGuiEditor').component('afGuiMarkup', {
    templateUrl: '~/afGuiEditor/elements/afGuiMarkup.html',
    bindings: {
      node: '=',
      deleteThis: '&'
    },
    require: {
      editor: '^^afGuiEditor',
    },
    controller: function($scope, $sce, $timeout) {
      const ts = $scope.ts = CRM.ts('org.civicrm.afform_admin'),
        ctrl = this;

      this.$onInit = function() {
        // CRM.wysiwyg doesn't work without a dom id
        $scope.id = 'af-markup-editor-' + richtextId++;

        // When creating a new markup container, go straight to edit mode
        $timeout(function() {
          if (ctrl.node['#markup'] === false) {
            $scope.edit();
          }
        });
      };

      $scope.getMarkup = function() {
        return $sce.trustAsHtml(ctrl.node['#markup'] || '');
      };

      $scope.edit = function() {
        $('#afGuiEditor').addClass('af-gui-editing-content');
        $scope.editingMarkup = true;
        CRM.wysiwyg.create('#' + $scope.id);
        CRM.wysiwyg.setVal('#' + $scope.id, ctrl.node['#markup'] || '<p></p>');
      };

      $scope.save = function() {
        ctrl.node['#markup'] = CRM.wysiwyg.getVal('#' + $scope.id);
        $scope.close();
      };

      $scope.close = function() {
        CRM.wysiwyg.destroy('#' + $scope.id);
        $('#afGuiEditor').removeClass('af-gui-editing-content');
        // If a newly-added wysiwyg was canceled, just remove it
        if (ctrl.node['#markup'] === false) {
          $scope.container.removeElement(ctrl.node);
        } else {
          $scope.editingMarkup = false;
        }
      };
    }
  });

})(angular, CRM.$, CRM._);
