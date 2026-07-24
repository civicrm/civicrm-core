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
 * When the cache-key lock is held by another connection, SqlGroup::set()
 * should skip the write and report FALSE -- not abort the caller.
 *
 * A cache-write is an optimization: the caller has already computed the value
 * and returns it either way. Lock contention is transient, so throwing here
 * turns a self-healing condition into a hard failure on an unrelated request.
 *
 * @group e2e
 */
class E2E_Cache_SqlGroupLockTest extends \CiviEndToEndTestCase {

  const GROUP = 'e2eSqlGroupLockTest';
  const KEY = 'contendedKey';

  /**
   * Second connection used to hold the lock that SqlGroup wants.
   *
   * @var \mysqli|null
   */
  private $blocker;

  public function tearDown(): void {
    if ($this->blocker) {
      $this->blocker->close();
      $this->blocker = NULL;
    }
    CRM_Core_DAO::executeQuery('DELETE FROM civicrm_cache WHERE group_name = %1', [
      1 => [self::GROUP, 'String'],
    ]);
    parent::tearDown();
  }

  public function testSetSkipsWriteWhenLockUnavailable(): void {
    $cache = new CRM_Utils_Cache_SqlGroup(['group' => self::GROUP, 'prefetch' => FALSE]);

    $this->holdLock();
    // CRM_Core_Lock::TIMEOUT applies here, so this call blocks briefly.
    $result = $cache->set(self::KEY, 'value-that-should-not-be-stored');

    $this->assertFalse($result, 'set() should report failure rather than throw.');
    $this->assertEquals(0, $this->countRows(), 'No row should be written while the lock is held.');
  }

  public function testSetSucceedsOnceLockIsReleased(): void {
    $cache = new CRM_Utils_Cache_SqlGroup(['group' => self::GROUP, 'prefetch' => FALSE]);

    $this->holdLock();
    $cache->set(self::KEY, 'first');
    $this->releaseLock();

    $this->assertTrue($cache->set(self::KEY, 'second'));
    $this->assertEquals(1, $this->countRows());
    $this->assertEquals('second', $cache->get(self::KEY));
  }

  /**
   * Grab the same named lock SqlGroup::set() will ask for, on a separate
   * connection (MySQL user-locks are re-entrant within one connection, so the
   * test's own connection cannot block itself).
   */
  private function holdLock(): void {
    $dsn = DB::parseDSN(CIVICRM_DSN);
    $this->blocker = new mysqli(
      $dsn['hostspec'],
      $dsn['username'],
      $dsn['password'],
      $dsn['database'],
      $dsn['port'] ?: NULL
    );
    $this->assertNull($this->blocker->connect_error, 'Could not open a second connection.');

    $stmt = $this->blocker->prepare('SELECT GET_LOCK(?, 0)');
    $lockId = $this->lockId();
    $stmt->bind_param('s', $lockId);
    $stmt->execute();
    $this->assertSame(1, (int) $stmt->get_result()->fetch_row()[0], 'Could not acquire the blocking lock.');
  }

  private function releaseLock(): void {
    $stmt = $this->blocker->prepare('SELECT RELEASE_LOCK(?)');
    $lockId = $this->lockId();
    $stmt->bind_param('s', $lockId);
    $stmt->execute();
    $stmt->get_result();
  }

  /**
   * Mirror the name/hash scheme of CRM_Core_Lock::__construct().
   */
  private function lockId(): string {
    $dsn = DB::parseDSN(CIVICRM_DSN);
    $name = 'cache.' . self::GROUP . '_' . self::KEY . '._null';
    return sha1($dsn['database'] . '.' . CRM_Core_Config::domainID() . '.' . $name);
  }

  private function countRows(): int {
    return (int) CRM_Core_DAO::singleValueQuery(
      'SELECT COUNT(*) FROM civicrm_cache WHERE group_name = %1 AND path = %2',
      [1 => [self::GROUP, 'String'], 2 => [self::KEY, 'String']]
    );
  }

}
