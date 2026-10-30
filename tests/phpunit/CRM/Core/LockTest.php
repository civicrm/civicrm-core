<?php

/**
 * @group headless
 */
class CRM_Core_LockTest extends CiviUnitTestCase {

  private ?mysqli $otherConnection = NULL;

  public function tearDown(): void {
    $this->otherConnection = NULL;
    parent::tearDown();
  }

  public function testAcquireWithZeroTimeoutFailsImmediately(): void {
    $name = 'civitest.lock.' . uniqid();
    $this->holdLockInOtherConnection(sha1($name));

    $lock = new CRM_Core_Lock($name, NULL, TRUE);
    $start = microtime(TRUE);
    $this->assertFalse($lock->acquire(0));
    $this->assertLessThan(1, microtime(TRUE) - $start);
  }

  private function holdLockInOtherConnection(string $lockId): void {
    $dsn = DB::parseDSN(CRM_Utils_SQL::autoSwitchDSN(CRM_Core_Config::singleton()->dsn));
    $this->otherConnection = new mysqli($dsn['hostspec'], $dsn['username'], $dsn['password'], $dsn['database'], (int) ($dsn['port'] ?: 3306), $dsn['socket'] ?: NULL);
    $result = $this->otherConnection->query("SELECT GET_LOCK('$lockId', 0)");
    $this->assertEquals(1, $result->fetch_row()[0]);
  }

}
