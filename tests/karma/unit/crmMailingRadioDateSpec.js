'use strict';
/* global CRM:true */

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

      model.the_date = '2014-01-01 ';
      $rootScope.$digest();
      expect($rootScope.myForm.$valid).toBe(false);
      expect(element.find('.radio-now').prop('checked')).toBe(false);
      expect(element.find('.radio-at').prop('checked')).toBe(true);
      expect(element.find('.crm-form-date').datepicker('getDate').toDateString()).toEqual('Wed Jan 01 2014');
      expect(element.find('.crm-form-time').timeEntry('getTime')).toBe(null);

      model.the_date = '02:03:04';
      $rootScope.$digest();
      expect($rootScope.myForm.$valid).toBe(false);
      expect(element.find('.radio-now').prop('checked')).toBe(false);
      expect(element.find('.radio-at').prop('checked')).toBe(true);
      expect(element.find('.crm-form-date').datepicker('getDate')).toBe(null);
      expect(element.find('.crm-form-time').timeEntry('getTime').getMinutes()).toBe(3);

      model.the_date = '2014-01-02 02:03:04';
      $rootScope.$digest();
      expect($rootScope.myForm.$valid).toBe(true);
      expect(element.find('.radio-now').prop('checked')).toBe(false);
      expect(element.find('.radio-at').prop('checked')).toBe(true);
      expect(element.find('.crm-form-date').datepicker('getDate').toDateString()).toEqual('Thu Jan 02 2014');
      expect(element.find('.crm-form-time').timeEntry('getTime').getMinutes()).toBe(3);
    });

    it('should update the model after changing the date and time', function() {
      element = $compile(standardMarkup)($rootScope);
      model.the_date = '';
      $rootScope.$digest();
      expect($rootScope.myForm.$valid).toBe(true);
      expect(element.find('.radio-now').prop('checked')).toBe(true);
      expect(element.find('.radio-at').prop('checked')).toBe(false);

      element.find('.crm-form-date').datepicker('setDate', '2014-01-03').trigger('change');
      $rootScope.$digest();
      expect(model.the_date).toBe('2014-01-03');
      expect($rootScope.myForm.$valid).toBe(false);
      expect(element.find('.radio-now').prop('checked')).toBe(false);
      expect(element.find('.radio-at').prop('checked')).toBe(true);

      element.find('.crm-form-time').timeEntry('setTime', '04:05').trigger('change');
      $rootScope.$digest();
      expect(model.the_date).toBe('2014-01-03 04:05');
      expect($rootScope.myForm.$valid).toBe(true);
      expect(element.find('.radio-now').prop('checked')).toBe(false);
      expect(element.find('.radio-at').prop('checked')).toBe(true);

      element.find('.crm-form-date').datepicker('setDate', '').trigger('change');
      $rootScope.$digest();
      expect(model.the_date).toBe('04:05');
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
