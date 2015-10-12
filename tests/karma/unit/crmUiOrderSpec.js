'use strict';

describe('crmUiOrder', function() {

  beforeEach(function() {
    module('crmResource');
    module('crmUtil');
    module('crmUi');
  });

  describe('crmUiOrder', function() {
    var $compile, $q, $rootScope, rows, element;

    var html = '<div>' +
      '  <span crm-ui-order="{var: \'myOrder\', defaults: [\'-num\']}"></span>' +
      '  <table>' +
      '    <thead>' +
      '      <tr>' +
      '        <th><a crm-ui-order-by="[myOrder,\'name\']" id="th-name">Name</a></th>' +
      '        <th><a crm-ui-order-by="[myOrder,\'num\']" id="th-num">Num</a></th>' +
      '      </tr>' +
      '    </thead>' +
      '    <tbody>' +
      '      <tr ng-repeat="r in rows|orderBy:myOrder.get()">' +
      '        <td class="row-value">{{r.name}}</td>' +
      '      </tr>' +
      '    </tbody>' +
      '  </table>' +
      '</div>';

    beforeEach(inject(function(_$compile_, _$rootScope_, _$q_) {
      $compile = _$compile_;
      $rootScope = _$rootScope_;
      $q = _$q_;

      $rootScope.rows = rows = [
        {name: 'a', num: 200},
        {name: 'c', num: 300},
        {name: 'b', num: 100},
        {name: 'd', num: 0}
      ];

    }));

    it('changes primary ordering on click', function() {
      element = $compile(html)($rootScope);
      $rootScope.$digest();
      expect($rootScope.myOrder).toEqual(jasmine.any(Object));
      expect(element.find('.row-value').text()).toBe('cabd');

      element.find('#th-name').click();
      $rootScope.$digest();
      expect(element.find('.row-value').text()).toBe('abcd');
    });

    it('cycles through ascending/descending orderings on multiple clicks', function() {
      // default: -num
      element = $compile(html)($rootScope);
      $rootScope.$digest();
      expect($rootScope.myOrder.get()).toEqual(['-num']);
      expect($rootScope.myOrder.getDir('num')).toEqual('-');
      expect(element.find('.row-value').text()).toBe('cabd');

      // toggle: "-num" => ""
      element.find('#th-num').click();
      $rootScope.$digest();
      expect($rootScope.myOrder.get()).toEqual([]);
      expect($rootScope.myOrder.getDir('num')).toEqual('');
      expect(element.find('.row-value').text()).toBe('acbd');

      // toggle: "" => "+num"
      element.find('#th-num').click();
      $rootScope.$digest();
      expect($rootScope.myOrder.get()).toEqual(['+num']);
      expect($rootScope.myOrder.getDir('num')).toEqual('+');
      expect(element.find('.row-value').text()).toBe('dbac');

      // toggle: "+num" => "-num"
      element.find('#th-num').click();
      $rootScope.$digest();
      expect($rootScope.myOrder.get()).toEqual(['-num']);
      expect($rootScope.myOrder.getDir('num')).toEqual('-');
      expect(element.find('.row-value').text()).toBe('cabd');
    });

  });
});
