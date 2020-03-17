<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * Verify that CRM_Utils_Cache_ArrayDecorator complies with PSR-16.
 *
 * @group e2e
 */
class E2E_Cache_ArrayDecoratorTest extends E2E_Cache_CacheTestCase {

  /**
   * @var CRM_Utils_Cache_Interface
   */
  protected $a;

  public function createSimpleCache() {
    return new CRM_Utils_Cache_ArrayDecorator(
      $this->a = CRM_Utils_Cache::create([
        'name' => 'e2e array-dec test',
        'type' => ['ArrayCache'],
      ])
    );
  }

  public function testDoubleLifeWithDelete() {
    $this->assertFalse($this->a->has('foo'));
    $this->assertEquals('dfl-1', $this->a->get('foo', 'dfl-1'));

    $this->cache->set('foo', 100);

    $this->assertTrue($this->a->has('foo'));
    $this->assertEquals(100, $this->a->get('foo', 'dfl-1')[1]);

    $this->cache->set('foo', 200);

    $this->assertTrue($this->a->has('foo'));
    $this->assertEquals(200, $this->a->get('foo', 'dfl-1')[1]);

    $this->cache->delete('foo');

    $this->assertFalse($this->a->has('foo'));
    $this->assertEquals('dfl-1', $this->a->get('foo', 'dfl-1'));
  }

  public function testDoubleLifeWithClear() {
    $this->assertFalse($this->a->has('foo'));
    $this->assertEquals('dfl-1', $this->a->get('foo', 'dfl-1'));

    $this->cache->set('foo', 100);

    $this->assertTrue($this->a->has('foo'));
    $this->assertEquals(100, $this->a->get('foo', 'dfl-1')[1]);

    $this->cache->clear();

    $this->assertFalse($this->a->has('foo'));
    $this->assertEquals('dfl-1', $this->a->get('foo', 'dfl-1'));
  }

  public function testSetTtl() {
    // This test has exhibited some flakiness. It is overridden to
    // dump more detailed information about failures; however, it should be
    // substantively the same.
    if (isset($this->skippedTests[__FUNCTION__])) {
      $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
    }
    $result = $this->cache->set('key1', 'value', 1);
    $this->assertTrue($result, 'set() must return true if success');
    $this->assertEquals('value', $this->cache->get('key1'));
    sleep(2);
    $this->assertNull($this->cache->get('key1'), 'Value must expire after ttl.');

    $this->cache->set('key2', 'value', new \DateInterval('PT1S'));
    $key2Value = $this->cache->get('key2');
    if ($key2Value !== 'value') {
      // dump out contents of cache.
      var_dump($this->cache);
      print_r(date('u') . 'Current UNIX timestamp');
    }
    $this->assertEquals('value', $key2Value);
    sleep(2);
    $this->assertNull($this->cache->get('key2'), 'Value must expire after ttl.');
  }

}
