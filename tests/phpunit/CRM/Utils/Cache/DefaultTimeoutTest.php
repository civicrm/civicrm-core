<?php

/**
 * A cache's configured timeout (CIVICRM_DB_CACHE_TIMEOUT) is the TTL of items set without one.
 *
 * @group headless
 */
class CRM_Utils_Cache_DefaultTimeoutTest extends CiviUnitTestCase {

  private const FAKE_REDIS_CONNECTION = 'connect:fake-redis.invalid:6379';

  private ?CRM_Utils_Cache_FileCache $fileCache = NULL;

  public function tearDown(): void {
    $this->fileCache?->clear();
    unset(Civi::$statics['CRM_Utils_Cache_Redis'][self::FAKE_REDIS_CONNECTION]);
    parent::tearDown();
  }

  public function testFileCacheUsesConfiguredTimeout(): void {
    $config = ['timeout' => 60, 'prefix' => 'DefaultTimeoutTest', 'prefetch' => FALSE];
    $this->fileCache = new CRM_Utils_Cache_FileCache($config);
    $this->fileCache->set('foo', 'bar');
    $file = Civi::paths()->getPath('[civicrm.private]/filecache') . '/DefaultTimeoutTest/foo.txt';
    $item = unserialize(file_get_contents($file));

    $this->assertEqualsWithDelta(time() + 60, $item['expires'], 2);
  }

  public function testRedisUsesConfiguredTimeout(): void {
    $redis = new class() {
      public array $setex = [];

      public function setex($key, $ttl, $value): bool {
        $this->setex[$key] = $ttl;
        return TRUE;
      }

    };
    Civi::$statics['CRM_Utils_Cache_Redis'][self::FAKE_REDIS_CONNECTION] = $redis;

    $cache = new CRM_Utils_Cache_Redis(['host' => 'fake-redis.invalid', 'port' => 6379, 'timeout' => 60]);
    $cache->set('foo', 'bar');

    $this->assertSame([60], array_values($redis->setex));
  }

}
