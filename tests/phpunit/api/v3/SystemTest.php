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
| License and the CiviCRM Licensing Exception along                  |
| with this program; if not, contact CiviCRM LLC                     |
| at info[AT]civicrm[DOT]org. If you have questions about the        |
| GNU Affero General Public License or the licensing of CiviCRM,     |
| see the CiviCRM license FAQ at http://civicrm.org/licensing        |
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

}
