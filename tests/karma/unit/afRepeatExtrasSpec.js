'use strict';

// Coverage for per-slot "extra" fields in a repeating fieldset:
// each repeat slot carries its own `extras` bag alongside `fields`, and joins
// have no per-slot extras (afField falls back to the form-level extras object).
describe('afform repeat extras', function() {

  beforeEach(function() {
    module('af');
  });

  describe('afRepeatItem controller', function() {
    var $controller, afRepeatItemCtrl;

    beforeEach(inject(function(_$controller_, afRepeatItemDirective) {
      $controller = _$controller_;
      afRepeatItemCtrl = afRepeatItemDirective[0].controller;
    }));

    function makeItem(repeatType, item) {
      var ctrl = $controller(afRepeatItemCtrl, {});
      ctrl.afRepeat = {getRepeatType: () => repeatType};
      ctrl.item = item;
      return ctrl;
    }

    it('exposes a per-slot extras bag for a fieldset repeat', function() {
      var item = {fields: {}};
      var ctrl = makeItem('fieldset', item);

      var extras = ctrl.getExtrasData();
      expect(extras).toEqual({});
      // The bag lives on the slot record, so subscribers can read it after submit.
      expect(item.extras).toBe(extras);
    });

    it('keeps the same extras bag across calls so values persist per slot', function() {
      var ctrl = makeItem('fieldset', {fields: {}});

      ctrl.getExtrasData().referral_note = 'Met at the conference';

      expect(ctrl.getExtrasData().referral_note).toBe('Met at the conference');
      expect(ctrl.item.extras.referral_note).toBe('Met at the conference');
    });

    it('has no per-slot extras for a join repeat', function() {
      var ctrl = makeItem('join', {email: 'a@example.com'});

      expect(ctrl.getExtrasData()).toBe(null);
      // A join must not sprout an extras bag on its record.
      expect('extras' in ctrl.item).toBe(false);
    });

    it('getFieldData targets fields for a fieldset and the record itself for a join', function() {
      var fieldsetItem = {fields: {first_name: 'Jo'}};
      expect(makeItem('fieldset', fieldsetItem).getFieldData()).toBe(fieldsetItem.fields);

      var joinItem = {email: 'a@example.com'};
      expect(makeItem('join', joinItem).getFieldData()).toBe(joinItem);
    });
  });

  describe('afFieldset addRepeatItem', function() {
    var ctrl;

    beforeEach(inject(function($controller, $rootScope, afFieldsetDirective) {
      // afFormCtrl is only wired up in the directive's link fn, so a bare
      // controller falls back to its own local data store - which is all we
      // need to observe what a new repeat slot is seeded with.
      ctrl = $controller(afFieldsetDirective[0].controller, {
        $scope: $rootScope.$new(),
        $element: angular.element('<div></div>'),
        crmApi4: angular.noop
      });
    }));

    it('seeds each new repeat slot with its own fields and extras bags', function() {
      ctrl.addRepeatItem();
      ctrl.addRepeatItem();

      var data = ctrl.getData();
      expect(data).toEqual([
        {fields: {}, extras: {}},
        {fields: {}, extras: {}}
      ]);
      // Distinct objects, so writing to one slot cannot leak into another.
      expect(data[0].extras).not.toBe(data[1].extras);
    });
  });

  describe('afRepeat copyItem', function() {
    var $controller, $rootScope, afRepeatCtrl;

    beforeEach(inject(function(_$controller_, _$rootScope_, afRepeatDirective) {
      $controller = _$controller_;
      $rootScope = _$rootScope_;
      afRepeatCtrl = afRepeatDirective[0].controller;
    }));

    it('copies fields but starts a fresh extras bag for a fieldset repeat', function() {
      var $scope = $rootScope.$new();
      $controller(afRepeatCtrl, {$scope: $scope});
      var data = [{fields: {first_name: 'Jo'}, extras: {referral_note: 'keep?'}}];
      $scope.afFieldset = {getData: () => data};

      $scope.copyItem();

      expect(data.length).toBe(2);
      expect(data[1].fields).toEqual({first_name: 'Jo'});
      // Copied slot must not inherit the previous slot's extra values.
      expect(data[1].extras).toEqual({});
      // Deep copy - editing the copy must not touch the original.
      data[1].fields.first_name = 'Changed';
      expect(data[0].fields.first_name).toBe('Jo');
    });

    it('deep-copies the whole record for a join repeat', function() {
      var $scope = $rootScope.$new();
      $controller(afRepeatCtrl, {$scope: $scope});
      var data = [{email: 'a@example.com'}];
      $scope.afJoin = {getData: () => data};

      $scope.copyItem();

      expect(data.length).toBe(2);
      expect(data[1]).toEqual({email: 'a@example.com'});
      expect(data[1]).not.toBe(data[0]);
    });
  });

});
