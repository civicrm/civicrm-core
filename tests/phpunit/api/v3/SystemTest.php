<<<<<<< HEAD
<?php
/*
 +--------------------------------------------------------------------+
| CiviCRM version 4.5                                                |
+--------------------------------------------------------------------+
| Copyright CiviCRM LLC (c) 2004-2014                                |
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

require_once 'CiviTest/CiviUnitTestCase.php';

/**
 * Test class for System API - civicrm_system_*
 *
 * @package   CiviCRM
 */
class api_v3_SystemTest extends CiviUnitTestCase {

  const TEST_CACHE_GROUP = 'SystemTest';
  const TEST_CACHE_PATH = 'api/v3/system';

  /**
   *  Constructor
   *
   *  Initialize configuration
   */
  function __construct() {
    parent::__construct();
  }

  /**
   * Sets up the fixture, for example, opens a network connection.
   * This method is called before a test is executed.
   *
   * @access protected
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   *
   * @access protected
   */
  protected function tearDown() {
    $this->quickCleanup(array('civicrm_system_log'));
  }

  ///////////////// civicrm_domain_get methods

  /**
   * Test system flush
   */
  public function testFlush() {
    // Note: this operation actually flushes several different caches; we don't
    // check all of them -- just enough to make sure that the API is doing
    // something

    $this->assertTrue(NULL === CRM_Core_BAO_Cache::getItem(self::TEST_CACHE_GROUP, self::TEST_CACHE_PATH));

    $data = 'abc';
    CRM_Core_BAO_Cache::setItem($data, self::TEST_CACHE_GROUP, self::TEST_CACHE_PATH);

    $this->assertEquals('abc', CRM_Core_BAO_Cache::getItem(self::TEST_CACHE_GROUP, self::TEST_CACHE_PATH));

    $params = array();
    $result = $this->callAPIAndDocument('system', 'flush', $params, __FUNCTION__, __FILE__, "Flush all system caches", 'Flush', 'flush');

    $this->assertTrue(NULL === CRM_Core_BAO_Cache::getItem(self::TEST_CACHE_GROUP, self::TEST_CACHE_PATH));
  }

  /**
   * Test system log function
   */
  function testSystemLog() {
    $this->callAPISuccess('system', 'log', array('level' => 'info', 'message' => 'We wish you a merry Christmas'));
    $result = $this->callAPISuccess('SystemLog', 'getsingle', array(
        'sequential' => 1,
        'message' => array('LIKE' => '%Chris%')
      ));
    $this->assertEquals($result['message'], 'We wish you a merry Christmas');
    $this->assertEquals($result['level'], 'info');
  }

  /**
   * Test system log function
   */
  function testSystemLogNoLevel() {
    $this->callAPISuccess('system', 'log', array('message' => 'We wish you a merry Christmas', 'level' => 'alert'));
    $result = $this->callAPISuccess('SystemLog', 'getsingle', array(
      'sequential' => 1,
      'message' => array('LIKE' => '%Chris%')
    ));
    $this->assertEquals($result['message'], 'We wish you a merry Christmas');
    $this->assertEquals($result['level'], 'alert');
  }

  function testSystemGet() {
    $result = $this->callAPISuccess('system', 'get', array());
    $this->assertRegExp('/^[0-9]+\.[0-9]+\.[0-9a-z\-]+$/', $result['values'][0]['version']);
    $this->assertEquals('UnitTests', $result['values'][0]['uf']);
  }
}
=======
<?php
/*
 +--------------------------------------------------------------------+
| CiviCRM version 4.7                                                |
+--------------------------------------------------------------------+
| Copyright CiviCRM LLC (c) 2004-2016                                |
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

    $this->assertTrue(NULL === CRM_Core_BAO_Cache::getItem(self::TEST_CACHE_GROUP, self::TEST_CACHE_PATH));

    $data = 'abc';
    CRM_Core_BAO_Cache::setItem($data, self::TEST_CACHE_GROUP, self::TEST_CACHE_PATH);

    $this->assertEquals('abc', CRM_Core_BAO_Cache::getItem(self::TEST_CACHE_GROUP, self::TEST_CACHE_PATH));

    $params = array();
    $result = $this->callAPIAndDocument('system', 'flush', $params, __FUNCTION__, __FILE__, "Flush all system caches", 'Flush');

    $this->assertTrue(NULL === CRM_Core_BAO_Cache::getItem(self::TEST_CACHE_GROUP, self::TEST_CACHE_PATH));
  }

  /**
   * Test system log function.
   */
  public function testSystemLog() {
    $this->callAPISuccess('system', 'log', array('level' => 'info', 'message' => 'We wish you a merry Christmas'));
    $result = $this->callAPISuccess('SystemLog', 'getsingle', array(
      'sequential' => 1,
      'message' => array('LIKE' => '%Chris%'),
    ));
    $this->assertEquals($result['message'], 'We wish you a merry Christmas');
    $this->assertEquals($result['level'], 'info');
  }

  /**
   * Test system log function.
   */
  public function testSystemLogNoLevel() {
    $this->callAPISuccess('system', 'log', array('message' => 'We wish you a merry Christmas', 'level' => 'alert'));
    $result = $this->callAPISuccess('SystemLog', 'getsingle', array(
      'sequential' => 1,
      'message' => array('LIKE' => '%Chris%'),
    ));
    $this->assertEquals($result['message'], 'We wish you a merry Christmas');
    $this->assertEquals($result['level'], 'alert');
  }

  public function testSystemGet() {
    $result = $this->callAPISuccess('system', 'get', array());
    $this->assertRegExp('/^[0-9]+\.[0-9]+\.[0-9a-z\-]+$/', $result['values'][0]['version']);
    $this->assertEquals('UnitTests', $result['values'][0]['uf']);
  }

}
>>>>>>> refs/remotes/civicrm/master
