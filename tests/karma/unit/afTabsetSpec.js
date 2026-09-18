'use strict';

describe('afTabset', function() {

  var $compile, $rootScope, $timeout, scope;

  // Each tab holds a marked input, so a spec can see which tabs are in the DOM
  // and whether their contents are the same nodes as before.
  var TABS =
    '<af-tab name="one" title="One"><input class="probe" data-tab="one"></af-tab>' +
    '<af-tab name="two" title="Two"><input class="probe" data-tab="two"></af-tab>' +
    '<af-tab name="three" title="Three"><input class="probe" data-tab="three"></af-tab>';

  beforeEach(function() {
    module('crmResource');
    module('af');
    // afForm injects crmApi4, which `af` does not itself require - on a real page it
    // arrives via afCore. @see ext/afform/core/ang/afCore.ang.php
    module('api4');
    module('angularFileUpload');
  });

  beforeEach(inject(['crmLegacy', function(crmLegacy) {
    // afForm builds its file-upload url as it is constructed, and CRM.url needs its
    // templates set once per browser session - which a real page does and karma does not.
    crmLegacy.url({
      back: '/civicrm/crmajax-placeholder-url-path?civicrm-placeholder-url-query=1',
      front: '/civicrm/crmajax-placeholder-url-path?civicrm-placeholder-url-query=1'
    });
  }]));

  beforeEach(inject(function(_$compile_, _$rootScope_, _$timeout_) {
    $compile = _$compile_;
    $rootScope = _$rootScope_;
    $timeout = _$timeout_;
  }));

  function build(html) {
    scope = $rootScope.$new();
    // With no af-form, afTabset reads the form name off the parent scope.
    scope.meta = {name: 'testForm'};
    var element = $compile(html)(scope);
    scope.$digest();
    return element;
  }

  // Names of the tabs whose contents are currently in the DOM, in document order.
  function inDom(element) {
    return Array.prototype.map.call(element[0].querySelectorAll('.probe'), function(el) {
      return el.getAttribute('data-tab');
    });
  }

  function probe(element, name) {
    return element[0].querySelector('.probe[data-tab="' + name + '"]');
  }

  function tabsetCtrl(element) {
    var el = element[0].matches('af-tabset') ? element[0] : element[0].querySelector('af-tabset');
    return angular.element(el).isolateScope().$ctrl;
  }

  describe('outside a submission form', function() {

    it('renders only the selected tab', function() {
      var element = build('<af-tabset>' + TABS + '</af-tabset>');
      // The initial selection is made in a $timeout.
      $timeout.flush();

      expect(tabsetCtrl(element).selectedTab).toBe('one');
      expect(inDom(element)).toEqual(['one']);
    });

    it('renders a tab the first time it is selected', function() {
      var element = build('<af-tabset>' + TABS + '</af-tabset>');
      $timeout.flush();

      tabsetCtrl(element).selectTab('three');
      scope.$digest();

      expect(inDom(element)).toEqual(['one', 'three']);
    });

    it('keeps a tab rendered after switching away, so unsaved input survives', function() {
      var element = build('<af-tabset>' + TABS + '</af-tabset>');
      $timeout.flush();
      tabsetCtrl(element).selectTab('three');
      scope.$digest();

      var input = probe(element, 'three');
      input.value = 'unsaved text';

      tabsetCtrl(element).selectTab('one');
      scope.$digest();

      expect(inDom(element)).toEqual(['one', 'three']);
      // The same node, not a fresh one - which is what preserves the input.
      expect(probe(element, 'three')).toBe(input);
      expect(probe(element, 'three').value).toBe('unsaved text');
    });

  });

  describe('where deferring would change behaviour', function() {

    it('renders every tab up front inside a submission form', function() {
      // An unrendered af-fieldset never registers with afForm, so its values would be
      // dropped from the submission.
      var element = build('<af-form ctrl="afform" ng-form="testForm">' +
        '<af-tabset>' + TABS + '</af-tabset>' +
        '</af-form>');
      // No $timeout.flush(): afForm prefills in a $timeout, and this is only about what
      // is in the DOM once the form has linked.

      expect(inDom(element)).toEqual(['one', 'two', 'three']);
    });

    it('renders every tab up front in page-nav mode', function() {
      // findInvalid() gates forward navigation by searching the DOM.
      var element = build('<af-tabset page-nav-buttons="true">' + TABS + '</af-tabset>');
      $timeout.flush();

      expect(inDom(element)).toEqual(['one', 'two', 'three']);
      expect(tabsetCtrl(element).tabs.length).toBe(3);
      tabsetCtrl(element).tabs.forEach(function(tab) {
        expect(tab.findInvalid().length).toBe(0);
      });
    });

  });

});
