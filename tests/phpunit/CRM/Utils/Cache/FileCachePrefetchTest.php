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
 * CRM_Utils_Cache::create() should forward the caller's prefetch option.
 *
 * @group headless
 */
class CRM_Utils_Cache_FileCachePrefetchTest extends CiviUnitTestCase {

  /**
   * Caches created by the test, so they can be cleared afterwards.
   *
   * @var \CRM_Utils_Cache_FileCache[]
   */
  private array $caches = [];

  protected function setUp(): void {
    parent::setUp();
    $this->caches = [];
  }

  protected function tearDown(): void {
    foreach ($this->caches as $cache) {
      $cache->clear();
    }
    parent::tearDown();
  }

  public function testCreateForwardsPrefetchOption(): void {
    $name = 'FileCachePrefetchTest_' . uniqid();

    $writer = $this->unwrapFileCache(CRM_Utils_Cache::create([
      'name' => $name,
      'type' => ['FileCache'],
    ]));
    $this->caches[] = $writer;
    $writer->set('foo', 'bar');

    $reader = $this->unwrapFileCache(CRM_Utils_Cache::create([
      'name' => $name,
      'type' => ['FileCache'],
      'prefetch' => TRUE,
    ]));
    $this->caches[] = $reader;
    $values = $this->readPrefetchedValues($reader);
    $this->assertCount(1, $values);
    $this->assertSame('bar', $values[$this->inMemoryKey($reader, 'foo')] ?? NULL);

    // The driver default is still TRUE, so an explicit FALSE only takes effect
    // when create() forwards it.
    $noPrefetch = $this->unwrapFileCache(CRM_Utils_Cache::create([
      'name' => $name,
      'type' => ['FileCache'],
      'prefetch' => FALSE,
    ]));
    $this->caches[] = $noPrefetch;
    $this->assertSame([], $this->readPrefetchedValues($noPrefetch));
    $this->assertSame('bar', $noPrefetch->get('foo'));
  }

  /**
   * @param mixed $cache
   */
  private function unwrapFileCache($cache): CRM_Utils_Cache_FileCache {
    if ($cache instanceof CRM_Utils_Cache_CacheWrapper) {
      $cache = (new ReflectionProperty(CRM_Utils_Cache_CacheWrapper::class, 'delegate'))->getValue($cache);
    }
    $this->assertInstanceOf(CRM_Utils_Cache_FileCache::class, $cache);
    return $cache;
  }

  /**
   * Read the values that prefetch() loaded into memory.
   */
  private function readPrefetchedValues(CRM_Utils_Cache_FileCache $cache): array {
    return (new ReflectionProperty(CRM_Utils_Cache_FileCache::class, 'valueCache'))->getValue($cache);
  }

  /**
   * The key used for the in-memory value cache (prefix plus cleaned key).
   */
  private function inMemoryKey(CRM_Utils_Cache_FileCache $cache, string $key): string {
    $prefix = (new ReflectionProperty(CRM_Utils_Cache_FileCache::class, '_prefix'))->getValue($cache);
    return $prefix . $key;
  }

}
