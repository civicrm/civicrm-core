'use strict';

describe('afTabset with conditional tabs', () => {

  let $compile, $rootScope, $timeout, scope, element;

  beforeEach(() => {
    module('crmResource');
    module('af');
  });

  beforeEach(inject((_$compile_, _$rootScope_, _$timeout_) => {
    $compile = _$compile_;
    $rootScope = _$rootScope_;
    $timeout = _$timeout_;
  }));

  // Page "two" is shown while scope.showTwo is true.
  function build(showTwo) {
    scope = $rootScope.$new();
    scope.meta = {name: 'testForm'};
    scope.showTwo = showTwo;
    // af-if requires an afForm ancestor; stand in for it with a controller that evaluates against the scope.
    const form = angular.element('<div></div>');
    form.data('$afFormController', {evaluateExpression: () => scope.showTwo});
    form.append(
      '<af-tabset page-nav-buttons="true">' +
        '<af-tab name="one" title="One"></af-tab>' +
        '<af-tab name="two" title="Two" af-if="showTwo"></af-tab>' +
        '<af-tab name="three" title="Three"></af-tab>' +
      '</af-tabset>'
    );
    element = $compile(form)(scope);
    scope.$digest();
    $timeout.flush();
  }

  function tabsetCtrl() {
    return angular.element(element[0].querySelector('af-tabset')).isolateScope().$ctrl;
  }

  function tabNames() {
    return tabsetCtrl().tabs.map((tab) => tab.name);
  }

  function setShowTwo(value) {
    scope.showTwo = value;
    scope.$digest();
  }

  it('keeps a conditional tab in markup order', () => {
    build(true);
    expect(tabNames()).toEqual(['one', 'two', 'three']);
  });

  it('puts a tab back in its place when it is shown', () => {
    build(false);
    setShowTwo(true);
    expect(tabNames()).toEqual(['one', 'two', 'three']);
  });

  it('removes a tab when it is hidden', () => {
    build(true);
    setShowTwo(false);
    expect(tabNames()).toEqual(['one', 'three']);
    expect(element[0].querySelectorAll('.crm-tab-button').length).toBe(2);
  });

  it('goes next to a page once it is shown', () => {
    build(false);
    setShowTwo(true);
    tabsetCtrl().goToNext();
    expect(tabsetCtrl().selectedTab).toBe('two');
  });

  it('moves the selection off a tab that is hidden', () => {
    build(true);
    tabsetCtrl().selectTab('two');
    setShowTwo(false);
    expect(tabsetCtrl().selectedTab).toBe('three');
  });

});
