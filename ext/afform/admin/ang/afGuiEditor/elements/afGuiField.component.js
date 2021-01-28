// https://civicrm.org/licensing
(function(angular, $, _) {
  "use strict";

  angular.module('afGuiEditor').component('afGuiField', {
    templateUrl: '~/afGuiEditor/elements/afGuiField.html',
    bindings: {
      node: '=',
      deleteThis: '&'
    },
    require: {
      editor: '^^afGuiEditor',
      container: '^^afGuiContainer'
    },
    controller: function($scope, afGui) {
      var ts = $scope.ts = CRM.ts(),
        ctrl = this;

      $scope.editingOptions = false;
      var yesNo = [
        {id: '1', label: ts('Yes')},
        {id: '0', label: ts('No')}
      ];

      this.$onInit = function() {
        $scope.meta = afGui.meta;
      };

      // $scope.getEntity = function() {
      //   return ctrl.editor ? ctrl.editor.getEntity(ctrl.container.getEntityName()) : {};
      // };

      // Returns the original field definition from metadata
      this.getDefn = function() {
        return ctrl.editor ? afGui.getField(ctrl.container.getFieldEntityType(ctrl.node.name), ctrl.node.name) : {};
      };

      $scope.getOriginalLabel = function() {
        if (ctrl.container.getEntityName()) {
          return ctrl.editor.getEntity(ctrl.container.getEntityName()).label + ': ' + ctrl.getDefn().label;
        }
        return afGui.getEntity(ctrl.container.getFieldEntityType(ctrl.node.name)).label + ': ' + ctrl.getDefn().label;
      };

      $scope.hasOptions = function() {
        var inputType = $scope.getProp('input_type');
        return _.contains(['CheckBox', 'Radio', 'Select'], inputType) && !(inputType === 'CheckBox' && !ctrl.getDefn().options);
      };

      $scope.getOptions = this.getOptions = function() {
        if (ctrl.node.defn && ctrl.node.defn.options) {
          return ctrl.node.defn.options;
        }
        return ctrl.getDefn().options || ($scope.getProp('input_type') === 'CheckBox' ? null : yesNo);
      };

      $scope.resetOptions = function() {
        delete ctrl.node.defn.options;
      };

      $scope.editOptions = function() {
        $scope.editingOptions = true;
        $('#afGuiEditor').addClass('af-gui-editing-content');
      };

      $scope.inputTypeCanBe = function(type) {
        var defn = ctrl.getDefn();
        switch (type) {
          case 'CheckBox':
          case 'Radio':
          case 'Select':
            return !(!defn.options && defn.data_type !== 'Boolean');

          case 'TextArea':
          case 'RichTextEditor':
            return (defn.data_type === 'Text' || defn.data_type === 'String');
        }
        return true;
      };

      // Returns a value from either the local field defn or the base defn
      $scope.getProp = function(propName) {
        var path = propName.split('.'),
          item = path.pop(),
          localDefn = drillDown(ctrl.node.defn || {}, path);
        if (typeof localDefn[item] !== 'undefined') {
          return localDefn[item];
        }
        return drillDown(ctrl.getDefn(), path)[item];
      };

      // Checks for a value in either the local field defn or the base defn
      $scope.propIsset = function(propName) {
        var val = $scope.getProp(propName);
        return !(typeof val === 'undefined' || val === null);
      };

      $scope.toggleLabel = function() {
        ctrl.node.defn = ctrl.node.defn || {};
        if (ctrl.node.defn.label === false) {
          delete ctrl.node.defn.label;
        } else {
          ctrl.node.defn.label = false;
        }
      };

      $scope.toggleRequired = function() {
        getSet('required', !getSet('required'));
        return false;
      };

      $scope.toggleHelp = function(position) {
        getSet('help_' + position, $scope.propIsset('help_' + position) ? null : (ctrl.getDefn()['help_' + position] || ts('Enter text')));
        return false;
      };

      // Getter/setter for definition props
      $scope.getSet = function(propName) {
        return _.wrap(propName, getSet);
      };

      // Getter/setter callback
      function getSet(propName, val) {
        if (arguments.length > 1) {
          var path = propName.split('.'),
            item = path.pop(),
            localDefn = drillDown(ctrl.node, ['defn'].concat(path)),
            fieldDefn = drillDown(ctrl.getDefn(), path);
          // Set the value if different than the field defn, otherwise unset it
          if (typeof val !== 'undefined' && (val !== fieldDefn[item] && !(!val && !fieldDefn[item]))) {
            localDefn[item] = val;
          } else {
            delete localDefn[item];
            clearOut(ctrl.node, ['defn'].concat(path));
          }
          return val;
        }
        return $scope.getProp(propName);
      }
      this.getSet = getSet;

      this.setEditingOptions = function(val) {
        $scope.editingOptions = val;
      };

      // Returns a reference to a path n-levels deep within an object
      function drillDown(parent, path) {
        var container = parent;
        _.each(path, function(level) {
          container[level] = container[level] || {};
          container = container[level];
        });
        return container;
      }

      // Recursively clears out empty arrays and objects
      function clearOut(parent, path) {
        var item;
        while (path.length && _.every(drillDown(parent, path), _.isEmpty)) {
          item = path.pop();
          delete drillDown(parent, path)[item];
        }
      }
    }
  });

})(angular, CRM.$, CRM._);
