<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License along with this program; if not, contact CiviCRM LLC       |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 * If you make two instances of a cache driver, do they coexist as you would expect?
 *
 * @group e2e
 */
class E2E_Cache_TwoInstancesTest extends CiviEndToEndTestCase {

  /**
   * @var Psr\SimpleCache\CacheInterface;
   */
  protected $a;

  /**
   * @var Psr\SimpleCache\CacheInterface;
   */
  protected $b;

  protected function setUp() {
    parent::setUp();
    $this->a = $this->b = NULL;
  }

  protected function tearDown() {
    parent::tearDown();
    if ($this->a) {
      $this->a->clear();
    }
    if ($this->b) {
      $this->b->clear();
    }
  }

  /**
   * Get a list of cache-creation specs.
   */
  public function getSingleGenerators() {
    $exs = [];
    $exs[] = [
      ['type' => ['SqlGroup'], 'name' => 'TwoInstancesTest_SameSQL'],
    ];
    $exs[] = [
      ['type' => ['*memory*'], 'name' => 'TwoInstancesTest_SameMem'],
    ];
    return $exs;
  }

  /**
   * Add item to one cache instance then read with another.
   *
   * @param array $cacheDef
   *   Cache definition. See CRM_Utils_Cache::create().
   * @dataProvider getSingleGenerators
   */
  public function testSingle_reload($cacheDef) {
    if (!E2E_Cache_ConfiguredMemoryTest::isMemorySupported() && $cacheDef['type'] === ['*memory*']) {
      $this->markTestSkipped('This environment is not configured to use a memory-backed cache service.');
    }

    $a = $this->a = CRM_Utils_Cache::create($cacheDef);
    $a->set('foo', 1234);
    $this->assertEquals(1234, $a->get('foo'));

    $b = $this->b = CRM_Utils_Cache::create($cacheDef + ['prefetch' => TRUE]);
    $this->assertEquals(1234, $b->get('foo'));

    $b = $this->b = CRM_Utils_Cache::create($cacheDef + ['prefetch' => FALSE]);
    $this->assertEquals(1234, $b->get('foo'));
  }

  /**
   * Get a list of distinct cache-creation specs.
   */
  public function getTwoGenerators() {
    $exs = [];
    $exs[] = [
      ['type' => ['SqlGroup'], 'name' => 'testTwo_a'],
      ['type' => ['SqlGroup'], 'name' => 'testTwo_b'],
    ];
    $exs[] = [
      ['type' => ['*memory*'], 'name' => 'testTwo_a'],
      ['type' => ['*memory*'], 'name' => 'testTwo_b'],
    ];
    $exs[] = [
      ['type' => ['*memory*'], 'name' => 'testTwo_drv'],
      ['type' => ['SqlGroup'], 'name' => 'testTwo_drv'],
    ];
    return $exs;
  }

  /**
   * Add items to the two caches. Then clear the first.
   *
   * @param array $cacheA
   *   Cache definition. See CRM_Utils_Cache::create().
   * @param array $cacheB
   *   Cache definition. See CRM_Utils_Cache::create().
   * @dataProvider getTwoGenerators
   */
  public function testDiff_clearA($cacheA, $cacheB) {
    list($a, $b) = $this->createTwoCaches($cacheA, $cacheB);
    $a->set('foo', 1234);
    $b->set('foo', 5678);
    $this->assertEquals(1234, $a->get('foo'), 'Check value A after initial setup');
    $this->assertEquals(5678, $b->get('foo'), 'Check value B after initial setup');

    $a->clear();
    $this->assertEquals(NULL, $a->get('foo'), 'Check value A after clearing A');
    $this->assertEquals(5678, $b->get('foo'), 'Check value B after clearing A');
  }

  /**
   * Add items to the two caches. Then clear the second.
   *
   * @param array $cacheA
   *   Cache definition. See CRM_Utils_Cache::create().
   * @param array $cacheB
   *   Cache definition. See CRM_Utils_Cache::create().
   * @dataProvider getTwoGenerators
   */
  public function testDiff_clearB($cacheA, $cacheB) {
    list($a, $b) = $this->createTwoCaches($cacheA, $cacheB);
    $a->set('foo', 1234);
    $b->set('foo', 5678);
    $this->assertEquals(1234, $a->get('foo'), 'Check value A after initial setup');
    $this->assertEquals(5678, $b->get('foo'), 'Check value B after initial setup');

    $b->clear();
    $this->assertEquals(1234, $a->get('foo'), 'Check value A after clearing B');
    $this->assertEquals(NULL, $b->get('foo'), 'Check value B after clearing B');
  }

  /**
   * Add items to the two caches. Then reload both caches and read from each.
   *
   * @param array $cacheA
   *   Cache definition. See CRM_Utils_Cache::create().
   * @param array $cacheB
   *   Cache definition. See CRM_Utils_Cache::create().
   * @dataProvider getTwoGenerators
   */
  public function testDiff_reload($cacheA, $cacheB) {
    list($a, $b) = $this->createTwoCaches($cacheA, $cacheB);
    $a->set('foo', 1234);
    $b->set('foo', 5678);
    $this->assertEquals(1234, $a->get('foo'), 'Check value A after initial setup');
    $this->assertEquals(5678, $b->get('foo'), 'Check value B after initial setup');

    list($a, $b) = $this->createTwoCaches($cacheA, $cacheB);
    $this->assertEquals(1234, $a->get('foo'), 'Check value A after initial setup');
    $this->assertEquals(5678, $b->get('foo'), 'Check value B after initial setup');
  }

  /**
   * @param $cacheA
   * @param $cacheB
   * @return array
   */
  protected function createTwoCaches($cacheA, $cacheB) {
    if (!E2E_Cache_ConfiguredMemoryTest::isMemorySupported() && $cacheA['type'] === ['*memory*']) {
      $this->markTestSkipped('This environment is not configured to use a memory-backed cache service.');
    }
    if (!E2E_Cache_ConfiguredMemoryTest::isMemorySupported() && $cacheB['type'] === ['*memory*']) {
      $this->markTestSkipped('This environment is not configured to use a memory-backed cache service.');
    }

    $a = $this->a = CRM_Utils_Cache::create($cacheA);
    $b = $this->b = CRM_Utils_Cache::create($cacheB);
    return array($a, $b);
  }

}
