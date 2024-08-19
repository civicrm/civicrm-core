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
        yesNo = [
          {id: '1', label: ts('Yes')},
          {id: '0', label: ts('No')}
        ];
      $scope.editingOptions = false;

      this.$onInit = function() {
        ctrl.hasDefaultValue = !!getSet('afform_default');
        setFieldDefn();
        ctrl.inputTypes = _.transform(_.cloneDeep(afGui.meta.inputTypes), function(inputTypes, type) {
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
        // Quick-add links for autocompletes
        this.quickAddLinks = [];
        let allowedEntity = (ctrl.getFkEntity() || {}).entity;
        let allowedEntities = (allowedEntity === 'Contact') ? ['Individual', 'Household', 'Organization'] : [allowedEntity];
        (CRM.config.quickAdd || []).forEach((link) => {
          if (allowedEntities.includes(link.entity)) {
            this.quickAddLinks.push({
              id: link.path,
              icon: link.icon,
              text: link.title,
            });
          }
        });
        this.searchOperators = CRM.afAdmin.search_operators;
        // If field has limited operators, set appropriately
        if (ctrl.fieldDefn.operators && ctrl.fieldDefn.operators.length) {
          this.searchOperators = _.pick(this.searchOperators, ctrl.fieldDefn.operators);
        }
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
        // Calc fields are specific to a search display, not part of the schema
        if (!defn && ctrl.container.getSearchDisplay()) {
          var searchDisplay = ctrl.container.getSearchDisplay();
          defn = _.findWhere(searchDisplay.calc_fields, {name: ctrl.node.name});
        }
        defn = defn || {
          label: ts('Untitled'),
          required: false
        };
        if (_.isEmpty(defn.input_attrs)) {
          defn.input_attrs = {};
        }
        return defn;
      };

      // Get the api entity this field belongs to
      this.getEntity = function() {
        return afGui.getEntity(ctrl.container.getFieldEntityType(ctrl.node.name));
      };

      $scope.getOriginalLabel = function() {
        // Use afform entity if available (e.g. "Individual1")
        if (ctrl.container.getEntityName()) {
          return ctrl.editor.getEntity(ctrl.container.getEntityName()).label + ': ' + ctrl.getDefn().label;
        }
        // Use generic entity (e.g. "Contact")
        return ctrl.getEntity().label + ': ' + ctrl.getDefn().label;
      };

      $scope.hasOptions = function() {
        var inputType = $scope.getProp('input_type');
        return _.contains(['CheckBox', 'Radio', 'Select'], inputType) && !(inputType === 'CheckBox' && ctrl.getDefn().data_type === 'Boolean');
      };

      this.getOptions = function() {
        if (ctrl.fieldDefn.options) {
          return ctrl.fieldDefn.options;
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
        if (_.includes(['Date', 'Timestamp'], $scope.getProp('data_type'))) {
          ctrl.node.defn = ctrl.node.defn || {};
          return $scope.getProp('search_range') ? CRM.afGuiEditor.dateRanges : CRM.afGuiEditor.dateRanges.slice(1);
        }
        return ctrl.getDefn().options || (ctrl.getDefn().data_type === 'Boolean' ? yesNo : null);
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
            return defn.options || defn.data_type === 'Boolean' || (defn.input_type === 'EntityRef' && !ctrl.isSearch()) || (defn.input_type === 'Date' && ctrl.isSearch());

          case 'Date':
            return defn.input_type === 'Date';

          case 'TextArea':
          case 'RichTextEditor':
            return (defn.data_type === 'Text' || defn.data_type === 'String');

          case 'Text':
            return !(defn.options || defn.input_type === 'Date' || defn.input_type === 'EntityRef' || defn.data_type === 'Boolean');

          case 'Number':
            return !(defn.options || defn.data_type === 'Boolean');

          case 'DisplayOnly':
          case 'Hidden':
            return true;

          default:
            return false;
        }
      }

      // Returns a value from either the local field defn or the base defn
      $scope.getProp = function(propName, defaultValue) {
        const path = propName.split('.');
        const item = path.pop();
        const localDefn = drillDown(ctrl.node.defn || {}, path);
        if (typeof localDefn[item] !== 'undefined') {
          return localDefn[item];
        }
        const fieldDefn = drillDown(ctrl.getDefn(), path);
        if (typeof fieldDefn[item] !== 'undefined') {
          return fieldDefn[item];
        }
        return defaultValue;
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
        if (ctrl.hasDefaultValue) {
          $scope.toggleDefaultValue();
        }
      };

      $scope.toggleAttr = function(attr) {
        getSet(attr, !getSet(attr));
      };

      $scope.toggleHelp = function(position) {
        getSet('help_' + position, $scope.propIsset('help_' + position) ? null : (ctrl.getDefn()['help_' + position] || ts('Enter text')));
      };

      function defaultValueShouldBeArray() {
        return ($scope.getProp('data_type') !== 'Boolean' &&
          ($scope.getProp('input_type') === 'CheckBox' || $scope.getProp('input_attrs.multiple')));
      }

      function setFieldDefn() {
        ctrl.fieldDefn = angular.merge({}, ctrl.getDefn(), ctrl.node.defn);
      }

      $scope.toggleDefaultValue = function() {
        if (ctrl.hasDefaultValue) {
          getSet('afform_default', undefined);
          ctrl.hasDefaultValue = false;
        } else {
          ctrl.hasDefaultValue = true;
        }
      };

      this.defaultDateType = function(newValue) {
        if (arguments.length) {
          if (newValue === 'relative') {
            getSet('afform_default', 'now +0 day');
          }
          if (newValue === 'now') {
            getSet('afform_default', 'now');
          }
          if (newValue === 'fixed') {
            getSet('afform_default', '');
          }
        }
        if (this.fieldDefn.input_type === 'Date') {
          const defaultVal = getSet('afform_default');
          if (defaultVal === 'now') {
            return 'now';
          }
          else if (typeof defaultVal === 'string' && defaultVal.startsWith('now')) {
            return 'relative';
          }
        }
        return 'fixed';
      };

      this.defaultDateOffset = function(newValue) {
        let defaultVals = getSet('afform_default').split(' ');
        if (arguments.length) {
          defaultVals[1] = newValue < 0 ? newValue : '+' + newValue;
          getSet('afform_default', defaultVals.join(' '));
        }
        return parseInt(defaultVals[1], 10);
      };

      this.defaultDateUnit = function(newValue) {
        let defaultVals = getSet('afform_default').split(' ');
        if (arguments.length) {
          defaultVals[2] = newValue;
          getSet('afform_default', defaultVals.join(' '));
        }
        return defaultVals[2];
      };

      this.defaultDatePlural = function() {
        return Math.abs(this.defaultDateOffset()) !== 1;
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
            ctrl.node.defn = ctrl.node.defn || {};
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

      // Getter/setter for search_operator and expose_operator combo-field
      // The expose_operator flag changes the behavior of the search_operator field
      // to either set the value on the backend, or set the default value for the user-select list on the form
      $scope.getSetOperator = function(val) {
        if (arguments.length) {
          // _EXPOSE_ is not a real option for search_operator, instead it sets the expose_operator boolean
          getSet('expose_operator', val === '_EXPOSE_');
          if (val === '_EXPOSE_') {
            getSet('search_operator', _.keys(ctrl.searchOperators)[0]);
          } else {
            getSet('search_operator', val);
          }
          return val;
        }
        return getSet('expose_operator') ? '_EXPOSE_' : getSet('search_operator');
      };

      // Generic getter/setter for definition props
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
            // Boolean checkbox has no options
            if (val === 'CheckBox' && ctrl.getDefn().data_type === 'Boolean' && ctrl.node.defn) {
              delete ctrl.node.defn.options;
            }
          }
          setFieldDefn();

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
