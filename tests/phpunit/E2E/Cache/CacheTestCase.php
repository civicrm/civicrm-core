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
 | License along with this program; if not, contact CiviCRM LLC       |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 * Verify that a cache service complies with PSR-16.
 *
 * @group e2e
 */
abstract class E2E_Cache_CacheTestCase extends \Cache\IntegrationTests\SimpleCacheTest implements \Civi\Test\EndToEndInterface {

  const MAX_KEY = 255;

  public static function setUpBeforeClass() {
    CRM_Core_Config::singleton(1, 1);
    CRM_Utils_System::loadBootStrap(array(
      'name' => $GLOBALS['_CV']['ADMIN_USER'],
      'pass' => $GLOBALS['_CV']['ADMIN_PASS'],
    ));
    CRM_Utils_System::synchronizeUsers();

    parent::setUpBeforeClass();
  }

  public function testBasicUsageWithLongKey() {
    if (isset($this->skippedTests[__FUNCTION__])) {
      $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
    }

    // Upstream test hardcodes 300, which is more permissive than PSR-16.
    $key = str_repeat('a', self::MAX_KEY);

    $this->assertFalse($this->cache->has($key));
    $this->assertTrue($this->cache->set($key, 'value'));

    $this->assertTrue($this->cache->has($key));
    $this->assertSame('value', $this->cache->get($key));

    $this->assertTrue($this->cache->delete($key));

    $this->assertFalse($this->cache->has($key));
  }

}
