(function(angular, $, _) {
  // Represent a datetime field as if it were a radio ('schedule.mode') and a datetime ('schedule.datetime').
  // example: <div crm-mailing-radio-date="mySchedule" ng-model="mailing.scheduled_date">...</div>
  angular.module('crmMailing').directive('crmMailingRadioDate', function() {
    return {
      require: 'ngModel',
      link: function($scope, element, attrs, ngModel) {

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
