describe('crmAutosave', function() {

  function using(name, values, func) {
    for (var i = 0, count = values.length; i < count; i++) {
      if (Object.prototype.toString.call(values[i]) !== '[object Array]') {
        values[i] = [values[i]];
      }
      func.apply(this, values[i]);
      jasmine.currentEnv_.currentSpec.description += ' (with "' + name + '" using ' + values[i].join(', ') + ')';
    }
  }

  beforeEach(function() {
    module('crmUtil');
    module('crmAutosave');
  });

  describe('Autosave directive', function() {
    var $compile,
      $rootScope,
      $interval,
      $timeout,
      fakeCtrl,
      model,
      element;

    beforeEach(inject(function(_$compile_, _$rootScope_, _$interval_, _$timeout_, _$q_) {
      // The injector unwraps the underscores (_) from around the parameter names when matching
      $compile = _$compile_;
      $rootScope = _$rootScope_;
      $interval = _$interval_;
      $timeout = _$timeout_;

      $rootScope.fakeCtrl = fakeCtrl = {
        doSave: function() {
        },
        doSaveWithPromise: function() {
          var dfr = _$q_.defer();
          $timeout(function() {
            dfr.resolve();
          }, 25);
          return dfr.promise;
        },
        doSaveSlowly: function() {
          var dfr = _$q_.defer();
          fakeCtrl.savingSlowly = true;
          $timeout(function() {
            fakeCtrl.savingSlowly = false;
            dfr.resolve();
          }, 1000);
          return dfr.promise;
        }
      };
      spyOn(fakeCtrl, 'doSave').and.callThrough();
      spyOn(fakeCtrl, 'doSaveWithPromise').and.callThrough();
      spyOn(fakeCtrl, 'doSaveSlowly').and.callThrough();

      $rootScope.model = model = {
        fieldA: 'alpha',
        fieldB: 'beta'
      };
    }));

    // Fake wait - advance the interval & timeout
    // @param int msec Total time to advance the clock
    // @param int steps Number of times to issue flush()
    //    Higher values provide a more realistic simulation
    //    but can be a bit slower.
    function wait(msec, steps) {
      if (!steps) steps = 4;
      for (var i = 0; i < steps; i++) {
        $interval.flush(msec/steps);
        $timeout.flush(msec/steps);
      }
    }

    // TODO: Test: If the save function throws an error, or if its promise returns an error, reset form as dirty.

    var fakeSaves = {
      "fakeCtrl.doSave()": 'doSave',
      "fakeCtrl.doSaveWithPromise()": 'doSaveWithPromise'
    };
    angular.forEach(fakeSaves, function(saveFunc, saveFuncExpr) {
      it('calls ' + saveFuncExpr + ' twice over the course of three changes', function() {
        element = $compile('<form name="myForm" crm-autosave="' + saveFuncExpr + '" crm-autosave-model="model" crm-autosave-interval="{poll: 25, save: 50}"><input class="fieldA" ng-model="model.fieldA"><input class="fieldB" ng-model="model.fieldB"></form>')($rootScope);
        $rootScope.$digest();

        // check that we load pristine data and don't do any saving
        wait(100);
        expect(element.find('.fieldA').val()).toBe('alpha');
        expect(element.find('.fieldB').val()).toBe('beta');
        expect($rootScope.myForm.$pristine).toBe(true);
        expect(fakeCtrl[saveFunc].calls.count()).toBe(0);

        // first round of changes
        element.find('.fieldA').val('eh?').trigger('change');
        $rootScope.$digest();
        element.find('.fieldB').val('bee').trigger('change');
        $rootScope.$digest();
        expect(model.fieldA).toBe('eh?');
        expect(model.fieldB).toBe('bee');
        expect($rootScope.myForm.$pristine).toBe(false);
        expect(fakeCtrl[saveFunc].calls.count()).toBe(0);

        // first autosave
        wait(100);
        expect($rootScope.myForm.$pristine).toBe(true);
        expect(fakeCtrl[saveFunc].calls.count()).toBe(1);

        // a little stretch of time with nothing happening
        wait(100);
        expect(fakeCtrl[saveFunc].calls.count()).toBe(1);

        // second round of changes
        element.find('.fieldA').val('ah').trigger('change');
        $rootScope.$digest();
        expect(model.fieldA).toBe('ah');

        // second autosave
        expect($rootScope.myForm.$pristine).toBe(false);
        expect(fakeCtrl[saveFunc].calls.count()).toBe(1);
        wait(100);
        expect(fakeCtrl[saveFunc].calls.count()).toBe(2);
        expect($rootScope.myForm.$pristine).toBe(true);

        // a little stretch of time with nothing happening
        wait(100);
        expect(fakeCtrl[saveFunc].calls.count()).toBe(2);
      });
    });

    it('does not save an invalid form', function() {
      element = $compile('<form name="myForm" crm-autosave="fakeCtrl.doSave()" crm-autosave-model="model" crm-autosave-interval="{poll: 25, save: 50}"><input class="fieldA" ng-model="model.fieldA"><input class="fieldB" required ng-model="model.fieldB"></form>')($rootScope);
      $rootScope.$digest();

      // check that we load pristine data and don't do any saving
      wait(100);
      expect(element.find('.fieldA').val()).toBe('alpha');
      expect(element.find('.fieldB').val()).toBe('beta');
      expect($rootScope.myForm.$pristine).toBe(true);
      expect(fakeCtrl.doSave.calls.count()).toBe(0);

      // first round of changes - fieldB is invalid
      element.find('.fieldA').val('eh?').trigger('change');
      $rootScope.$digest();
      element.find('.fieldB').val('').trigger('change');
      $rootScope.$digest();
      expect(model.fieldA).toBe('eh?');
      expect(model.fieldB).toBeFalsy();
      expect($rootScope.myForm.$pristine).toBe(false);
      expect(fakeCtrl.doSave.calls.count()).toBe(0);

      // first autosave declines to run
      wait(100);
      expect($rootScope.myForm.$pristine).toBe(false);
      expect(fakeCtrl.doSave.calls.count()).toBe(0);

      // second round of changes
      element.find('.fieldB').val('bee').trigger('change');
      $rootScope.$digest();
      expect(model.fieldB).toBe('bee');

      // second autosave
      expect($rootScope.myForm.$pristine).toBe(false);
      expect(fakeCtrl.doSave.calls.count()).toBe(0);
      wait(100);
      expect(fakeCtrl.doSave.calls.count()).toBe(1);
      expect($rootScope.myForm.$pristine).toBe(true);

      // a little stretch of time with nothing happening
      wait(100);
      expect(fakeCtrl.doSave.calls.count()).toBe(1);
    });

    it('defers saving new changes when a save is already pending', function() {
      element = $compile('<form name="myForm" crm-autosave="fakeCtrl.doSaveSlowly()" crm-autosave-model="model" crm-autosave-interval="{poll: 25, save: 50}"><input class="fieldA" ng-model="model.fieldA"><input class="fieldB" ng-model="model.fieldB"></form>')($rootScope);
      $rootScope.$digest();

      // check that we load pristine data and don't do any saving
      wait(100);
      expect(element.find('.fieldA').val()).toBe('alpha');
      expect(element.find('.fieldB').val()).toBe('beta');
      expect($rootScope.myForm.$pristine).toBe(true);
      expect(fakeCtrl.doSaveSlowly.calls.count()).toBe(0);

      // first round of changes
      element.find('.fieldA').val('eh?').trigger('change');
      $rootScope.$digest();
      expect(model.fieldA).toBe('eh?');
      expect($rootScope.myForm.$pristine).toBe(false);
      expect(fakeCtrl.doSaveSlowly.calls.count()).toBe(0);

      // first autosave starts
      wait(100);
      expect(fakeCtrl.savingSlowly).toBe(true);
      expect(fakeCtrl.doSaveSlowly.calls.count()).toBe(1);

      // second round of changes; doesn't save yet
      element.find('.fieldA').val('aleph').trigger('change');
      $rootScope.$digest();
      expect(model.fieldA).toBe('aleph');
      expect(fakeCtrl.savingSlowly).toBe(true);
      expect(fakeCtrl.doSaveSlowly.calls.count()).toBe(1);
      wait(100);
      expect(fakeCtrl.doSaveSlowly.calls.count()).toBe(1);

      // second autosave starts and finishes
      wait(2500, 5);
      expect(fakeCtrl.savingSlowly).toBe(false);
      expect(fakeCtrl.doSaveSlowly.calls.count()).toBe(2);
    });
  });
});
