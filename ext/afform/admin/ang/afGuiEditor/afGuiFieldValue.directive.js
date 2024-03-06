// https://civicrm.org/licensing
(function(angular, $, _) {
  "use strict";

  // Cribbed from the Api4 Explorer
  angular.module('afGuiEditor').directive('afGuiFieldValue', function(afGui) {
    return {
      bindToController: {
        field: '<afGuiFieldValue'
      },
      require: {
        ngModel: 'ngModel',
        editor: '?^^afGuiEditor'
      },
      controller: function ($element, $timeout) {
        var ts = CRM.ts('org.civicrm.afform_admin'),
          ctrl = this,
          multi;

        function makeWidget(field) {
          var options,
            filters,
            $el = $($element),
            inputType = field.input_type,
            dataType = field.data_type;
          multi = field.serialize || dataType === 'Array';
          $el.crmAutocomplete('destroy').crmDatepicker('destroy');
          // Allow input_type to override dataType
          if (inputType) {
            multi = (dataType !== 'Boolean' &&
              (inputType === 'CheckBox' || (field.input_attrs && field.input_attrs.multiple)));
          }
          if (inputType === 'Date') {
            $el.crmDatepicker({time: (field.input_attrs && field.input_attrs.time) || false});
          }
          else if (field.fk_entity || field.options || dataType === 'Boolean') {
            if (field.fk_entity) {
              // Static options for choosing current user or other entities on the form
              options = [];
              filters = (field.input_attrs && field.input_attrs.filter) || {};
              if (field.fk_entity === 'Contact' && (!filters.contact_type || filters.contact_type === 'Individual')) {
                options.push('user_contact_id');
              }
              _.each(ctrl.editor ? ctrl.editor.getEntities({type: field.fk_entity}) : [], function(entity) {
                // Check if field filters match entity data (e.g. contact_type)
                var filtersMatch = true;
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
              $el.attr('placeholder', ts('- select -')).crmSelect2({allowClear: false, multiple: multi, separator: '\u0001', placeholder: ts('- select -'), data: [
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

        // Copied from ng-list but applied conditionally if field is multi-valued
        var parseFieldInput = function(viewValue) {
          // If the viewValue is invalid (say required but empty) it will be `undefined`
          if (_.isUndefined(viewValue)) return;

          if ((viewValue === '1' || viewValue === '0') && ctrl.field.data_type === 'Boolean') {
            return viewValue === '1';
          }

          if (!multi || !isSelect2()) {
            return viewValue;
          }

          var list = [];

          if (viewValue) {
            _.each(viewValue.split("\u0001"), function(value) {
              if (value) list.push(_.trim(value));
            });
          }

          return list;
        };

        var formatViewValue = function(value) {
          if (Array.isArray(value)) {
            return value.join(',');
          }
          if (typeof value === 'boolean') {
            return value ? '1' : '0';
          }
          return value;
        };

        this.$onInit = function() {
          // Copied from ng-list
          ctrl.ngModel.$parsers.push(parseFieldInput);
          ctrl.ngModel.$formatters.push(formatViewValue);

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
