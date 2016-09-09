'use strict';
/* global $, CRM:true */

describe('crmMailingRadioDate', function() {

  beforeEach(function() {
    module('crmResource');
    module('crmUtil');
    module('crmMailing');
  });

  var standardMarkup = '<form name="myForm">' +
    '  <div crm-mailing-radio-date="mySchedule" ng-model="model.the_date" name="myRadioDate">' +
    '    <input ng-model="mySchedule.mode" type="radio" name="send" value="now" class="radio-now" />' +
    '    <input ng-model="mySchedule.mode" type="radio" name="send" value="at" class="radio-at" />' +
    '    <input crm-ui-datepicker ng-model="mySchedule.datetime" ng-required="mySchedule.mode == \'at\'"/>' +
    '  </div>' +
    '</form>';

  describe('crmMailingRadioDate directive', function() {
    var $compile,
      $rootScope,
      $interval,
      $timeout,
      model,
      element;

    beforeEach(inject(function(_$compile_, _$rootScope_, _$interval_, _$timeout_) {
      $compile = _$compile_;
      $rootScope = _$rootScope_;
      $interval = _$interval_;
      $timeout = _$timeout_;

      // Global settings needed for crmUiDatepicker
      CRM = CRM || {};
      CRM.config = CRM.config || {};
      CRM.config.dateInputFormat = 'mm/dd/yy';
      CRM.config.timeIs24Hr = true;

      $rootScope.model = model = {
        the_date: ''
      };
    }));

    it('should update the UI after changing the model', function() {
      element = $compile(standardMarkup)($rootScope);

      model.the_date = '';
      $rootScope.$digest();
      expect($rootScope.myForm.$valid).toBe(true);
      expect(element.find('.radio-now').prop('checked')).toBe(true);
      expect(element.find('.radio-at').prop('checked')).toBe(false);
      expect(element.find('.crm-form-date').datepicker('getDate')).toBe(null);
      expect(element.find('.crm-form-time').timeEntry('getTime')).toBe(null);

      model.the_date = ' ';
      $rootScope.$digest();
      expect($rootScope.myForm.$valid).toBe(false);
      expect(element.find('.radio-now').prop('checked')).toBe(false);
      expect(element.find('.radio-at').prop('checked')).toBe(true);
      expect(element.find('.crm-form-date').datepicker('getDate')).toBe(null);
      expect(element.find('.crm-form-time').timeEntry('getTime')).toBe(null);

      model.the_date = '2014-01-01';
      $rootScope.$digest();
      expect($rootScope.myForm.$valid).toBe(false);
      expect(element.find('.radio-now').prop('checked')).toBe(false);
      expect(element.find('.radio-at').prop('checked')).toBe(true);
      expect(element.find('.crm-hidden-date').val()).toEqual('2014-01-01');
      expect(element.find('.crm-form-date').val()).toEqual('01/01/2014');
      expect(element.find('.crm-form-time').timeEntry('getTime')).toBe(null);

      model.the_date = '02:03:00';
      $rootScope.$digest();
      expect($rootScope.myForm.$valid).toBe(false);
      expect(element.find('.radio-now').prop('checked')).toBe(false);
      expect(element.find('.radio-at').prop('checked')).toBe(true);
      expect(element.find('.crm-form-date').datepicker('getDate')).toBe(null);
      expect(element.find('.crm-form-time').timeEntry('getTime').getMinutes()).toBe(3);

      model.the_date = '2014-01-02 02:03:00';
      $rootScope.$digest();
      expect($rootScope.myForm.$valid).toBe(false);
      expect(element.find('.radio-now').prop('checked')).toBe(false);
      expect(element.find('.radio-at').prop('checked')).toBe(true);
      expect(element.find('.crm-form-date').datepicker('getDate').toDateString()).toEqual('Thu Jan 02 2014');
      expect(element.find('.crm-form-time').timeEntry('getTime').getMinutes()).toBe(3);

      var now = new Date();
      var month = '';
      var day = '';
      if (now.getMonth() == 12) {
        month = '1';
      } else {
        month = month + (now.getMonth() + 1);
      }
      if (now.getDate() >= 28) {
        day = '1';
      } else {
        day = day + (now.getDate() + 1);
      }
      var year = (now.getFullYear() + 1);
      if (day.length < 2) day = '0' + day;
      if (month.length < 2) month = '0' + month;
      var minutes = "30";
      var hours = "09";
      var datenow = [year, month, day].join('-');
      var time = [hours, minutes, "00"].join(':');
      var currentDate = datenow + ' ' + time;
      var ndate = new Date(datenow);
      model.the_date = currentDate;

      $rootScope.$digest();
      expect($rootScope.myForm.$valid).toBe(true);
      expect(element.find('.radio-now').prop('checked')).toBe(false);
      expect(element.find('.radio-at').prop('checked')).toBe(true);
      expect(element.find('.crm-form-date').datepicker('getDate').toDateString()).toEqual(ndate.toDateString());
      expect(element.find('.crm-hidden-date').val()).toEqual(currentDate);
    });

    it('should update the model after changing the date and time', function() {
      element = $compile(standardMarkup)($rootScope);

      model.the_date = '';
      $rootScope.$digest();
      expect($rootScope.myForm.$valid).toBe(true);
      expect(element.find('.radio-now').prop('checked')).toBe(true);
      expect(element.find('.radio-at').prop('checked')).toBe(false);

      element.find('.radio-now').click().trigger('click').trigger('change');
      element.find('.crm-form-date').datepicker('setDate', $.datepicker.parseDate('yy-mm-dd', '2014-01-03')).trigger('change');
      $rootScope.$digest();
      expect(model.the_date).toBe('2014-01-03');
      expect($rootScope.myForm.$valid).toBe(false);
      expect(element.find('.radio-now').prop('checked')).toBe(false);
      expect(element.find('.radio-at').prop('checked')).toBe(true);

      element.find('.crm-form-time').timeEntry('setTime', '04:05').trigger('change');
      $rootScope.$digest();
      expect(model.the_date).toBe('2014-01-03 04:05:00');
      expect($rootScope.myForm.$valid).toBe(false);
      expect(element.find('.radio-now').prop('checked')).toBe(false);
      expect(element.find('.radio-at').prop('checked')).toBe(true);

      element.find('.crm-form-date').datepicker('setDate', '').trigger('change');
      $rootScope.$digest();
      expect(model.the_date).toBe('04:05:00');
      expect($rootScope.myForm.$valid).toBe(false);
      expect(element.find('.radio-now').prop('checked')).toBe(false);
      expect(element.find('.radio-at').prop('checked')).toBe(true);

      element.find('.radio-now').click().trigger('click').trigger('change');
      $rootScope.$digest();
      expect(model.the_date).toBe(null);
      expect($rootScope.myForm.$valid).toBe(true);
      expect(element.find('.radio-now').prop('checked')).toBe(true);
      expect(element.find('.radio-at').prop('checked')).toBe(false);
    });
  });
});
