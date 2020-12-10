(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchKit').directive('crmSearchValue', function($interval, formatForSelect2) {
    return {
      scope: {
        data: '=crmSearchValue'
      },
      require: 'ngModel',
      link: function (scope, element, attrs, ngModel) {
        var ts = scope.ts = CRM.ts(),
          multi = _.includes(['IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN'], scope.data.op),
          format = scope.data.format;

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

        function makeWidget(field, op, optionKey) {
          var $el = $(element),
            inputType = field.input_type,
            dataType = field.data_type;
          if (!op) {
            op = field.serialize || dataType === 'Array' ? 'IN' : '=';
          }
          multi = _.includes(['IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN'], op);
          if (op === 'IS NULL' || op === 'IS NOT NULL') {
            $el.hide();
            return;
          }
          if (inputType === 'Date') {
            if (_.includes(['=', '!=', '>', '>=', '<', '<='], op)) {
              $el.crmDatepicker({time: (field.input_attrs && field.input_attrs.time) || false});
            }
          } else if (_.includes(['=', '!=', 'IN', 'NOT IN', 'CONTAINS'], op) && (field.fk_entity || field.options || dataType === 'Boolean')) {
            if (field.options) {
              if (field.options === true) {
                $el.addClass('loading');
                var waitForOptions = $interval(function() {
                  if (field.options !== true) {
                    $interval.cancel(waitForOptions);
                    $el.removeClass('loading').crmSelect2({data: getFieldOptions, multiple: multi});
                  }
                }, 200);
              }
              $el.attr('placeholder', ts('select')).crmSelect2({data: getFieldOptions, multiple: multi});
            } else if (field.fk_entity) {
              $el.crmEntityRef({entity: field.fk_entity, select:{multiple: multi}});
            } else if (dataType === 'Boolean') {
              $el.attr('placeholder', ts('- select -')).crmSelect2({allowClear: false, multiple: multi, placeholder: ts('- select -'), data: [
                // FIXME: it would be more correct to use real true/false booleans instead of numbers, but select2 doesn't seem to like them
                {id: 1, text: ts('Yes')},
                {id: 0, text: ts('No')}
              ]});
            }
          } else if (dataType === 'Integer' && !multi) {
            $el.attr('type', 'number');
          }

          function getFieldOptions() {
            return {results: formatForSelect2(field.options, optionKey, 'label', ['description', 'color', 'icon'])};
          }
        }

        // Copied from ng-list but applied conditionally if field is multi-valued
        var parseList = function(viewValue) {
          // If the viewValue is invalid (say required but empty) it will be `undefined`
          if (_.isUndefined(viewValue)) return;

          if (!multi) {
            return format === 'json' ? JSON.stringify(viewValue) : viewValue;
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
        ngModel.$parsers.push(parseList);
        ngModel.$formatters.push(function(value) {
          return _.isArray(value) ? value.join(', ') : (format === 'json' && value !== '' ? JSON.parse(value) : value);
        });

        // Copied from ng-list
        ngModel.$isEmpty = function(value) {
          return !value || !value.length;
        };

        scope.$watchCollection('data', function(data) {
          destroyWidget();
          if (data.field) {
            makeWidget(data.field, data.op, data.optionKey || 'id');
          }
        });
      }
    };
  });

})(angular, CRM.$, CRM._);
