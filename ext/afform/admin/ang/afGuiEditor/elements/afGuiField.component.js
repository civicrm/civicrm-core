// https://civicrm.org/licensing
(function(angular, $, _) {
  "use strict";
  let afGuiFieldId = 0;
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
      const ts = $scope.ts = CRM.ts('org.civicrm.afform_admin');
      const ctrl = this;
      const singleElement = [''];
      // When search-by-range is enabled the second element gets a suffix for some properties like "placeholder2"
      const rangeElements = ['', '2'];
      const dateRangeElements = ['1', '2'];
      const yesNo = [
        {id: true, label: ts('Yes')},
        {id: false, label: ts('No')}
      ];

      let entityRefOptions = [];
      let searchJoins = null;

      $scope.editingOptions = false;
      $scope.fieldId = 'af-gui-field-' + afGuiFieldId++;

      this.$onInit = function() {
        ctrl.hasDefaultValue = !!getSet('afform_default');
        setFieldDefn();
        ctrl.inputTypes = _.transform(_.cloneDeep(afGui.meta.inputTypes), function(inputTypes, type) {
          type.enabled = inputTypeCanBe(type.name);
          // Change labels for EntityRef fields
          if (ctrl.getDefn().input_type === 'EntityRef') {
            const entity = ctrl.getFkEntity();
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
        this.isMultiFieldFilter = ctrl.node.name.includes(',');
      };

      this.getFkEntity = function() {
        const fkEntity = ctrl.getDefn().fk_entity;
        return ctrl.editor.meta.entities[fkEntity];
      };

      this.isSearch = function() {
        return ctrl.editor.getFormType() === 'search';
      };

      this.canBeRange = function() {
        // Range search only makes sense for search display forms
        return this.isSearch() &&
          // Hack for postal code which is not stored as a number but can act like one
          (ctrl.getFieldName().substr(-11) === 'postal_code' || (
            // Multiselects cannot use range search
            !ctrl.getDefn().input_attrs.multiple &&
            // DataType & inputType must make sense for a range
            _.includes(['Date', 'Timestamp', 'Integer', 'Float', 'Money'], ctrl.getDefn().data_type) &&
            _.includes(['Date', 'Number', 'Select'], $scope.getProp('input_type'))
        ));
      };

      this.canBeMultiple = () => {
        if (!this.isSearch() ||
          ['Date', 'Timestamp'].includes(ctrl.getDefn().data_type) ||
          !(['Select', 'EntityRef', 'ChainSelect'].includes($scope.getProp('input_type')))
        ) {
          return false;
        }
        const op = $scope.getSetOperator();
        return (!op || ['_EXPOSE_', 'IN', 'NOT IN'].includes(op));
      };

      this.getRangeElements = function(type) {
        if (!$scope.getProp('search_range') || (type === 'Select' && ctrl.getDefn().input_type === 'Date')) {
          return singleElement;
        }
        return type === 'Date' ? dateRangeElements : rangeElements;
      };

      // Returns the original field definition from metadata
      this.getDefn = function() {
        let defn = afGui.getField(ctrl.container.getFieldEntityType(ctrl.getFieldName()), ctrl.getFieldName());
        // Calc fields are specific to a search display, not part of the schema
        if (!defn && ctrl.container.getSearchDisplay()) {
          const searchDisplay = ctrl.container.getSearchDisplay();
          defn = _.findWhere(searchDisplay.calc_fields, {name: ctrl.getFieldName()});
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

      this.getFieldName = function() {
        // Search filters can contain multiple field names joined by a comma. Return the first as the primary.
        return ctrl.node.name.split(',')[0];
      };

      // Get the api entity this field belongs to
      this.getEntity = function() {
        return afGui.getEntity(ctrl.container.getFieldEntityType(ctrl.getFieldName()));
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
        const inputType = $scope.getProp('input_type');
        if (inputType === 'Range' && ctrl.getDefn().data_type === 'Boolean') {
        }
        return _.contains(['CheckBox', 'Toggle', 'Radio', 'Select'], inputType) &&
          !(inputType === 'CheckBox' && ctrl.getDefn().data_type === 'Boolean');
      };

      this.getOptions = function() {
        if (ctrl.fieldDefn.options) {
          return ctrl.fieldDefn.options;
        }
        return this.getOriginalOptions();
      };

      this.getOriginalOptions = function() {
        if (ctrl.getDefn().input_type === 'EntityRef') {
          // Build a list of all entities in this form that can be referenced by this field.
          const newOptions = _.map(ctrl.editor.getEntities({type: ctrl.getDefn().fk_entity}), (entity) => {
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

      this.getInputTypeTemplate = () => {
        const selectedType = $scope.getProp('input_type');
        const meta = this.inputTypes.find((type) => type.name === selectedType);

        if (!meta || !meta.admin_template) {
          return '~/afGuiEditor/inputType/Missing.html';
        }

        return meta.admin_template;
      };

      $scope.resetOptions = function() {
        delete ctrl.node.defn.options;
      };

      $scope.editOptions = function() {
        $scope.editingOptions = true;
        $('#afGuiEditor').addClass('af-gui-editing-content');
      };

      function inputTypeCanBe(type) {
        const defn = ctrl.getDefn();
        if (defn.input_type === type) {
          return true;
        }
        if (defn.readonly && !ctrl.isSearch()) {
          switch (type) {
            case 'DisplayOnly':
            case 'Hidden':
              return true;

            default:
              return false;
          }
        }
        switch (type) {
          case 'CheckBox':
          case 'Toggle':
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

          case 'Range':
            return (defn.data_type === 'Integer' || defn.data_type === 'Float' || defn.data_type === 'Money');

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
        const val = $scope.getProp(propName);
        return !(typeof val === 'undefined' || val === null);
      };

      $scope.getRangeProp = (prop) => {
        const options = this.getOptions();
        if (!options) {
          const val = $scope.getProp('input_attrs.' + prop);
          // Set these all explicitly if not set
          if (typeof val === 'undefined') {
            ctrl.node.defn = ctrl.node.defn || {};
            ctrl.node.defn.input_attrs = ctrl.node.defn.input_attrs || {};
            switch (prop) {
              case 'min':
                return (ctrl.node.defn.input_attrs.min = 0);
              case 'max':
                return (ctrl.node.defn.input_attrs.max = 99);
              case 'step':
                return (ctrl.node.defn.input_attrs.step = 1);
            }
          }
          return val;
        }
        // Calculate min, max and range based on options
        switch (prop) {
          case 'min':
            return Math.min(...options.map(opt => Number(opt.id)));
          case 'max':
            return Math.max(...options.map(opt => Number(opt.id)));
          case 'step':
            const values = options.map(opt => Number(opt.id)).sort((a, b) => a - b);
            // Return difference between first two values, or 1 if not enough values
            return values.length > 1 ? values[1] - values[0] : 1;
        }
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
        const newVal = getSet('input_attrs.multiple', !getSet('input_attrs.multiple'));
        if (newVal && getSet('search_range')) {
          getSet('search_range', false);
        }
      };

      $scope.toggleSearchRange = function() {
        const newVal = getSet('search_range', !getSet('search_range'));
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
          ($scope.getProp('input_type') === 'CheckBox' || $scope.getProp('input_type') === 'Toggle' || $scope.getProp('input_attrs.multiple')));
      }

      function setFieldDefn() {
        // Deeply merge defn to include nested settings e.g. `input_attrs.time`.
        ctrl.fieldDefn = angular.merge({}, ctrl.getDefn(), ctrl.node.defn);
        // Undo deep merge of options array.
        if (ctrl.node.defn && ctrl.node.defn.options) {
          ctrl.fieldDefn.options = JSON.parse(JSON.stringify(ctrl.node.defn.options));
        }
      }

      $scope.toggleDefaultValue = function() {
        if (ctrl.hasDefaultValue) {
          getSet('afform_default', undefined);
          ctrl.hasDefaultValue = false;
        } else {
          ctrl.hasDefaultValue = true;
          // Boolean default value should be set right away, as there are no options
          if (ctrl.getDefn().data_type === 'Boolean') {
            getSet('afform_default', true);
          }
        }
      };

      // Return `true` if field has a default value and it's not a boolean or relative date
      this.hasDefaultValueInput = function() {
        return ctrl.hasDefaultValue && ctrl.getDefn().data_type !== 'Boolean' && ctrl.defaultDateType() === 'fixed';
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

      this.toggleMultiFieldFilter = function() {
        this.isMultiFieldFilter = !this.isMultiFieldFilter;
        if (!this.isMultiFieldFilter) {
          this.node.name = this.getFieldName();
        }
      };

      this.getSearchFilterFields = function() {
        return afGui.getSearchDisplayFields(ctrl.container.getSearchDisplay(), _.noop, [ctrl.getFieldName()]);
      };

      this.showLabel = () => {
        if (this.node.defn && this.node.defn.label === false) {
          return false;
        }
        // Single checkboxes don't get a separate label
        return !(getSet('input_type') === 'CheckBox' && this.getDefn().data_type === 'Boolean');
      };

      $scope.defaultValueContains = function(val) {
        const defaultVal = getSet('afform_default');
        return defaultVal === val || (Array.isArray(defaultVal) && defaultVal.includes(val));
      };

      $scope.toggleDefaultValueItem = function(val) {
        if (defaultValueShouldBeArray()) {
          if (!Array.isArray(getSet('afform_default'))) {
            ctrl.node.defn = ctrl.node.defn || {};
            ctrl.node.defn.afform_default = [];
          }
          if (_.includes(ctrl.node.defn.afform_default, val)) {
            const newVal = _.without(ctrl.node.defn.afform_default, val);
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
            getSet('search_operator', Object.keys(ctrl.searchOperators)[0]);
          } else {
            getSet('search_operator', val);
          }
          // Ensure multiselect is only used when compatible with operator
          if (['_EXPOSE_', 'IN', 'NOT IN'].includes(val) != getSet('input_attrs.multiple')) {
            $scope.toggleMultiple();
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
          const path = propName.split('.'),
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
            if ((val === 'CheckBox' || val === 'Toggle') && ctrl.getDefn().data_type === 'Boolean' && ctrl.node.defn) {
              delete ctrl.node.defn.options;
            }
          }
          setFieldDefn();

          // When changing the multiple property, force-reset the default value widget
          if (ctrl.hasDefaultValue && _.includes(['input_type', 'input_attrs.multiple'], propName)) {
            ctrl.hasDefaultValue = false;
            if (!defaultValueShouldBeArray() && Array.isArray(getSet('afform_default'))) {
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
        return $scope.getProp(propName) || '';
      }
      this.getSet = getSet;

      this.setEditingOptions = function(val) {
        $scope.editingOptions = val;
      };

      this.getSearchJoins = function() {
        if (Array.isArray(searchJoins)) {
          return searchJoins;
        }
        searchJoins = [];
        const searchDisplay = ctrl.container.getSearchDisplay();
        if (searchDisplay) {
          Object.keys(searchDisplay['saved_search_id.form_values'].join).forEach((joinName) => {
            searchJoins.push({
              id: joinName,
              text: searchDisplay['saved_search_id.form_values'].join[joinName],
            });
          });
        }
        return searchJoins;
      };

      // When changing min, keep it under max.
      this.onChangeMin = () => {
        const max = $scope.getProp('input_attrs.max');
        if (typeof max !== 'undefined' && max <= ctrl.node.defn.input_attrs.min) {
          ctrl.node.defn.input_attrs.min = ctrl.node.defn.input_attrs.max - 1;
        }
      };

      // When changing max, keep it over min.
      this.onChangeMax = () => {
        const min = $scope.getProp('input_attrs.min');
        if (typeof min !== 'undefined' && min >= ctrl.node.defn.input_attrs.max) {
          ctrl.node.defn.input_attrs.max = ctrl.node.defn.input_attrs.min + 1;
        }
      };

      // Returns a reference to a path n-levels deep within an object
      function drillDown(parent, path) {
        let container = parent;
        path.forEach((level) => {
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
        let item;
        while (path.length && _.every(drillDown(parent, path), isEmpty)) {
          item = path.pop();
          delete drillDown(parent, path)[item];
        }
      }
    }
  });

})(angular, CRM.$, CRM._);
