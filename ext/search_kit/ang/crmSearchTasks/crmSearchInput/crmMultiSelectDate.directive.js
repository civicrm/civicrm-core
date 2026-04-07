(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchTasks')
    .directive('crmMultiSelectDate', function () {
      return {
        restrict: 'A',
        require: 'ngModel',
        link: function (scope, element, attrs, ngModel) {

          let defaultDate = null;

          function getDisplayDate(date) {
            return $.datepicker.formatDate(CRM.config.dateInputFormat, $.datepicker.parseDate('yy-mm-dd', date));
          }

          ngModel.$render = function () {
            element.val(Array.isArray(ngModel.$viewValue) ? ngModel.$viewValue.join(',') : ngModel.$viewValue).change();
          };

          element
            .crmSelect2({
              multiple: true,
              data: [],
              initSelection: function(element, callback) {
                const values = [];
                $.each($(element).val().split(','), function(k, v) {
                  values.push({
                    text: getDisplayDate(v),
                    id: v
                  });
                });
                callback(values);
              }
            })
            .on('select2-opening', function(e) {
              const $el = $(this),
                $input = $('.select2-search-field input', $el.select2('container'));
              // Prevent select2 from opening and show a datepicker instead
              e.preventDefault();
              if (!$input.data('datepicker')) {
                $input
                  .datepicker({
                    beforeShow: function() {
                      const existingSelections = _.pluck($el.select2('data') || [], 'id');
                      return {
                        changeMonth: true,
                        changeYear: true,
                        defaultDate: defaultDate,
                        beforeShowDay: function(date) {
                          // Don't allow the same date to be selected twice
                          const dateStr = $.datepicker.formatDate('yy-mm-dd', date);
                          if (_.includes(existingSelections, dateStr)) {
                            return [false, '', ''];
                          }
                          return [true, '', ''];
                        }
                      };
                    }
                  })
                  .datepicker('show')
                  .on('change.crmDate', function() {
                    if ($(this).val()) {
                      const data = $el.select2('data') || [];
                      defaultDate = $(this).datepicker('getDate');
                      data.push({
                        text: $.datepicker.formatDate(CRM.config.dateInputFormat, defaultDate),
                        id: $.datepicker.formatDate('yy-mm-dd', defaultDate)
                      });
                      $el.select2('data', data, true);
                    }
                  })
                  .on('keyup', function() {
                    $(this).val('').datepicker('show');
                  });
              }
            })
            // Don't leave datepicker open when clearing selections
            .on('select2-removed', function() {
              $('input.hasDatepicker', $(this).select2('container'))
                .datepicker('hide');
            })
            .on('change', function() {
              ngModel.$setViewValue(element.val().split(','));
            });
        }
      };
    });
})(angular, CRM.$, CRM._);
