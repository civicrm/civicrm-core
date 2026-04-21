// https://civicrm.org/licensing
(function (angular, $, _) {
  "use strict";

  angular.module('afGuiEditor').component('afGuiElements', {
    templateUrl: '~/afGuiEditor/afGuiElements.html',
    require: {editor: '^^afGuiEditor'},
    controller: function ($scope, $timeout, afGui, formatForSelect2) {
      const ts = $scope.ts = CRM.ts('org.civicrm.afform_admin');
      const ctrl = this;
      $scope.controls = {
        fieldSearch: '',
      };
      $scope.fieldList = [];
      $scope.fieldTitles = [];
      $scope.blockList = [];
      $scope.blockTitles = [];
      $scope.elementList = [];
      $scope.elementTitles = [];

      this.$onInit = function() {
        this.buildPaletteLists();
      };

      this.buildPaletteLists = () => {
        const search = $scope.controls.fieldSearch ? $scope.controls.fieldSearch.toLowerCase() : null;
        buildFieldList(search);
        buildBlockList(search);
        buildElementList(search);
      };

      const buildFieldList = (search) => {
        $scope.fieldList.length = 0;
        $scope.fieldTitles.length = 0;

        // Fields can only be added to a submission form
        if (this.editor.getFormType() !== 'form') {
          return;
        }

        const extraFormFields = afGui.findRecursive(this.editor.afform.layout['#children'],
          (node) => node['#tag'] === 'af-field' && !node.name,
          (node) => node.defn.name
        );

        function addExtraField(field) {
          const tag = {
            "#tag": "af-field",
            defn: _.cloneDeep(field.extra_defn),
          };
          tag.defn.label = ts('Extra %1', {1: field.label});
          tag.defn.input_type = field.name;

          // Make a unique name not already in use on the form
          const name = field.name.toLowerCase();
          let count = 1;
          while ((name + count) in extraFormFields) {
            ++count;
          }
          tag.defn.name = name + count;

          $scope.fieldList.push(tag);
          $scope.fieldTitles.push(field.label);
        }

        afGui.meta.inputTypes
          .filter((field) => field.extra_defn && (!search || field.label.toLowerCase().includes(search)))
          .forEach(field => addExtraField(field));
      };

      const buildElementList = (search) => {
        $scope.elementList.length = 0;
        $scope.elementTitles.length = 0;
        const formType = this.editor.getFormType();
        Object.entries(afGui.meta.elements).forEach(([name, element]) => {
          if (
            (!element.afform_type || element.afform_type.includes(formType)) &&
            name !== 'fieldset' && // Only shown on afGuiEntity tab
            (!search || name.includes(search) || element.title.toLowerCase().includes(search))) {
            const node = _.cloneDeep(element.element);
            $scope.elementList.push(node);
            $scope.elementTitles.push(element.title);
          }
        });
      };

      function buildBlockList(search) {
        $scope.blockList.length = 0;
        $scope.blockTitles.length = 0;
        Object.entries(afGui.meta.blocks).forEach(([directive, block]) => {
          if ((!search || directive.includes(search) || block.name.toLowerCase().includes(search) || block.title.toLowerCase().includes(search)) &&
            // A block of type "*" applies to everything.
            block.entity_type === '*' && !block.join_entity &&
            // Prevent recursion
            block.name !== ctrl.editor.getAfform().name
          ) {
            const item = {"#tag": directive};
            $scope.blockList.push(item);
            $scope.blockTitles.push(block.title);
          }
        });
      }

      // This gets called from jquery-ui so we have to manually apply changes to scope
      $scope.buildPaletteLists = () => {
        $timeout(() => {
          $scope.$apply(() => {
            ctrl.buildPaletteLists();
          });
        });
      };

      this.addValue = function(fieldName) {
        if (fieldName) {
          if (!ctrl.entity.data) {
            ctrl.entity.data = {};
          }
          ctrl.entity.data[fieldName] = '';
        }
      };
    }
  });

})(angular, CRM.$, CRM._);
