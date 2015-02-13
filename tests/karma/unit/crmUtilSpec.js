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

          // call a second time, but now the data is cached cached
          $q.when(crmMetadata.getFields('MyEntity')).then(
            function(fields) {
              expect(fields.id.title).toBe('My entity ID');
              expect(apiSpy.calls.count()).toBe(1);

              // third call
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
});
