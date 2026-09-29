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
 * Verify that CRM_Utils_Cache_Redis complies with PSR-16.
 *
 * Talks to a real Redis server, independent of the site's cache settings.
 * Defaults to localhost:6379; override with the CIVICRM_TEST_REDIS_HOST and
 * CIVICRM_TEST_REDIS_PORT environment variables. Skipped if the phpredis
 * extension is missing or the server is unreachable.
 *
 * @group e2e
 */
class E2E_Cache_RedisCacheTest extends E2E_Cache_CacheTestCase {

  private function getRedisConfig(): array {
    return [
      'host' => getenv('CIVICRM_TEST_REDIS_HOST') ?: 'localhost',
      'port' => (int) (getenv('CIVICRM_TEST_REDIS_PORT') ?: CRM_Utils_Cache_Redis::DEFAULT_PORT),
      'timeout' => 3600,
    ];
  }

  /**
   * CRM_Utils_Cache_Redis::connect() exits the process on failure, so probe first.
   */
  private function skipUnlessRedisReachable(array $config): void {
    if (!class_exists('Redis')) {
      $this->markTestSkipped('The phpredis extension is not loaded.');
    }
    try {
      $probe = new Redis();
      $reachable = $probe->connect($config['host'], $config['port'], 1.0);
    }
    catch (RedisException $e) {
      $reachable = FALSE;
    }
    if (!$reachable) {
      $this->markTestSkipped("No Redis server reachable at {$config['host']}:{$config['port']}.");
    }
  }

  private function createRedisCache(string $prefix): CRM_Utils_Cache_Redis {
    $config = $this->getRedisConfig() + ['prefix' => $prefix];
    $this->skipUnlessRedisReachable($config);
    return new CRM_Utils_Cache_Redis($config);
  }

  public function createSimpleCache() {
    return $this->createRedisCache('e2e_rediscache_test');
  }

  /**
   * Whatever the batch methods return must match what single-key calls return.
   */
  public function testMultipleMethodsAgreeWithSingleKeyMethods(): void {
    $stored = [
      'string' => 'value',
      'zero' => 0,
      'empty' => '',
      'null' => NULL,
      'false' => FALSE,
      'array' => ['a' => [1, 2]],
      'object' => (object) ['x' => 1],
    ];
    foreach ($stored as $key => $value) {
      $this->cache->set($key, $value);
    }
    $keys = array_merge(array_keys($stored), ['missing1', 'missing2']);

    $expected = [];
    foreach ($keys as $key) {
      $expected[$key] = $this->cache->get($key, 'fallback');
    }
    $this->assertEquals($expected, $this->cache->getMultiple($keys, 'fallback'));
    $this->assertSame(array_keys($expected), array_keys($this->cache->getMultiple($keys, 'fallback')));

    foreach ($keys as $key) {
      $this->assertSame(array_key_exists($key, $stored), $this->cache->has($key), "has($key)");
    }
  }

  public function testHasIsTrueForStoredFalseAndNull(): void {
    $this->cache->set('stored_false', FALSE);
    $this->cache->set('stored_null', NULL);
    $this->assertTrue($this->cache->has('stored_false'));
    $this->assertTrue($this->cache->has('stored_null'));
  }

  public function testHasIsFalseAfterExpiry(): void {
    $this->cache->set('short_lived', 'value', 1);
    $this->assertTrue($this->cache->has('short_lived'));
    $this->advanceTime(2);
    $this->assertFalse($this->cache->has('short_lived'));
  }

  public function testGetMultipleWithDuplicateAndIntegerLikeKeys(): void {
    $this->cache->set('123', 'numeric');
    $this->cache->set('dup', 'once');
    $result = $this->cache->getMultiple(['123', 'dup', 'dup']);
    $this->assertEquals(['123' => 'numeric', 'dup' => 'once'], $result);
  }

  public function testGetMultipleWithNoKeys(): void {
    $this->assertSame([], $this->cache->getMultiple([]));
  }

  public function testDeleteMultipleLeavesOtherKeysAlone(): void {
    $this->cache->setMultiple(['a' => 1, 'b' => 2, 'c' => 3]);
    $this->assertTrue($this->cache->deleteMultiple(['a', 'c', 'never_existed']));
    $this->assertFalse($this->cache->has('a'));
    $this->assertTrue($this->cache->has('b'));
    $this->assertFalse($this->cache->has('c'));
  }

  public function testPrefixesIsolateInstances(): void {
    $other = $this->createRedisCache('e2e_rediscache_other');
    try {
      $this->cache->setMultiple(['k1' => 'mine', 'k2' => 'mine']);
      $other->set('k1', 'theirs');

      $this->assertSame(['k1' => 'mine', 'k2' => 'mine'], $this->cache->getMultiple(['k1', 'k2']));
      $this->assertSame(['k1' => 'theirs', 'k2' => NULL], $other->getMultiple(['k1', 'k2']));
      $this->assertFalse($other->has('k2'));

      $this->cache->deleteMultiple(['k1', 'k2']);
      $this->assertTrue($other->has('k1'));
    }
    finally {
      $other->clear();
    }
  }

}
