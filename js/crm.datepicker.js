(function($, CRM, _) {
  "use strict";

  /**
   * @see https://docs.civicrm.org/dev/en/latest/framework/ui/#date-picker
   */
  $.fn.crmDatepicker = function(options) {
    return $(this).each(function() {
      if ($(this).is('.crm-form-date-wrapper .crm-hidden-date')) {
        // Already initialized - destroy
        $(this)
          .off('.crmDatepicker')
          .css('display', '')
          .removeClass('crm-hidden-date')
          .siblings().remove();
        $(this).unwrap();
      }
      if (options === 'destroy') {
        return;
      }
      var
        $dataField = $(this).wrap('<span class="crm-form-date-wrapper" />'),
        settings = _.cloneDeep(options || {}),
        $dateField = $(),
        $timeField = $(),
        $clearLink = $(),
        placeholder,
        hasDatepicker = settings.date !== false && settings.date !== 'yy',
        type = hasDatepicker ? 'text' : 'number';

      if (settings.allowClear !== undefined ? settings.allowClear : !$dataField.is('.required, [required]')) {
        $clearLink = $('<a class="crm-hover-button crm-clear-link" title="'+ _.escape(ts('Clear')) +'"><i class="crm-i fa-times" aria-hidden="true"></i></a>')
          .insertAfter($dataField);
      }
      if (settings.time !== false) {
        $timeField = $('<input>').insertAfter($dataField);
        placeholder = settings.timePlaceholder || $dataField.attr('time-placeholder');
        CRM.utils.copyAttributes($dataField, $timeField, ['class', 'disabled']);
        $timeField
          .addClass('crm-form-text crm-form-time')
          // Set default placeholder as clock icon (`fa-clock` is Unicode f017)
          .attr('placeholder', placeholder === undefined ? '\uf017' : placeholder)
          .attr('aria-label', placeholder === undefined ? ts('Time') : placeholder)
          .change(updateDataField)
          .timeEntry({
            spinnerImage: '',
            useMouseWheel: false,
            show24Hours: settings.time === true || settings.time === undefined ? CRM.config.timeIs24Hr : settings.time == '24'
          });
        if (!placeholder) {
          $timeField.addClass('crm-placeholder-icon');
        }
      }
      if (settings.date !== false) {
        // Render "number" field for year-only format, calendar popup for all other formats
        $dateField = $('<input type="' + type + '">').insertAfter($dataField);
        CRM.utils.copyAttributes($dataField, $dateField, ['style', 'class', 'disabled', 'aria-label']);
        placeholder = settings.placeholder || $dataField.attr('placeholder');
        $dateField.addClass('crm-form-' + type);
        if (!settings.minDate && isInt(settings.start_date_years)) {
          settings.minDate = '' + (new Date().getFullYear() - settings.start_date_years) + '-01-01';
        }
        if (!settings.maxDate && isInt(settings.end_date_years)) {
          settings.maxDate = '' + (new Date().getFullYear() + settings.end_date_years) + '-12-31';
        }
        if (hasDatepicker) {
          settings.minDate = settings.minDate ? CRM.utils.makeDate(settings.minDate) : null;
          settings.maxDate = settings.maxDate ? CRM.utils.makeDate(settings.maxDate) : null;
          settings.dateFormat = typeof settings.date === 'string' ? settings.date : CRM.config.dateInputFormat;
          settings.changeMonth = _.includes(settings.dateFormat, 'm');
          settings.changeYear = _.includes(settings.dateFormat, 'y');
          if (!settings.yearRange && settings.minDate !== null && settings.maxDate !== null) {
            settings.yearRange = '' + CRM.utils.formatDate(settings.minDate, 'yy') + ':' + CRM.utils.formatDate(settings.maxDate, 'yy');
          }
          settings.showButtonPanel = true;
          settings.closeText = 'Close';
          $dateField.addClass('crm-form-date').datepicker(settings);
        } else {
          $dateField.attr('min', settings.minDate ? CRM.utils.formatDate(settings.minDate, 'yy') : '1000');
          $dateField.attr('max', settings.maxDate ? CRM.utils.formatDate(settings.maxDate, 'yy') : '4000');
          placeholder = null;
        }
        // Set placeholder as calendar icon (`fa-calendar` is Unicode f073)
        $dateField.attr({placeholder: placeholder === undefined ? '\uF073' : placeholder}).change(updateDataField);
        if (!placeholder) {
          $dateField.addClass('crm-placeholder-icon').attr('aria-label', ts('Select Date'));
        }
        else {
          $dateField.attr('aria-label', placeholder);
        }
        $dateField.change(updateDataField);
        $dateField.on('focus', function(event) {
          var calender = $('#ui-datepicker-div');
          if (calender.css('display') != 'none') {
            var today = $('.ui-datepicker-today a')[0];
            if (!today) {
              today = $('.ui-state-active')[0] || $('.ui-state-default')[0];
            }
            today.focus();
            event.preventDefault();
          }
        });
        $('#ui-datepicker-div').keydown(function(event) {
          var key = event.key,
          target = event.target,
          calender = $('#ui-datepicker-div'),
          today =  $('.ui-datepicker-today a')[0] || $('.ui-state-active')[0] || $('.ui-state-default')[0],
          el = $(target).closest('td');
          if (key === "Tab") {
            event.preventDefault();
            if (el.hasClass('ui-datepicker-days-cell-over')) {
              $('.ui-datepicker-current').focus();
            }
            else if ($(target).hasClass('ui-datepicker-current')) {
              $('.ui-datepicker-close').focus();
            }
            if ($(target).hasClass('ui-datepicker-close')) {
              $('.ui-datepicker-month').focus();
            }
            else if ($(target).hasClass('ui-datepicker-month')) {
              $('.ui-datepicker-year').focus();
            }
            else if ($(target).hasClass('ui-datepicker-year')) {
              today.focus();
            }
            else {
              $('.ui-datepicker-close').focus();
            }
          }
          else if (key === "ArrowLeft" || key === "ArrowRight") {
            if (!$(target).hasClass('ui-datepicker-close') && $(target).hasClass('ui-state-default')) {
              event.preventDefault();
              el = (key === 'ArrowLeft') ? $('a.ui-state-default', el.prev())[0] : $('a.ui-state-default', el.next())[0];
              if (el) {
                el.focus();
              }
              else {
                el = $(target).closest('tr');
                el = (key === 'ArrowLeft') ? $('a.ui-state-default', el.prev())[6] : $('a.ui-state-default', el.next())[0];
                if (el) {
                  el.focus();
                }
                else {
                  $('.ui-datepicker-month').focus();
                }
              }
            }
            else if ($(target).hasClass('ui-datepicker-month')) {
              if (key === "ArrowRight") {
                $('.ui-datepicker-year').focus();
              }
              else {
                $('td a.ui-state-default')[0].focus();
              }
            }
            else if ($(target).hasClass('ui-datepicker-year')) {
              if (key === "ArrowLeft") {
                $('.ui-datepicker-month').focus();
              }
              else {
                $('td a.ui-state-default')[0].focus();
              }
            }
          }
          // TODO : for now we are relying on up/down arrow change month and year, but need to replace with ENTER + Up/Down
          else if (key === "ArrowUp" || key === "ArrowDown") {
            event.preventDefault();
            if (!$(target).hasClass('ui-datepicker-close') && $(target).hasClass('ui-state-default')) {
              var index = el.index();
              el = (key === "ArrowUp") ? $('a.ui-state-default', $(target).closest('tr').prev()) : $('a.ui-state-default', $(target).closest('tr').next());
              if (el.length) {
                if (el[index]) {
                  el[index].focus();
                }
                else if (key === "ArrowUp") {
                  el[0].focus();
                }
                else if (key === "ArrowDown") {
                  el.last().focus();
                }
              }
              else {
                $('.ui-datepicker-month').focus();
              }
            }
            else if ($(target).hasClass('ui-datepicker-month')) {
              var nextMonth = (key === "ArrowUp") ? parseInt($(target).val()) - 1 : parseInt($(target).val()) + 1;
              nextMonth = (nextMonth === 12) ? 0 : ((nextMonth === -1) ? 11 : nextMonth);
              $(target).val(nextMonth).trigger('change');
              $('.ui-datepicker-month').focus();
            }
            else if ($(target).hasClass('ui-datepicker-year')) {
              var nextYear = (key === "ArrowUp") ? parseInt($(target).val()) - 1 : parseInt($(target).val()) + 1;
              // prevent value to change when the next selected year doesn't fall in the range
              if ((nextYear <= $('.ui-datepicker-year option:last-child').val()) && (nextYear >= $('.ui-datepicker-year option:first-child').val())) {
                $(target).val(nextYear).trigger('change');
                $('.ui-datepicker-year').focus();
              }
            }
            else if ($(target).hasClass('ui-datepicker-close')) {
              $('.ui-datepicker-month').focus();
            }
          }
        });
      }

      // Rudimentary validation. TODO: Roll into use of jQUery validate and ui.datepicker.validation
      function isValidDate() {
        // FIXME: parseDate doesn't work with incomplete date formats; skip validation if no month, day or year in format
        var lowerFormat = settings.dateFormat.toLowerCase();
        if (lowerFormat.indexOf('y') < 0 || lowerFormat.indexOf('m') < 0 || !dateHasDay()) {
          return true;
        }
        try {
          $.datepicker.parseDate(settings.dateFormat, $dateField.val());
          return true;
        } catch (e) {
          return false;
        }
      }

      /**
       * Does the date format contain the day.
       *
       * @returns {boolean}
       */
      function dateHasDay() {
        var lowerFormat = settings.dateFormat.toLowerCase();
        return lowerFormat.indexOf('d') >= 0;
      }
      function updateInputFields(e, context) {
        var val = $dataField.val(),
          time = null;
        if (context !== 'userInput' && context !== 'crmClear') {
          if (hasDatepicker) {
            $dateField.datepicker('setDate', _.includes(val, '-') ? $.datepicker.parseDate('yy-mm-dd', val) : null);
          } else if ($dateField.length) {
            $dateField.val(val.slice(0, 4));
          }
          if ($timeField.length) {
            if (val.length === 8) {
              time = val;
            } else if (val.length === 19) {
              time = val.split(' ')[1];
            }
            $timeField.timeEntry('setTime', time);
          }
        }
        $clearLink.css('visibility', val ? 'visible' : 'hidden');
      }
      function updateDataField(e, context) {
        // The crmClear event wipes all the field values anyway, so no need to respond
        if (context !== 'crmClear') {
          var val = '';
          if ($dateField.val()) {
            if (hasDatepicker && isValidDate() && dateHasDay()) {
              val = $.datepicker.formatDate('yy-mm-dd', $dateField.datepicker('getDate'));
              $dateField.removeClass('crm-error');
            } else if (!hasDatepicker) {
              val = $dateField.val() + '-01-01';
            }
            else if (!dateHasDay()) {
              // This would be a Year-month date (yyyy-mm)
              // it could be argued it should not use a datepicker....
              val = $dateField.val() + '-01';
            } else {
              $dateField.addClass('crm-error');
            }
          }
          if ($timeField.val()) {
            val += (val ? ' ' : '') + $timeField.timeEntry('getTime').toTimeString().substr(0, 8);
          }
          $dataField.val(val).trigger('change', ['userInput']);
        }
      }
      $dataField.hide().addClass('crm-hidden-date').on('change.crmDatepicker', updateInputFields);
      updateInputFields();
    });
  };

  function isInt(value) {
    if (isNaN(value)) {
      return false;
    }
    var x = parseFloat(value);
    return (x | 0) === x;
  }

})(jQuery, CRM, CRM._);
