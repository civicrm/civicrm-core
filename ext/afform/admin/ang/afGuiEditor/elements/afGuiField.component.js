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
    controller: function($scope, afGui, $timeout) {
      var ts = $scope.ts = CRM.ts('org.civicrm.afform_admin'),
        ctrl = this,
        entityRefOptions = [],
        singleElement = [''],
        // When search-by-range is enabled the second element gets a suffix for some properties like "placeholder2"
        rangeElements = ['', '2'],
        dateRangeElements = ['1', '2'],
        relativeDatesWithPickRange = CRM.afGuiEditor.dateRanges,
        relativeDatesWithoutPickRange = relativeDatesWithPickRange.slice(1),
        yesNo = [
          {id: '1', label: ts('Yes')},
          {id: '0', label: ts('No')}
        ];
      $scope.editingOptions = false;

      this.$onInit = function() {
        ctrl.hasDefaultValue = !!getSet('afform_default');
        ctrl.fieldDefn = angular.extend({}, ctrl.getDefn(), ctrl.node.defn);
        ctrl.inputTypes = _.transform(_.cloneDeep(afGui.meta.inputType), function(inputTypes, type) {
          if (inputTypeCanBe(type.name)) {
            // Change labels for EntityRef fields
            if (ctrl.getDefn().input_type === 'EntityRef') {
              var entity = ctrl.getFkEntity();
              if (entity && type.name === 'EntityRef') {
                type.label = ts('Autocomplete %1', {1: entity.label});
              }
              if (entity && type.name === 'Number') {
                type.label = ts('%1 ID', {1: entity.label});
              }
              if (entity && type.name === 'Select') {
                type.label = ts('Select Form %1', {1: entity.label});
              }
            }
            inputTypes.push(type);
          }
        });
      };

      this.getFkEntity = function() {
        var fkEntity = ctrl.getDefn().fk_entity;
        return ctrl.editor.meta.entities[fkEntity];
      };

      this.isSearch = function() {
        return ctrl.editor.getFormType() === 'search';
      };

      this.canBeRange = function() {
        // Range search only makes sense for search display forms
        return this.isSearch() &&
          // Hack for postal code which is not stored as a number but can act like one
          (ctrl.node.name.substr(-11) === 'postal_code' || (
            // Multiselects cannot use range search
            !ctrl.getDefn().input_attrs.multiple &&
            // DataType & inputType must make sense for a range
            _.includes(['Date', 'Timestamp', 'Integer', 'Float', 'Money'], ctrl.getDefn().data_type) &&
            _.includes(['Date', 'Number', 'Select'], $scope.getProp('input_type'))
        ));
      };

      this.canBeMultiple = function() {
        return this.isSearch() &&
          !_.includes(['Date', 'Timestamp'], ctrl.getDefn().data_type) &&
          _.includes(['Select', 'EntityRef', 'ChainSelect'], $scope.getProp('input_type'));
      };

      this.getRangeElements = function(type) {
        if (!$scope.getProp('search_range') || (type === 'Select' && ctrl.getDefn().input_type === 'Date')) {
          return singleElement;
        }
        return type === 'Date' ? dateRangeElements : rangeElements;
      };

      // Returns the original field definition from metadata
      this.getDefn = function() {
        var defn = afGui.getField(ctrl.container.getFieldEntityType(ctrl.node.name), ctrl.node.name);
        defn = defn || {
          label: ts('Untitled'),
          required: false
        };
        if (_.isEmpty(defn.input_attrs)) {
          defn.input_attrs = {};
        }
        return defn;
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

      this.getOptions = function() {
        if (ctrl.node.defn && ctrl.node.defn.options) {
          return ctrl.node.defn.options;
        }
        if (_.includes(['Date', 'Timestamp'], $scope.getProp('data_type'))) {
          return $scope.getProp('search_range') ? relativeDatesWithPickRange : relativeDatesWithoutPickRange;
        }
        if (ctrl.getDefn().input_type === 'EntityRef') {
          // Build a list of all entities in this form that can be referenced by this field.
          var newOptions = _.map(ctrl.editor.getEntities({type: ctrl.getDefn().fk_entity}), function(entity) {
            return {id: entity.name, label: entity.label};
          }, []);
          // Store it in a stable variable for the sake of ng-repeat
          if (!angular.equals(newOptions, entityRefOptions)) {
            entityRefOptions = newOptions;
          }
          return entityRefOptions;
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

      function inputTypeCanBe(type) {
        var defn = ctrl.getDefn();
        if (defn.input_type === type) {
          return true;
        }
        switch (type) {
          case 'CheckBox':
          case 'Radio':
            return defn.options || defn.data_type === 'Boolean';

          case 'Select':
            return defn.options || defn.data_type === 'Boolean' || defn.input_type === 'EntityRef' || (defn.input_type === 'Date' && ctrl.isSearch());

          case 'Date':
            return defn.input_type === 'Date';

          case 'TextArea':
          case 'RichTextEditor':
            return (defn.data_type === 'Text' || defn.data_type === 'String');

          case 'Text':
            return !(defn.options || defn.input_type === 'Date' || defn.input_type === 'EntityRef' || defn.data_type === 'Boolean');

          case 'Number':
            return !(defn.options || defn.data_type === 'Boolean');

          default:
            return false;
        }
      }

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

      $scope.toggleMultiple = function() {
        var newVal = getSet('input_attrs.multiple', !getSet('input_attrs.multiple'));
        if (newVal && getSet('search_range')) {
          getSet('search_range', false);
        }
      };

      $scope.toggleSearchRange = function() {
        var newVal = getSet('search_range', !getSet('search_range'));
        if (newVal && getSet('input_attrs.multiple')) {
          getSet('input_attrs.multiple', false);
        }
      };

      $scope.toggleRequired = function() {
        getSet('required', !getSet('required'));
      };

      $scope.toggleHelp = function(position) {
        getSet('help_' + position, $scope.propIsset('help_' + position) ? null : (ctrl.getDefn()['help_' + position] || ts('Enter text')));
      };

      function defaultValueShouldBeArray() {
        return ($scope.getProp('data_type') !== 'Boolean' &&
          ($scope.getProp('input_type') === 'CheckBox' || $scope.getProp('input_attrs.multiple')));
      }


      $scope.toggleDefaultValue = function() {
        if (ctrl.hasDefaultValue) {
          getSet('afform_default', undefined);
          ctrl.hasDefaultValue = false;
        } else {
          ctrl.hasDefaultValue = true;
        }
      };

      $scope.defaultValueContains = function(val) {
        val = '' + val;
        var defaultVal = getSet('afform_default');
        return defaultVal === val || (_.isArray(defaultVal) && _.includes(defaultVal, val));
      };

      $scope.toggleDefaultValueItem = function(val) {
        val = '' + val;
        if (defaultValueShouldBeArray()) {
          if (!_.isArray(getSet('afform_default'))) {
            ctrl.node.defn.afform_default = [];
          }
          if (_.includes(ctrl.node.defn.afform_default, val)) {
            var newVal = _.without(ctrl.node.defn.afform_default, val);
            getSet('afform_default', newVal.length ? newVal : undefined);
            ctrl.hasDefaultValue = !!newVal.length;
          } else {
            ctrl.node.defn.afform_default.push(val);
            ctrl.hasDefaultValue = true;
          }
        } else if (getSet('afform_default') === val) {
          getSet('afform_default', undefined);
          ctrl.hasDefaultValue = false;
        } else {
          getSet('afform_default', val);
          ctrl.hasDefaultValue = true;
        }
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
          // When changing input_type
          if (propName === 'input_type') {
            if (ctrl.node.defn && ctrl.node.defn.search_range && !ctrl.canBeRange()) {
              delete ctrl.node.defn.search_range;
              clearOut(ctrl.node, ['defn']);
            }
            if (ctrl.node.defn && ctrl.node.defn.input_attrs && 'multiple' in ctrl.node.defn.input_attrs && !ctrl.canBeMultiple()) {
              delete ctrl.node.defn.input_attrs.multiple;
              clearOut(ctrl.node, ['defn', 'input_attrs']);
            }
          }
          ctrl.fieldDefn = angular.extend({}, ctrl.getDefn(), ctrl.node.defn);

          // When changing the multiple property, force-reset the default value widget
          if (ctrl.hasDefaultValue && _.includes(['input_type', 'input_attrs.multiple'], propName)) {
            ctrl.hasDefaultValue = false;
            if (!defaultValueShouldBeArray() && _.isArray(getSet('afform_default'))) {
              ctrl.node.defn.afform_default = ctrl.node.defn.afform_default[0];
            } else if (defaultValueShouldBeArray() && _.isString(getSet('afform_default')) && ctrl.node.defn.afform_default.length) {
              ctrl.node.defn.afform_default = ctrl.node.defn.afform_default.split(',');
            }
            $timeout(function() {
              ctrl.hasDefaultValue = true;
            });
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

      // Returns true only if value is [], {}, '', null, or undefined.
      function isEmpty(val) {
        return typeof val !== 'boolean' && typeof val !== 'number' && _.isEmpty(val);
      }

      // Recursively clears out empty arrays and objects
      function clearOut(parent, path) {
        var item;
        while (path.length && _.every(drillDown(parent, path), isEmpty)) {
          item = path.pop();
          delete drillDown(parent, path)[item];
        }
      }
    }
  });

})(angular, CRM.$, CRM._);
