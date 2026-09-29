<?php

use PHPUnit\Framework\TestCase;

/**
 * Verify which phpredis commands CRM_Utils_Cache_Redis issues.
 *
 * The phpredis client is mocked, so no Redis server is needed. Behavioural
 * equivalence with a real server is covered by E2E_Cache_RedisCacheTest.
 *
 * @group headless
 */
class CRM_Utils_Cache_RedisTest extends TestCase {

  private const PREFIX = 'testprefix_';

  /**
   * @var \Redis&\PHPUnit\Framework\MockObject\MockObject
   */
  private $redis;

  /**
   * @var \CRM_Utils_Cache_Redis
   */
  private $cache;

  protected function setUp(): void {
    if (!class_exists('Redis')) {
      $this->markTestSkipped('The phpredis extension is not loaded.');
    }
    $this->redis = $this->createMock(Redis::class);

    // The constructor would open a real connection.
    $reflection = new ReflectionClass(CRM_Utils_Cache_Redis::class);
    $this->cache = $reflection->newInstanceWithoutConstructor();
    $reflection->getProperty('_cache')->setValue($this->cache, $this->redis);
    $reflection->getProperty('_prefix')->setValue($this->cache, self::PREFIX);
  }

  public function testHasAsksRedisWithoutFetchingPayload(): void {
    $this->redis->expects($this->once())->method('exists')->with(self::PREFIX . 'foo')->willReturn(1);
    $this->redis->expects($this->never())->method('get');
    $this->assertTrue($this->cache->has('foo'));
  }

  public function testHasIsFalseWhenKeyMissing(): void {
    $this->redis->method('exists')->willReturn(0);
    $this->redis->expects($this->never())->method('get');
    $this->assertFalse($this->cache->has('foo'));
  }

  public function testHasIsFalseWhenRedisReportsError(): void {
    // phpredis returns FALSE rather than throwing, unless OPT_THROW_EXCEPTION is set.
    $this->redis->method('exists')->willReturn(FALSE);
    $this->assertFalse($this->cache->has('foo'));
  }

  public function testHasRejectsInvalidKey(): void {
    $this->redis->expects($this->never())->method('exists');
    $this->expectException(CRM_Utils_Cache_InvalidArgumentException::class);
    $this->cache->has('bad/key');
  }

  public function testGetMultipleIssuesOneMget(): void {
    $this->redis->expects($this->once())
      ->method('mget')
      ->with([self::PREFIX . 'a', self::PREFIX . 'b', self::PREFIX . 'c'])
      ->willReturn([serialize('A'), FALSE, serialize(['C'])]);
    $this->redis->expects($this->never())->method('get');

    $this->assertSame(
      ['a' => 'A', 'b' => 'fallback', 'c' => ['C']],
      $this->cache->getMultiple(['a', 'b', 'c'], 'fallback')
    );
  }

  public function testGetMultipleWithNoKeysSkipsRedis(): void {
    $this->redis->expects($this->never())->method('mget');
    $this->redis->expects($this->never())->method('get');
    $this->assertSame([], $this->cache->getMultiple([]));
  }

  public function testGetMultipleReturnsDefaultsWhenMgetFails(): void {
    $this->redis->method('mget')->willReturn(FALSE);
    $this->assertSame(
      ['a' => 'fallback', 'b' => 'fallback'],
      $this->cache->getMultiple(['a', 'b'], 'fallback')
    );
  }

  public function testGetMultipleAcceptsGeneratorWithRepeatedKeys(): void {
    $keys = function () {
      yield 1 => 'a';
      yield 1 => 'b';
    };
    $this->redis->expects($this->once())
      ->method('mget')
      ->with([self::PREFIX . 'a', self::PREFIX . 'b'])
      ->willReturn([serialize('A'), serialize('B')]);

    $this->assertSame(['a' => 'A', 'b' => 'B'], $this->cache->getMultiple($keys()));
  }

  public function testGetMultipleRejectsInvalidKeyBeforeAskingRedis(): void {
    $this->redis->expects($this->never())->method('mget');
    $this->expectException(CRM_Utils_Cache_InvalidArgumentException::class);
    $this->cache->getMultiple(['good', 'bad/key']);
  }

  public function testGetMultipleRejectsNonIterable(): void {
    $this->redis->expects($this->never())->method('mget');
    $this->expectException(CRM_Utils_Cache_InvalidArgumentException::class);
    $this->cache->getMultiple('not-iterable');
  }

  public function testDeleteMultipleIssuesOneDel(): void {
    $this->redis->expects($this->once())
      ->method('del')
      ->with([self::PREFIX . 'a', self::PREFIX . 'b']);
    $this->assertTrue($this->cache->deleteMultiple(['a', 'b']));
  }

  public function testDeleteMultipleWithNoKeysSkipsRedis(): void {
    $this->redis->expects($this->never())->method('del');
    $this->assertTrue($this->cache->deleteMultiple([]));
  }

  public function testDeleteMultipleRejectsInvalidKeyBeforeAskingRedis(): void {
    $this->redis->expects($this->never())->method('del');
    $this->expectException(CRM_Utils_Cache_InvalidArgumentException::class);
    $this->cache->deleteMultiple(['good', 'bad/key']);
  }

}
