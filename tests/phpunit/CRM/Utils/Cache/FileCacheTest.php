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
 * Exercise the FileCache read/write path.
 *
 * @group headless
 */
class CRM_Utils_Cache_FileCacheTest extends CiviUnitTestCase {

  /**
   * Caches created by the test, so they can be cleared afterwards.
   *
   * @var \CRM_Utils_Cache_FileCache[]
   */
  private array $caches = [];

  /**
   * Prefix for this test's cache group.
   *
   * @var string
   */
  private string $prefix;

  /**
   * Directory used by this test's cache group, with a trailing slash.
   *
   * @var string
   */
  private string $groupDir;

  protected function setUp(): void {
    parent::setUp();
    $this->prefix = 'FileCacheTest';
    $this->groupDir = rtrim(Civi::paths()->getPath('[civicrm.private]/filecache'), '/') . '/' . $this->prefix . '/';
    $this->caches = [];
    // A crashed earlier run could leave this group behind.
    $this->createCache()->clear();
  }

  protected function tearDown(): void {
    foreach ($this->caches as $cache) {
      $cache->clear();
    }
    parent::tearDown();
  }

  public function testGetDoesNotCreateCacheDirectory(): void {
    $cache = $this->createCache();

    $this->assertNull($cache->get('foo'));
    $this->assertDirectoryDoesNotExist($this->groupDir);
  }

  public function testSetWritesFileWithoutLeavingTemporaryFile(): void {
    $cache = $this->createCache();

    $this->assertTrue($cache->set('foo', 'bar'));
    $this->assertFileExists($this->groupDir . 'foo.txt');
    $this->assertSame([], glob($this->groupDir . '*.tmp') ?: []);
  }

  public function testSetFailsWhenCsprngIsUnavailable(): void {
    $cache = $this->createCacheWithCsprngFailure();

    $this->assertFalse($cache->set('foo', 'bar'));
    $this->assertFileDoesNotExist($this->groupDir . 'foo.txt');
    $this->assertSame([], glob($this->groupDir . '*.tmp') ?: []);
  }

  public function testWriteRetriesRenameAfterConcurrentClear(): void {
    $cache = $this->createCacheWithRenameFailures(1, TRUE);

    $this->assertTrue($cache->set('foo', 'bar'));
    $this->assertSame(2, $cache->attempts);
    $this->assertSame('bar', $this->createCache()->get('foo'));
    $this->assertSame([], glob($this->groupDir . '*.tmp') ?: []);
  }

  public function testWriteDoesNotRetryRenameForOtherFailures(): void {
    $cache = $this->createCacheWithRenameFailures(1);

    $this->assertFalse($cache->set('foo', 'bar'));
    $this->assertSame(1, $cache->attempts);
    $this->assertFileDoesNotExist($this->groupDir . 'foo.txt');
    $this->assertSame([], glob($this->groupDir . '*.tmp') ?: []);
  }

  public function testWriteFailsWhenRenameKeepsFailing(): void {
    $cache = $this->createCacheWithRenameFailures(2, TRUE);

    $this->assertFalse($cache->set('foo', 'bar'));
    $this->assertSame(2, $cache->attempts);
    $this->assertFileDoesNotExist($this->groupDir . 'foo.txt');
    $this->assertSame([], glob($this->groupDir . '*.tmp') ?: []);
  }

  public function testSetAfterClearRecreatesDirectory(): void {
    $cache = $this->createCache();
    $cache->set('foo', 'bar');
    $cache->clear();

    $this->assertTrue($cache->set('foo', 'baz'));
    $this->assertSame('baz', $this->createCache()->get('foo'));
  }

  public function testSetWhenGroupDirectoryAlreadyExists(): void {
    @mkdir($this->groupDir, 0777, TRUE);
    $cache = $this->createCache();

    $this->assertTrue($cache->set('foo', 'bar'));
    $this->assertSame('bar', $this->createCache()->get('foo'));
  }

  public function testClearWhenDirectoryIsAbsent(): void {
    $this->assertDirectoryDoesNotExist($this->groupDir);

    $this->assertTrue($this->createCache()->clear());
  }

  public function testEntryIsReadableByAnotherInstance(): void {
    $this->createCache()->set('foo', 'bar');

    $this->assertSame('bar', $this->createCache()->get('foo'));
  }

