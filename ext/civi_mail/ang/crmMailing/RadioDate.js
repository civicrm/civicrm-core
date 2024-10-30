(function(angular, $, _) {
  // "YYYY-MM-DD hh:mm:ss" => Date()
  function parseYmdHms(d) {
    var parts = d.split(/[\-: ]/);
    return new Date(parts[0], parts[1]-1, parts[2], parts[3], parts[4], parts[5]);
  }

  function isDateBefore(tgt, cutoff, tolerance) {
    var ad = parseYmdHms(tgt), bd = parseYmdHms(cutoff);
    // We'll allow a little leeway, where tgt is considered before cutoff
    // even if technically misses the cutoff by a little.
    return  ad < bd-tolerance;
  }

  // Represent a datetime field as if it were a radio ('schedule.mode') and a datetime ('schedule.datetime').
  // example: <div crm-mailing-radio-date="mySchedule" ng-model="mailing.scheduled_date">...</div>
  angular.module('crmMailing').directive('crmMailingRadioDate', function(crmUiAlert) {
    return {
      require: 'ngModel',
      link: function($scope, element, attrs, ngModel) {
        var lastAlert = null;

        var schedule = $scope[attrs.crmMailingRadioDate] = {
          mode: 'now',
          datetime: ''
        };

        ngModel.$render = function $render() {
          var sched = ngModel.$viewValue;
          if (!_.isEmpty(sched)) {
            schedule.mode = 'at';
            schedule.datetime = sched;
          }
          else {
            schedule.mode = 'now';
            schedule.datetime = '';
          }
        };

        var updateParent = (function() {
          switch (schedule.mode) {
            case 'now':
              ngModel.$setViewValue(null);
              schedule.datetime = '';
              break;
            case 'at':
              schedule.datetime = schedule.datetime || '?';
              ngModel.$setViewValue(schedule.datetime);
              break;
            default:
              throw 'Unrecognized schedule mode: ' + schedule.mode;
          }
        });

        element
          // Open datepicker when clicking "At" radio
          .on('click', ':radio[value=at]', function() {
            $('.crm-form-date', element).focus();
          })
          // Reset mode if user entered an invalid date
          .on('change', '.crm-hidden-date', function(e, context) {
            if (context === 'userInput' && $(this).val() === '' && $(this).siblings('.crm-form-date').val().length) {
              schedule.mode = 'at';
              schedule.datetime = '?';
            } else {
              var d = new Date(),
                month = '' + (d.getMonth() + 1),
                day = '' + d.getDate(),
                year = d.getFullYear(),
                hours = '' + d.getHours(),
                minutes = '' + d.getMinutes(),
                submittedDate = $(this).val();
              if (month.length < 2) month = '0' + month;
              if (day.length < 2) day = '0' + day;
              if (hours.length < 2) hours = '0' + hours;
              if (minutes.length < 2) minutes = '0' + minutes;
              var
                date = [year, month, day].join('-'),
                time = [hours, minutes, "00"].join(':'),
                currentDate = date + ' ' + time,
                isInPast = (submittedDate.length && submittedDate.match(/^[0-9\-]+ [0-9\:]+$/) && isDateBefore(submittedDate, currentDate, 4*60*60*1000));
              ngModel.$setValidity('dateTimeInThePast', !isInPast);
              if (lastAlert && lastAlert.isOpen) {
                lastAlert.close();
              }
              if (isInPast) {
                lastAlert = crmUiAlert({
                  text: ts('The scheduled date and time is in the past'),
                  title: ts('Error')
                });
              }
            }
          });

        $scope.$watch(attrs.crmMailingRadioDate + '.mode', updateParent);
        $scope.$watch(attrs.crmMailingRadioDate + '.datetime', function(newValue, oldValue) {
          // automatically switch mode based on datetime entry
          if (typeof oldValue === 'undefined') {
            oldValue = '';
          }
          if (typeof newValue === 'undefined') {
            newValue = '';
          }
          if (oldValue !== newValue) {
            if (_.isEmpty(newValue)) {
              schedule.mode = 'now';
            }
            else {
              schedule.mode = 'at';
            }
          }
          updateParent();
        });
      }
    };
  });
})(angular, CRM.$, CRM._);
