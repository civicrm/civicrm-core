'use strict';

describe('crmUiDatepicker', () => {

  let $compile, $rootScope, $timeout;

  beforeEach(() => {
    module('crmResource');
    module('crmUi');
    inject((_$compile_, _$rootScope_, _$timeout_) => {
      $compile = _$compile_;
      $rootScope = _$rootScope_;
      $timeout = _$timeout_;
    });
    CRM.config = CRM.config || {};
    CRM.config.dateInputFormat = 'mm/dd/yy';
    CRM.config.timeIs24Hr = true;
  });

  // Set the value after the datepicker initialises, as a form prefill does
  function render(settings, value) {
    $rootScope.settings = settings;
    $rootScope.model = {value: ''};
    const element = $compile('<form name="myForm"><input crm-ui-datepicker="settings" ng-model="model.value" /></form>')($rootScope);
    $rootScope.$digest();
    $timeout.flush();
    $rootScope.model.value = value;
    $rootScope.$digest();
    $timeout.flush();
    return element;
  }

  it('accepts a full datetime when time is shown', () => {
    render({}, '2026-11-18 10:30:00');
    expect($rootScope.myForm.$valid).toBe(true);
  });

  it('rejects a date without a time when time is shown', () => {
    render({}, '2026-11-18');
    expect($rootScope.myForm.$valid).toBe(false);
  });

  it('accepts a date when time is hidden', () => {
    render({time: false}, '2026-11-18');
    expect($rootScope.myForm.$valid).toBe(true);
  });

  it('accepts a datetime column value when time is hidden', () => {
    const element = render({time: false}, '2026-11-18 00:00:00');
    expect(element.find('.crm-form-date').val()).toEqual('11/18/2026');
    expect($rootScope.myForm.$valid).toBe(true);
  });

});
