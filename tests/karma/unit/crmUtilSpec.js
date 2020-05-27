'use strict';

describe('crmUtil', function() {

  beforeEach(function() {
    module('crmUtil');
  });

  describe('crmMetadata', function() {
    var crmMetadata, $q, $rootScope, crmApi;

    beforeEach(inject(function(_crmMetadata_, _$rootScope_, _$q_, _crmApi_) {
      crmMetadata = _crmMetadata_;
      $rootScope = _$rootScope_;
      $q = _$q_;
      crmApi = _crmApi_;
    }));

    it('returns a failed promise on error', function(done) {
      var apiSpy = jasmine.createSpy('crmApi');
      crmApi.backend = apiSpy.and.returnValue(crmApi.val({
        is_error: 1
      }));
      expect(apiSpy.calls.count()).toBe(0);
      $q.when(crmMetadata.getFields('MyEntity')).then(
        function() {
          expect(false).toEqual(true);
          done();
        },
        function() {
          expect(apiSpy.calls.count()).toBe(1);
          done();
        }
      );

      $rootScope.$apply();
    });

    it('only calls the API once', function(done) {
      var apiSpy = jasmine.createSpy('crmApi');
      crmApi.backend = apiSpy.and.returnValue(crmApi.val({
        is_error: 0,
        values: {
          id: {
            name: 'id',
            type: 1,
            title: 'My entity ID'
          }
        }
      }));

      expect(apiSpy.calls.count()).toBe(0);
      $q.when(crmMetadata.getFields('MyEntity')).then(
        function(fields) {
          expect(fields.id.title).toBe('My entity ID');
          expect(apiSpy.calls.count()).toBe(1);

          // call a second time, but now the data is cached
          $q.when(crmMetadata.getFields('MyEntity')).then(
            function(fields) {
              expect(fields.id.title).toBe('My entity ID');
              expect(apiSpy.calls.count()).toBe(1);

              // call a third time using a diff interface; data is still cached!
              $q.when(crmMetadata.getField('MyEntity', 'id')).then(
                function(field) {
                  expect(field.title).toBe('My entity ID');
                  expect(apiSpy.calls.count()).toBe(1);
                  done();
                }
              );
            }
          );
        }
      );

      $rootScope.$apply();
    });

    it('returns individual fields', function(done) {
      var apiSpy = jasmine.createSpy('crmApi');
      crmApi.backend = apiSpy.and.returnValue(crmApi.val({
        is_error: 0,
        values: {
          id: {
            name: 'id',
            type: 1,
            title: 'My entity ID'
          }
        }
      }));

      expect(apiSpy.calls.count()).toBe(0);
      $q.when(crmMetadata.getField('MyEntity', 'id')).then(
        function(field) {
          expect(field.title).toBe('My entity ID');
          expect(apiSpy.calls.count()).toBe(1);
          done();
        }
      );

      $rootScope.$apply();
    });

  });

  describe('crmQueue', function() {
    var crmQueue, $q, $rootScope, $timeout;

    beforeEach(inject(function(_crmQueue_, _$rootScope_, _$q_, _$timeout_) {
      crmQueue = _crmQueue_;
      $rootScope = _$rootScope_;
      $q = _$q_;
      $timeout = _$timeout_;
    }));

    function addAfterTimeout(a, b, ms) {
      var dfr = $q.defer();
      $timeout(function(){
        dfr.resolve(a+b);
      }, ms);
      return dfr.promise;
    }

    it('returns in order', function(done) {
      var last = null;
      var queuedFunc = crmQueue(addAfterTimeout);
      // note: the queueing order is more important the timeout-durations (15ms vs 5ms)
      queuedFunc(1, 2, 25).then(function(sum) {
        expect(last).toBe(null);
        expect(sum).toBe(3);
        last = sum;
      });
      queuedFunc(3, 4, 5).then(function(sum){
        expect(last).toBe(3);
        expect(sum).toBe(7);
        last = sum;
        done();
      });

      for (var i = 0; i < 5; i++) {
        $rootScope.$apply();
        $timeout.flush(20);
      }
    });
  });

  describe('crmThrottle', function() {
    var crmThrottle, $q, $timeout, i;

    beforeEach(inject(function(_crmThrottle_, _$q_, _$timeout_) {
      crmThrottle = _crmThrottle_;
      $q = _$q_;
      $timeout = _$timeout_;
    }));

    function resolveAfterTimeout() {
      var dfr = $q.defer();
      $timeout(function(){
        dfr.resolve(i++);
      }, 80);
      return dfr.promise;
    }

    it('executes the function once', function() {
      i = 0;
      crmThrottle(resolveAfterTimeout);
      expect(i).toBe(0);
      $timeout.flush(100);
      expect(i).toBe(1);
      $timeout.verifyNoPendingTasks();
    });

    it('executes the function again', function() {
      i = 0;
      crmThrottle(resolveAfterTimeout);
      $timeout.flush(100);
      expect(i).toBe(1);
      crmThrottle(resolveAfterTimeout);
      $timeout.flush(20);
      expect(i).toBe(1);
      $timeout.flush(100);
      expect(i).toBe(2);
      $timeout.verifyNoPendingTasks();
    });

    it('executes the first and last function', function() {
      i = 0;
      crmThrottle(resolveAfterTimeout);
      $timeout.flush(10);
      crmThrottle(resolveAfterTimeout);
      crmThrottle(resolveAfterTimeout);
      crmThrottle(resolveAfterTimeout);
      crmThrottle(resolveAfterTimeout);
      expect(i).toBe(0);
      $timeout.flush(100);
      expect(i).toBe(1);
      $timeout.flush(100);
      $timeout.flush(100);
      $timeout.flush(100);
      $timeout.flush(100);
      expect(i).toBe(2);
      $timeout.verifyNoPendingTasks();
    });

  });

});
