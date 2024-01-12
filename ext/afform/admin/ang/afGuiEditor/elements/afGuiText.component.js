// https://civicrm.org/licensing
(function(angular, $, _) {
  "use strict";

  angular.module('afGuiEditor').component('afGuiText', {
    templateUrl: '~/afGuiEditor/elements/afGuiText.html',
    bindings: {
      node: '=',
      deleteThis: '&'
    },
    require: {
      editor: '^^afGuiEditor',
    },
    controller: function($scope, afGui) {
      var ts = $scope.ts = CRM.ts('org.civicrm.afform_admin'),
        ctrl = this;

      $scope.tags = {
        p: ts('Paragraph'),
        legend: ts('Fieldset Legend'),
        h1: ts('Heading 1'),
        h2: ts('Heading 2'),
        h3: ts('Heading 3'),
        h4: ts('Heading 4'),
        h5: ts('Heading 5'),
        h6: ts('Heading 6')
      };

      $scope.alignments = {
        'text-left': ts('Align left'),
        'text-center': ts('Align center'),
        'text-right': ts('Align right'),
        'text-justify': ts('Justify')
      };

      $scope.getAlign = function() {
        return _.intersection(afGui.splitClass(ctrl.node['class']), _.keys($scope.alignments))[0] || 'text-left';
      };

      $scope.setAlign = function(val) {
        afGui.modifyClasses(ctrl.node, _.keys($scope.alignments), val === 'text-left' ? null : val);
      };

      $scope.styles = _.transform(CRM.afGuiEditor.styles, function(styles, val, key) {
        styles['text-' + key] = val;
      });

      // Getter/setter for ng-model
      $scope.getSetStyle = function(val) {
        if (arguments.length) {
          return afGui.modifyClasses(ctrl.node, _.keys($scope.styles), val === 'text-default' ? null : val);
        }
        return _.intersection(afGui.splitClass(ctrl.node['class']), _.keys($scope.styles))[0] || 'text-default';
      };

    }
  });

})(angular, CRM.$, CRM._);
