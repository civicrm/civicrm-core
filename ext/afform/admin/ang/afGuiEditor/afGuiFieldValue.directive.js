// https://civicrm.org/licensing
(function(angular, $, _) {
  "use strict";

  // Cribbed from the Api4 Explorer
  angular.module('afGuiEditor').directive('afGuiFieldValue', function() {
    return {
      scope: {
        field: '=afGuiFieldValue'
      },
      require: 'ngModel',
      link: function (scope, element, attrs, ctrl) {
        var ts = scope.ts = CRM.ts(),
          multi;

        function destroyWidget() {
          var $el = $(element);
          if ($el.is('.crm-form-date-wrapper .crm-hidden-date')) {
            $el.crmDatepicker('destroy');
          }
          if ($el.is('.select2-container + input')) {
            $el.crmEntityRef('destroy');
          }
          $(element).removeData().removeAttr('type').removeAttr('placeholder').show();
        }

        function makeWidget(field) {
          var $el = $(element),
            inputType = field.input_type,
            dataType = field.data_type;
          multi = field.serialize || dataType === 'Array';
          if (inputType === 'Date') {
            $el.crmDatepicker({time: (field.input_attrs && field.input_attrs.time) || false});
          }
          else if (field.fk_entity || field.options || dataType === 'Boolean') {
            if (field.fk_entity) {
              $el.crmEntityRef({entity: field.fk_entity, select:{multiple: multi}});
            } else if (field.options) {
              var options = _.transform(field.options, function(options, val) {
                options.push({id: val.id, text: val.label});
              }, []);
              $el.select2({data: options, multiple: multi});
            } else if (dataType === 'Boolean') {
              $el.attr('placeholder', ts('- select -')).crmSelect2({allowClear: false, multiple: multi, placeholder: ts('- select -'), data: [
                  {id: '1', text: ts('Yes')},
                  {id: '0', text: ts('No')}
                ]});
            }
          } else if (dataType === 'Integer' && !multi) {
            $el.attr('type', 'number');
          }
        }

        // Copied from ng-list but applied conditionally if field is multi-valued
        var parseList = function(viewValue) {
          // If the viewValue is invalid (say required but empty) it will be `undefined`
          if (_.isUndefined(viewValue)) return;

          if (!multi) {
            return viewValue;
          }

          var list = [];

          if (viewValue) {
            _.each(viewValue.split(','), function(value) {
              if (value) list.push(_.trim(value));
            });
          }

          return list;
        };

        // Copied from ng-list
        ctrl.$parsers.push(parseList);
        ctrl.$formatters.push(function(value) {
          return _.isArray(value) ? value.join(', ') : value;
        });

        // Copied from ng-list
        ctrl.$isEmpty = function(value) {
          return !value || !value.length;
        };

        scope.$watchCollection('field', function(field) {
          destroyWidget();
          if (field) {
            makeWidget(field);
          }
        });
      }
    };
  });

})(angular, CRM.$, CRM._);
