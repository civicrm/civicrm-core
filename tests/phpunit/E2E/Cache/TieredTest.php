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
 * Verify that CRM_Utils_Cache_Tiered complies with PSR-16.
 *
 * @group e2e
 */
class E2E_Cache_TieredTest extends E2E_Cache_CacheTestCase {
  const TOLERANCE = 5;

  /**
   * @var CRM_Utils_Cache_ArrayCache
   */
  protected $a;

  /**
   * @var CRM_Utils_Cache_ArrayCache
   */
  protected $b;

  protected function tearDown() {
    if (function_exists('timecop_return')) {
      timecop_return();
    }
    parent::tearDown();
  }

  public function createSimpleCache($maxTimeouts = [86400]) {
    return new CRM_Utils_Cache_Tiered([
      $this->a = CRM_Utils_Cache::create([
        'name' => 'e2e tiered test a',
        'type' => ['ArrayCache'],
      ]),
      $this->b = CRM_Utils_Cache::create([
        'name' => 'e2e tiered test b',
        'type' => ['ArrayCache'],
      ]),
    ], $maxTimeouts);
  }

  public function testDoubleLifeWithDelete() {
    $this->assertFalse($this->a->has('foo'));
    $this->assertFalse($this->b->has('foo'));
    $this->assertEquals('dfl-1', $this->a->get('foo', 'dfl-1'));
    $this->assertEquals('dfl-2', $this->b->get('foo', 'dfl-2'));

    $this->cache->set('foo', 100);

    $this->assertTrue($this->a->has('foo'));
    $this->assertTrue($this->b->has('foo'));
    $this->assertEquals(100, $this->a->get('foo', 'dfl-1')[1]);
    $this->assertEquals(100, $this->b->get('foo', 'dfl-2')[1]);
    $this->assertEquals($this->a->get('foo'), $this->b->get('foo'));

    $this->cache->set('foo', 200);

    $this->assertTrue($this->a->has('foo'));
    $this->assertTrue($this->b->has('foo'));
    $this->assertEquals(200, $this->a->get('foo', 'dfl-1')[1]);
    $this->assertEquals(200, $this->b->get('foo', 'dfl-2')[1]);
    $this->assertEquals($this->a->get('foo'), $this->b->get('foo'));

    $this->cache->delete('foo');

    $this->assertFalse($this->a->has('foo'));
    $this->assertFalse($this->b->has('foo'));
    $this->assertEquals('dfl-1', $this->a->get('foo', 'dfl-1'));
    $this->assertEquals('dfl-2', $this->b->get('foo', 'dfl-2'));
  }

  public function testDoubleLifeWithClear() {
    $this->assertFalse($this->a->has('foo'));
    $this->assertFalse($this->b->has('foo'));
    $this->assertEquals('dfl-1', $this->a->get('foo', 'dfl-1'));
    $this->assertEquals('dfl-2', $this->b->get('foo', 'dfl-2'));

    $this->cache->set('foo', 100);

    $this->assertTrue($this->a->has('foo'));
    $this->assertTrue($this->b->has('foo'));
    $this->assertEquals(100, $this->a->get('foo', 'dfl-1')[1]);
    $this->assertEquals(100, $this->b->get('foo', 'dfl-2')[1]);
    $this->assertEquals($this->a->get('foo'), $this->b->get('foo'));

    $this->cache->clear();

    $this->assertFalse($this->a->has('foo'));
    $this->assertFalse($this->b->has('foo'));
    $this->assertEquals('dfl-1', $this->a->get('foo', 'dfl-1'));
    $this->assertEquals('dfl-2', $this->b->get('foo', 'dfl-2'));
  }

  public function testTieredTimeout_default() {
    $start = CRM_Utils_Time::getTimeRaw();
    $this->cache = $this->createSimpleCache([100, 1000]);

    $this->cache->set('foo', 'bar');
    $this->assertApproxEquals($start + 100, $this->a->getExpires('foo'), self::TOLERANCE);
    $this->assertApproxEquals($start + 1000, $this->b->getExpires('foo'), self::TOLERANCE);

    // Simulate expiration & repopulation in nearest tier.

    $this->a->clear();
    $this->assertApproxEquals(NULL, $this->a->getExpires('foo'), self::TOLERANCE);
    $this->assertApproxEquals($start + 1000, $this->b->getExpires('foo'), self::TOLERANCE);

    $this->assertEquals('bar', $this->cache->get('foo'));
    $this->assertApproxEquals($start + 100, $this->a->getExpires('foo'), self::TOLERANCE);
    $this->assertApproxEquals($start + 1000, $this->b->getExpires('foo'), self::TOLERANCE);
  }

