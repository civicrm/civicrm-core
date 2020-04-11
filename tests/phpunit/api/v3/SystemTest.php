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
 * Test class for System API - civicrm_system_*
 *
 * @package   CiviCRM
 * @group headless
 */
class api_v3_SystemTest extends CiviUnitTestCase {

  const TEST_CACHE_GROUP = 'SystemTest';
  const TEST_CACHE_PATH = 'api/v3/system';

  /**
   * Sets up the fixture, for example, opens a network connection.
   *
   * This method is called before a test is executed.
   */
  protected function setUp() {
    parent::setUp();
    $this->useTransaction(TRUE);
  }

  /**
   * Test system flush.
   */
  public function testFlush() {
    // Note: this operation actually flushes several different caches; we don't
    // check all of them -- just enough to make sure that the API is doing
    // something

    $this->assertTrue(NULL === Civi::cache()->get(CRM_Utils_Cache::cleanKey(self::TEST_CACHE_PATH)));

    $data = 'abc';
    Civi::cache()->set(CRM_Utils_Cache::cleanKey(self::TEST_CACHE_PATH), $data);

    $this->assertEquals('abc', Civi::cache()->get(CRM_Utils_Cache::cleanKey(self::TEST_CACHE_PATH)));

    $params = [];
    $result = $this->callAPIAndDocument('system', 'flush', $params, __FUNCTION__, __FILE__, "Flush all system caches", 'Flush');

    $this->assertTrue(NULL === Civi::cache()->get(CRM_Utils_Cache::cleanKey(self::TEST_CACHE_PATH)));
  }

  /**
   * Test system log function.
   */
  public function testSystemLog() {
    $this->callAPISuccess('system', 'log', ['level' => 'info', 'message' => 'We wish you a merry Christmas']);
    $result = $this->callAPISuccess('SystemLog', 'getsingle', [
      'sequential' => 1,
      'message' => ['LIKE' => '%Chris%'],
    ]);
    $this->assertEquals($result['message'], 'We wish you a merry Christmas');
    $this->assertEquals($result['level'], 'info');
  }

  /**
   * Test system log function.
   */
  public function testSystemLogNoLevel() {
    $this->callAPISuccess('system', 'log', ['message' => 'We wish you a merry Christmas', 'level' => 'alert']);
    $result = $this->callAPISuccess('SystemLog', 'getsingle', [
      'sequential' => 1,
      'message' => ['LIKE' => '%Chris%'],
    ]);
    $this->assertEquals($result['message'], 'We wish you a merry Christmas');
    $this->assertEquals($result['level'], 'alert');
  }

  public function testSystemGet() {
    $result = $this->callAPISuccess('system', 'get', []);
    $this->assertRegExp('/^[0-9]+\.[0-9]+\.[0-9a-z\-]+$/', $result['values'][0]['version']);
    $this->assertEquals('UnitTests', $result['values'][0]['uf']);
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testSystemUTFMB8Conversion() {
    if (version_compare(CRM_Utils_SQL::getDatabaseVersion(), '5.7', '>=')) {
      $this->callAPISuccess('System', 'utf8conversion', []);
      $table = CRM_Core_DAO::executeQuery('SHOW CREATE TABLE civicrm_contact');
      $table->fetch();
      $this->assertStringEndsWith('DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC', $table->Create_Table);

      $this->callAPISuccess('System', 'utf8conversion', ['is_revert' => 1]);
      $table = CRM_Core_DAO::executeQuery('SHOW CREATE TABLE civicrm_contact');
      $table->fetch();
      $this->assertStringEndsWith('DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC', $table->Create_Table);
    }
    else {
      $this->markTestSkipped('MySQL Version does not support ut8mb4 testing');
    }
  }

}