  public function testOrphanTemporaryFileIsIgnoredOnRead(): void {
    $this->createCache()->set('foo', 'bar');
    $orphan = $this->groupDir . 'foo.txt.abcd1234.tmp';
    file_put_contents($orphan, 'partial');

    $this->assertSame('bar', $this->createCache()->get('foo'));
    $this->assertFileExists($orphan);
  }

  public function testGarbageCollectionRemovesOnlyStaleTemporaryFiles(): void {
    $cache = $this->createCache();
    $cache->set('foo', 'bar');

    $freshTemp = $this->groupDir . 'foo.txt.fresh.tmp';
    $staleTemp = $this->groupDir . 'foo.txt.stale.tmp';
    file_put_contents($freshTemp, 'partial');
    file_put_contents($staleTemp, 'partial');
    touch($staleTemp, time() - 7200);

    $cache->garbageCollection();

    $this->assertFileExists($freshTemp);
    $this->assertFileDoesNotExist($staleTemp);
  }

  public function testGarbageCollectionRemovesExpiredEntry(): void {
    $cache = $this->createCache();
    $cache->set('foo', 'bar', 1);
    $path = $this->groupDir . 'foo.txt';
    // Rewrite the entry with an expiry in the past.
    $item = unserialize(file_get_contents($path));
    $item['expires'] = time() - 60;
    file_put_contents($path, serialize($item));

    $this->assertTrue($cache->garbageCollection());
    $this->assertFileDoesNotExist($path);
  }

  public function testDeleteIsIdempotent(): void {
    $cache = $this->createCache();
    $cache->set('foo', 'bar');

    $this->assertTrue($cache->delete('foo'));
    $this->assertFalse($cache->has('foo'));
    $this->assertTrue($cache->delete('foo'));
  }

  public function testClearRemovesTemporaryFiles(): void {
    $cache = $this->createCache();
    $cache->set('foo', 'bar');
    file_put_contents($this->groupDir . 'foo.txt.abcd1234.tmp', 'partial');

    $this->assertTrue($cache->clear());
    $this->assertDirectoryDoesNotExist($this->groupDir);
  }

  private function createCache(array $config = []): CRM_Utils_Cache_FileCache {
    $config += ['prefix' => $this->prefix, 'prefetch' => FALSE];
    $cache = new CRM_Utils_Cache_FileCache($config);
    $this->caches[] = $cache;
    return $cache;
  }

  private function createCacheWithRenameFailures(int $failures, bool $removeDir = FALSE): CRM_Utils_Cache_FileCache {
    $config = ['prefix' => $this->prefix, 'prefetch' => FALSE];
    $cache = new CRM_Utils_Cache_FileCacheTest_RenameFailure($config);
    $cache->failures = $failures;
    $cache->removeDirOnFailure = $removeDir;
    $this->caches[] = $cache;
    return $cache;
  }

  private function createCacheWithCsprngFailure(): CRM_Utils_Cache_FileCache {
    $config = ['prefix' => $this->prefix, 'prefetch' => FALSE];
    $cache = new CRM_Utils_Cache_FileCacheTest_NoCsprng($config);
    $this->caches[] = $cache;
    return $cache;
  }

}

/**
 * Test double for FileCache that can fail rename() calls.
 */
class CRM_Utils_Cache_FileCacheTest_RenameFailure extends CRM_Utils_Cache_FileCache {

  /**
   * Number of rename() calls to fail before delegating to the real rename.
   *
   * @var int
   */
  public int $failures = 0;

  /**
   * Whether a simulated failure also removes the temp file and directory.
   *
   * @var bool
   */
  public bool $removeDirOnFailure = FALSE;

  /**
   * Number of rename() attempts made.
   *
   * @var int
   */
  public int $attempts = 0;

  protected function renameFile(string $from, string $to): bool {
    $this->attempts++;
    if ($this->failures > 0) {
      $this->failures--;
      if ($this->removeDirOnFailure) {
        @unlink($from);
        @rmdir(dirname($from));
      }
      return FALSE;
    }
    return parent::renameFile($from, $to);
  }

}

/**
 * Test double for FileCache that cannot generate a temp suffix.
 */
class CRM_Utils_Cache_FileCacheTest_NoCsprng extends CRM_Utils_Cache_FileCache {

  protected function randomSuffix(): string {
    throw new \RuntimeException('CSPRNG unavailable');
  }

}
