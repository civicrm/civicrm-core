// https://civicrm.org/licensing
(function(angular, $, _) {
  "use strict";

  // Cribbed from the Api4 Explorer
  angular.module('afGuiEditor').directive('afGuiFieldValue', function(afGui) {
    return {
      bindToController: {
        op: '<?',
        field: '<afGuiFieldValue'
      },
      require: {
        ngModel: 'ngModel',
        editor: '?^^afGuiEditor'
      },
      link: {
        post: function ngOptionsPreLink(scope, element, attr, ctrls) {

          // Formatter for ngModel to convert value to a string for select2
          // AngularJS provides its own formatter (`stringBasedInputType`) which simply casts the value to a string
          // Formatters are applied in reverse-order so using postLink ensures this one comes last in the array & gets applied first
          function formatViewValue(value) {
            if (Array.isArray(value)) {
              return value.join("\u0001");
            }
            if (typeof value === 'boolean') {
              return value ? '1' : '0';
            }
            return '' + value;
          }
          ctrls.ngModel.$formatters.push(formatViewValue);
        }
      },
      controller: function ($element, $timeout) {
        var ts = CRM.ts('org.civicrm.afform_admin'),
          ctrl = this,
          dataType,
          multi;

        function makeWidget(field) {
          var options,
            filters,
            $el = $($element),
            inputType = field.input_type;

          getDataType();

          // Decide whether the input should be multivalued
          if (ctrl.op) {
            multi = ['IN', 'NOT IN'].includes(ctrl.op);
          } else if (inputType && dataType !== 'Boolean') {
            multi = (inputType === 'CheckBox' || (field.input_attrs && field.input_attrs.multiple));
            // Hidden fields are multi-select if the original input type is.
            if (inputType === 'Hidden' || inputType === 'DisplayOnly') {
              multi = _.contains(['CheckBox', 'Radio', 'Select'], field.original_input_type);
            }
          } else {
            multi = field.serialize || dataType === 'Array';
          }
          $el.crmAutocomplete('destroy').crmDatepicker('destroy');
          // Allow input_type to override dataType
          if (inputType === 'Date') {
            $el.crmDatepicker({time: (field.input_attrs && field.input_attrs.time) || false});
          }
          else if (field.fk_entity || field.options || dataType === 'Boolean') {
            if (field.fk_entity) {
              // Static options for choosing current user or other entities on the form
              options = [];
              filters = (field.input_attrs && field.input_attrs.filter) || {};
              if (field.fk_entity === 'Individual' || (field.fk_entity === 'Contact' && (!filters.contact_type || filters.contact_type === 'Individual'))) {
                options.push('user_contact_id');
              }
              _.each(ctrl.editor ? ctrl.editor.getEntities() : [], function(entity) {
                let filtersMatch = (entity.type === field.fk_entity) || (field.fk_entity === 'Contact' && ['Individual', 'Household', 'Organization'].includes(entity.type));
                // Check if field filters match entity data (e.g. contact_type)
                _.each(filters, function(value, key) {
                  if (entity.data && entity.data[key] && entity.data[key] != value) {
                    filtersMatch = false;
                  }
                });
                if (filtersMatch) {
                  options.push({id: entity.name, label: entity.label, icon: afGui.meta.entities[entity.type].icon});
                }
              });
              var params = field.entity && field.name ? {fieldName: field.entity + '.' + field.name} : {filters: filters};
              $el.crmAutocomplete(field.fk_entity, params, {
                multiple: multi,
                separator: '\u0001',
                "static": options,
                minimumInputLength: options.length ? 1 : 0
              });
            } else if (field.options) {
              options = _.transform(field.options, function(options, val) {
                options.push({id: val.id, text: val.label});
              }, []);
              $el.select2({data: options, multiple: multi, separator: '\u0001'});
            } else if (dataType === 'Boolean') {
              $el.attr('placeholder', ts('- select -')).crmSelect2({allowClear: false, separator: '\u0001', placeholder: ts('- select -'), data: [
                  {id: '1', text: ts('Yes')},
                  {id: '0', text: ts('No')}
                ]});
            }
          } else if (dataType === 'Integer' && !multi) {
            $el.attr('type', 'number');
          }
        }

        function isSelect2() {
          return $element.is('.select2-container + input');
        }

        function getDataType() {
          if (ctrl.field) {
            dataType = ctrl.field.data_type;
          }
          else {
            dataType = null;
          }
        }

        function convertDataType(val) {
          if (dataType === 'Integer' || dataType === 'Float') {
            let newVal = Number(val);
            // FK Entities can use a mix of numeric & string values (see `"static": options` above)
            if (ctrl.field.fk_entity && ('' + newVal) !== val) {
              return val;
            }
            return newVal;
          }
          return val;
        }

        // Copied from ng-list but applied conditionally if field is multi-valued
        var parseFieldInput = function(viewValue) {
          // If the viewValue is invalid (say required but empty) it will be `undefined`
          if (_.isUndefined(viewValue)) return;

          if ((viewValue === '1' || viewValue === '0') && ctrl.field.data_type === 'Boolean') {
            return viewValue === '1';
          }

          if (!multi || !isSelect2()) {
            return convertDataType(viewValue);
          }

          var list = [];

          if (viewValue) {
            _.each(viewValue.split("\u0001"), function(value) {
              list.push(convertDataType(value));
            });
          }

          return list;
        };

        this.$onInit = function() {
          getDataType();
          // Copied from ng-list
          ctrl.ngModel.$parsers.push(parseFieldInput);

          // Copied from ng-list
          ctrl.ngModel.$isEmpty = function(value) {
            return !value || !value.length;
          };
        };

        this.$onChanges = function() {
          $timeout(function() {
            makeWidget(ctrl.field);
          });
        };
      }
    };
  });

})(angular, CRM.$, CRM._);
