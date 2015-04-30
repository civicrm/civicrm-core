/*
 * crmField is useful for converting field metadata into field markup.
 */

(function(angular, $, _) {

  angular.module('crmField', [])

    // This is the interface we want to developers to use; ex: <div crm-field-build="fieldMetaData"></div>
    .directive('crmFieldBuild', ['$compile', 'crmFieldDelegate', function($compile, crmFieldDelegate) {
      return {
        link: function(scope, elem, attrs) {
          crmFieldDelegate.getWidgetData(scope.field).then(function(result) {
            scope.widgetData = result;
            var childEl = $compile('<div crm-field-build-' + scope.widgetData.type + '></div>')(scope);
            elem.append(childEl);
          });
        },
        replace: true
      };
    }])

    // Delegate of crmFieldBuild; shouldn't be used externally.
    .directive('crmFieldBuildCheckbox', function() {
      return {
        replace: true,
        templateUrl: '~/crmField/checkbox.html'
      };
    })

    // Delegate of crmFieldBuild; shouldn't be used externally.
    .directive('crmFieldBuildRadio', function() {
      return {
        replace: true,
        templateUrl: '~/crmField/radio.html'
      };
    })

    // Delegate of crmFieldBuild; shouldn't be used externally.
    .directive('crmFieldBuildSelect', function() {
      return {
        replace: true,
        templateUrl: '~/crmField/select.html'
      };
    })

    // Delegate of crmFieldBuild; shouldn't be used externally.
    .directive('crmFieldBuildText', function() {
      return {
        replace: true,
        templateUrl: '~/crmField/text.html'
      };
    })

    // This service figures out which field widget to use for given metadata
    .service('crmFieldDelegate', ['crmApi', '$q', function(crmApi, $q) {
      this.widgetTypeMap = {
        checkbox: ['CheckBox'],
        radio: ['Radio'],
        select: ['AdvMulti-Select', 'Autocomplete-Select', 'Multi-Select', 'Select'],
        text: ['Text']
      };

      /**
       * @param {Object} field
       * @returns {Promise} widgetData
       */
      this.getWidgetData = function(field) {
        var widgetData = {
          // For human readable text, custom fields use "label" while settings use "title"
          label: field.hasOwnProperty('label') ? field.label : field.title,
          name: field.name
        };

        widgetData.type = _.findKey(this.widgetTypeMap, function(group) {
          return _.contains(group, field.html_type);
        });

        if (_.contains(['checkbox', 'radio', 'select'], widgetData.type)) {
          return this.getOptions(field).then(function(result){
            widgetData.options = result;
            return widgetData;
          });
        } else {
          return $q.when(widgetData);
        }
      };

      /**
       * Returns option list for the given field.
       *
       * @param {Object} field
       * @returns {Array} OptionValue objects
       */
      this.getOptions = function(field) {
        var options = [];

        // Boolean fields which do not specify option values default to Yes/No
        if (field.type === 'Boolean' && !field.hasOwnProperty('option_values')) {
          options.push({
            is_active: 1,
            is_default: 1,
            label: ts("Yes"),
            value: 1,
            weight: 1
          });
          options.push({
            is_active: 1,
            is_default: 0,
            label: ts("No"),
            value: 0,
            weight: 2
          });

        // TODO: It appears there are only two Settings which follow the "option_values"
        // convention; we should move these to an optionGroup and get rid of this
        // condition. See also above reference to option_values.
        } else if (field.hasOwnProperty('option_values')) {
          $.each(field.option_values, function(k, v) {
            options.push({
              is_active: 1,
              label: v,
              value: k,
              weight: k
            });
          });
        } else if (field.hasOwnProperty('pseudoconstant') && field.pseudoconstant.hasOwnProperty('optionGroupName')) {
          options = crmApi('OptionValue', 'get', {
            'option_group_id': field.pseudoconstant.optionGroupName
          }).then(function (result) {
            return result.values;
          });
        } else if (field.hasOwnProperty('pseudoconstant')) {
          console.log('TODO: we need a way to handle pseudoconstants that aren\'t option groups. Field name: ' + field.name);
        } else {
          console.log('Error: No options could be found for ' + field.name);
        }

        return $q.when(options);
      };
    }]);
})(angular, CRM.$, CRM._);