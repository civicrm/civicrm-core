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
 * FileCache should only read the whole cache group when explicitly asked to.
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

  /**
   * Unique directory for this test, so leftover files cannot affect results.
   *
   * @var string
   */
  private string $prefix;

  protected function setUp(): void {
    parent::setUp();
    $this->prefix = 'FileCachePrefetchTest_' . uniqid();
    $this->caches = [];
  }

  protected function tearDown(): void {
    foreach ($this->caches as $cache) {
      $cache->clear();
    }
    parent::tearDown();
  }

  public function testDefaultDoesNotPrefetch(): void {
    $this->createFileCache()->set('foo', 'bar');

    $cache = $this->createFileCache();

    $this->assertSame([], $this->readPrefetchedValues($cache));
    $this->assertSame('bar', $cache->get('foo'));
  }

  public function testExplicitPrefetchReadsWholeGroup(): void {
    $this->createFileCache()->set('foo', 'bar');

    $cache = $this->createFileCache(['prefetch' => TRUE]);

    $values = $this->readPrefetchedValues($cache);
    $this->assertCount(1, $values);
    $this->assertSame('bar', $values[$this->inMemoryKey($cache, 'foo')] ?? NULL);
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

    $noPrefetch = $this->unwrapFileCache(CRM_Utils_Cache::create([
      'name' => $name,
      'type' => ['FileCache'],
      'prefetch' => FALSE,
    ]));
    $this->caches[] = $noPrefetch;
    $this->assertSame([], $this->readPrefetchedValues($noPrefetch));
    $this->assertSame('bar', $noPrefetch->get('foo'));

    // With no option, the FileCache default applies.
    $defaultReader = $this->unwrapFileCache(CRM_Utils_Cache::create([
      'name' => $name,
      'type' => ['FileCache'],
    ]));
    $this->caches[] = $defaultReader;
    $this->assertSame([], $this->readPrefetchedValues($defaultReader));
  }

  private function createFileCache(array $config = []): CRM_Utils_Cache_FileCache {
    $config += ['prefix' => $this->prefix];
    $cache = new CRM_Utils_Cache_FileCache($config);
    $this->caches[] = $cache;
    return $cache;
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
