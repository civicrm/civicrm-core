'use strict';
/* global AbortController, DOMException */

describe('CRM.api4', function() {
  let requests;

  function makeJqXHR(url, data) {
    const deferred = CRM.$.Deferred();
    const promise = deferred.promise();
    promise.aborted = false;
    promise.abort = function() {
      promise.aborted = true;
      deferred.reject({responseJSON: {error_message: 'aborted'}});
    };
    requests.push({url, data, deferred, promise});
    return promise;
  }

  // Flush microtasks queued by native Promises
  async function flushPromises() {
    await Promise.resolve();
    await Promise.resolve();
    await Promise.resolve();
  }

  async function expectRejection(promise) {
    let rejected = false;
    let error;
    try {
      await promise;
    }
    catch (err) {
      rejected = true;
      error = err;
    }
    expect(rejected).toBe(true);
    return error;
  }

  beforeEach(function() {
    requests = [];
    jasmine.clock().install();
    jasmine.clock().mockDate(new Date(1000000));
    CRM.$(window).trigger('crmFormSuccess');
    spyOn(CRM, 'url').and.callFake((path) => '/' + path);
    spyOn(CRM.$, 'post').and.callFake(makeJqXHR);
  });

  afterEach(function() {
    CRM.$(window).trigger('crmFormSuccess');
    jasmine.clock().uninstall();
  });

  it('formats single-call responses as arrayObject', async function() {
    const promise = CRM.api4('Contact', 'get', {limit: 1});
    expect(requests.length).toBe(1);
    expect(requests[0].url).toBe('/civicrm/ajax/api4/Contact/get');
    expect(JSON.parse(requests[0].data.params)).toEqual({limit: 1});

    requests[0].deferred.resolve({values: [{id: 10}], count: 42});
    const result = await promise;
    expect(Array.isArray(result)).toBe(true);
    expect(result.length).toBe(1);
    expect(result[0]).toEqual({id: 10});
    expect(result.count).toBe(42);
  });

  describe('config / index normalization', function() {
    it('accepts scalar and array index values without warning', async function() {
      spyOn(console, 'warn');

      const p1 = CRM.api4('Contact', 'get', {limit: 1}, 0);
      expect(requests[0].data.index).toBe(0);
      requests[0].deferred.resolve({values: [{id: 1}]});
      await p1;

      const p2 = CRM.api4('Contact', 'get', {limit: 1}, ['display_name']);
      expect(requests[1].data.index).toEqual(['display_name']);
      requests[1].deferred.resolve({values: ['Alice']});
      await p2;

      const p3 = CRM.api4('Contact', 'get', {limit: 1}, {index: 'name'});
      expect(requests[2].data.index).toBe('name');
      requests[2].deferred.resolve({values: []});
      await p3;

      expect(console.warn).not.toHaveBeenCalled();
    });

    it('warns and falls back to index when given a plain object with unrecognized keys', async function() {
      spyOn(console, 'warn');

      const p = CRM.api4('Contact', 'get', {limit: 1}, {name: 'title'});
      expect(console.warn).toHaveBeenCalledTimes(1);
      expect(requests[0].data.index).toEqual({name: 'title'});
      requests[0].deferred.resolve({values: []});
      await p;
    });
  });

  describe('multi-call mode', function() {
    it('sends calls bundle and formats each result', async function() {
      const p = CRM.api4({
        contacts: ['Contact', 'get', {limit: 1}],
        tags: ['Tag', 'get', {limit: 2}]
      });
      expect(requests.length).toBe(1);
      expect(requests[0].url).toBe('/civicrm/ajax/api4');
      requests[0].deferred.resolve({
        contacts: {values: [{id: 1}], count: 1},
        tags: {values: [{id: 2}, {id: 3}], count: 2}
      });

      const res = await p;
      expect(res.contacts.count).toBe(1);
      expect(res.tags.length).toBe(2);
    });

    it('invalidates cached reads when a multi-call contains a write', async function() {
      const r1 = CRM.api4('Contact', 'get', {where: [['id', '=', 1]]}, {cache: true});
      requests[0].deferred.resolve({values: [{id: 1}]});
      await r1;

      const w = CRM.api4([
        ['Contact', 'get', {}],
        ['Contact', 'update', {values: {first_name: 'Bob'}}]
      ]);
      requests[1].deferred.resolve([{values: []}, {values: []}]);
      await w;

      const r2 = CRM.api4('Contact', 'get', {where: [['id', '=', 1]]}, {cache: true});
      expect(requests.length).toBe(3);
      requests[2].deferred.resolve({values: [{id: 1}]});
      await r2;
    });
  });

  describe('in-flight deduplication and TTL caching', function() {
    it('deduplicates concurrent read-only calls by default without retaining after completion', async function() {
      const p1 = CRM.api4('Contact', 'get', {where: [['id', '=', 5]]});
      const p2 = CRM.api4('Contact', 'get', {where: [['id', '=', 5]]});
      const p3 = CRM.api4('Contact', 'get', {where: [['id', '=', 5]]}, {cache: false});
      expect(requests.length).toBe(2);

      requests[0].deferred.resolve({values: [{id: 5}], count: 1});
      requests[1].deferred.resolve({values: [{id: 5, fresh: true}], count: 1});
      const [r1, r2, r3] = await Promise.all([p1, p2, p3]);
      expect(r1[0]).toEqual({id: 5});
      expect(r2[0]).toEqual({id: 5});
      expect(r3[0]).toEqual({id: 5, fresh: true});

      // Default cache: 0 does not retain after completion
      const p4 = CRM.api4('Contact', 'get', {where: [['id', '=', 5]]});
      expect(requests.length).toBe(3);
      requests[2].deferred.resolve({values: [{id: 5}]});
      await p4;
    });

    it('treats autocomplete, checkAccess, SearchDisplay.run, and read-only chains as cacheable', async function() {
      const a1 = CRM.api4('Contact', 'autocomplete', {input: 'al'});
      const a2 = CRM.api4('Contact', 'autocomplete', {input: 'al'});
      const c1 = CRM.api4('Contact', 'checkAccess', {action: 'create'});
      const c2 = CRM.api4('Contact', 'checkAccess', {action: 'create'});
      const s1 = CRM.api4('SearchDisplay', 'run', {savedSearch: 'S'});
      const s2 = CRM.api4('SearchDisplay', 'run', {savedSearch: 'S'});
      const ch1 = CRM.api4('Contact', 'get', {chain: {emails: ['Email', 'get', {contact_id: '$id'}]}});
      const ch2 = CRM.api4('Contact', 'get', {chain: {emails: ['Email', 'get', {contact_id: '$id'}]}});
      expect(requests.length).toBe(4);

      requests.forEach((r) => r.deferred.resolve({values: []}));
      await Promise.all([a1, a2, c1, c2, s1, s2, ch1, ch2]);
    });

    it('does not cache writes or get calls with non-get chained actions', async function() {
      const w1 = CRM.api4('Contact', 'create', {values: {first_name: 'A'}});
      const w2 = CRM.api4('Contact', 'create', {values: {first_name: 'A'}});
      const ch1 = CRM.api4('Contact', 'get', {chain: {note: ['Note', 'create', {entity_id: '$id'}]}});
      const ch2 = CRM.api4('Contact', 'get', {chain: {note: ['Note', 'create', {entity_id: '$id'}]}});
      expect(requests.length).toBe(4);

      requests.forEach((r) => r.deferred.resolve({values: []}));
      await Promise.all([w1, w2, ch1, ch2]);
    });

    it('caches for 60000ms with cache: true and for custom ms with numeric cache', async function() {
      const p1 = CRM.api4('Contact', 'get', {id: 1}, {cache: true});
      requests[0].deferred.resolve({values: [{id: 1}], count: 1});
      await p1;

      jasmine.clock().tick(59999);
      const p2 = await CRM.api4('Contact', 'get', {id: 1}, {cache: true});
      expect(requests.length).toBe(1);
      expect(p2.count).toBe(1);

      jasmine.clock().tick(2);
      const p3 = CRM.api4('Contact', 'get', {id: 1}, {cache: true});
      expect(requests.length).toBe(2);
      requests[1].deferred.resolve({values: [{id: 1}]});
      await p3;

      const m1 = CRM.api4('Contact', 'get', {id: 2}, {cache: 500});
      requests[2].deferred.resolve({values: [{id: 2}]});
      await m1;

      jasmine.clock().tick(400);
      await CRM.api4('Contact', 'get', {id: 2}, {cache: 500});
      expect(requests.length).toBe(3);

      jasmine.clock().tick(200);
      const m2 = CRM.api4('Contact', 'get', {id: 2}, {cache: 500});
      expect(requests.length).toBe(4);
      requests[3].deferred.resolve({values: [{id: 2}]});
      await m2;
    });

    it('invalidates cache on writes, form success events, and guards against stale in-flight writes', async function() {
      const p1 = CRM.api4('Contact', 'get', {id: 9}, {cache: true});
      requests[0].deferred.resolve({values: [{id: 9}]});
      await p1;

      // In-flight read started before crmPopupFormSuccess must not populate responseCache
      CRM.$(window).trigger('crmPopupFormSuccess');
      const p2 = CRM.api4('Contact', 'get', {id: 9}, {cache: true});
      expect(requests.length).toBe(2);

      const w = CRM.api4('Contact', 'delete', {where: [['id', '=', 9]]});
      expect(requests.length).toBe(3);

      requests[1].deferred.resolve({values: [{id: 9, stale: true}]});
      requests[2].deferred.resolve({values: []});
      await Promise.all([p2, w]);

      const p3 = CRM.api4('Contact', 'get', {id: 9}, {cache: true});
      expect(requests.length).toBe(4);
      requests[3].deferred.resolve({values: [{id: 9, fresh: true}]});
      const r3 = await p3;
      expect(r3[0]).toEqual({id: 9, fresh: true});
    });
  });

  describe('delayed batching', function() {
    it('batches calls with delay: true (800ms) and shortens timer for smaller numeric values', async function() {
      const b1 = CRM.api4('SearchDisplay', 'run', () => ({display: 'a'}), {delay: true, index: 'id'});
      const b2 = CRM.api4('SearchDisplay', 'run', () => ({display: 'b'}), {delay: 100});
      expect(requests.length).toBe(0);

      jasmine.clock().tick(99);
      expect(requests.length).toBe(0);

      jasmine.clock().tick(1);
      expect(requests.length).toBe(1);
      expect(requests[0].url).toBe('/civicrm/ajax/api4');
      expect(JSON.parse(requests[0].data.calls)).toEqual([
        ['SearchDisplay', 'run', {display: 'a'}, 'id'],
        ['SearchDisplay', 'run', {display: 'b'}]
      ]);

      requests[0].deferred.resolve([
        {values: [{id: 1}], count: 1},
        {error_code: 500, error_message: 'Boom'}
      ]);

      expect((await b1).count).toBe(1);
      const err = await expectRejection(b2);
      expect(err.error_code).toBe(500);
      expect(err.error_message).toBe('Boom');
    });

    it('rejects only the caller whose lazy params function throws', async function() {
      const b1 = CRM.api4('Contact', 'get', () => ({limit: 1}), {delay: 50});
      const b2 = CRM.api4('Contact', 'get', () => {
        throw new Error('cancelled');
      }, {delay: 50});

      jasmine.clock().tick(50);
      expect(requests.length).toBe(1);
      expect(JSON.parse(requests[0].data.calls)).toEqual([['Contact', 'get', {limit: 1}]]);

      requests[0].deferred.resolve([{values: [{id: 7}]}]);
      expect((await b1)[0]).toEqual({id: 7});
      const err = await expectRejection(b2);
      expect(err.message).toBe('cancelled');
    });
  });

  describe('AbortSignal', function() {
    it('rejects immediately if signal is already aborted', async function() {
      const controller = new AbortController();
      controller.abort();

      const err = await expectRejection(CRM.api4('Contact', 'get', {}, {signal: controller.signal}));
      expect(err instanceof DOMException).toBe(true);
      expect(err.name).toBe('AbortError');
      expect(requests.length).toBe(0);
    });

    it('removes aborted call from delayed queue before send', async function() {
      const controller = new AbortController();
      const p = CRM.api4('Contact', 'get', {q: 1}, {delay: 100, signal: controller.signal});

      controller.abort();
      const err = await expectRejection(p);
      expect(err instanceof DOMException).toBe(true);
      expect(err.name).toBe('AbortError');

      jasmine.clock().tick(200);
      expect(requests.length).toBe(0);
    });

    it('aborts standalone in-flight jqXHR, but preserves shared jqXHR when other subscribers remain', async function() {
      const c1 = new AbortController();
      const standalone = CRM.api4('Contact', 'get', {a: 1}, {signal: c1.signal});
      c1.abort();
      const err1 = await expectRejection(standalone);
      expect(err1 instanceof DOMException).toBe(true);
      expect(requests[0].promise.aborted).toBe(true);

      const c2 = new AbortController();
      const shared1 = CRM.api4('Contact', 'get', {s: 1}, {signal: c2.signal});
      const shared2 = CRM.api4('Contact', 'get', {s: 1});
      c2.abort();
      const err2 = await expectRejection(shared1);
      expect(err2 instanceof DOMException).toBe(true);
      expect(requests[1].promise.aborted).toBe(false);

      requests[1].deferred.resolve({values: [{id: 99}]});
      await flushPromises();
      expect((await shared2)[0]).toEqual({id: 99});
    });
  });
});