  public function testTieredTimeout_explicitLow() {
    $start = CRM_Utils_Time::getTimeRaw();
    $this->cache = $this->createSimpleCache([100, 1000]);

    $this->cache->set('foo', 'bar', 50);
    $this->assertApproxEquals($start + 50, $this->a->getExpires('foo'), self::TOLERANCE);
    $this->assertApproxEquals($start + 50, $this->b->getExpires('foo'), self::TOLERANCE);

    // Simulate expiration & repopulation in nearest tier.

    $this->a->clear();
    $this->assertApproxEquals(NULL, $this->a->getExpires('foo'), self::TOLERANCE);
    $this->assertApproxEquals($start + 50, $this->b->getExpires('foo'), self::TOLERANCE);

    $this->assertEquals('bar', $this->cache->get('foo'));
    $this->assertApproxEquals($start + 50, $this->a->getExpires('foo'), self::TOLERANCE);
    $this->assertApproxEquals($start + 50, $this->b->getExpires('foo'), self::TOLERANCE);
  }

  public function testTieredTimeout_explicitMedium() {
    $start = CRM_Utils_Time::getTimeRaw();
    $this->cache = $this->createSimpleCache([100, 1000]);

    $this->cache->set('foo', 'bar', 500);
    $this->assertApproxEquals($start + 100, $this->a->getExpires('foo'), self::TOLERANCE);
    $this->assertApproxEquals($start + 500, $this->b->getExpires('foo'), self::TOLERANCE);

    // Simulate expiration & repopulation in nearest tier.

    $this->a->clear();
    $this->assertApproxEquals(NULL, $this->a->getExpires('foo'), self::TOLERANCE);
    $this->assertApproxEquals($start + 500, $this->b->getExpires('foo'), self::TOLERANCE);

    $this->assertEquals('bar', $this->cache->get('foo'));
    $this->assertApproxEquals($start + 100, $this->a->getExpires('foo'), self::TOLERANCE);
    $this->assertApproxEquals($start + 500, $this->b->getExpires('foo'), self::TOLERANCE);
  }

  public function testTieredTimeout_explicitHigh_lateReoad() {
    $start = CRM_Utils_Time::getTimeRaw();
    $this->cache = $this->createSimpleCache([100, 1000]);

    $this->cache->set('foo', 'bar', 5000);
    $this->assertApproxEquals($start + 100, $this->a->getExpires('foo'), self::TOLERANCE);
    $this->assertApproxEquals($start + 1000, $this->b->getExpires('foo'), self::TOLERANCE);

    // Simulate expiration & repopulation in nearest tier.

    $this->a->clear();
    $this->assertApproxEquals(NULL, $this->a->getExpires('foo'), self::TOLERANCE);
    $this->assertApproxEquals($start + 1000, $this->b->getExpires('foo'), self::TOLERANCE);

    function_exists('timecop_return') ? timecop_travel(time() + self::TOLERANCE) : sleep(self::TOLERANCE);

    $this->assertEquals('bar', $this->cache->get('foo'));
    $this->assertApproxEquals($start + 100 + self::TOLERANCE, $this->a->getExpires('foo'), self::TOLERANCE);
    $this->assertApproxEquals($start + 1000, $this->b->getExpires('foo'), self::TOLERANCE);
  }

  /**
   * Assert that two numbers are approximately equal.
   *
   * @param int|float $expected
   * @param int|float $actual
   * @param int|float $tolerance
   * @param string $message
   */
  public function assertApproxEquals($expected, $actual, $tolerance, $message = NULL) {
    if ($message === NULL) {
      $message = sprintf("approx-equals: expected=[%.3f] actual=[%.3f] tolerance=[%.3f]", $expected, $actual, $tolerance);
    }
    $this->assertTrue(abs($actual - $expected) < $tolerance, $message);
  }

}
