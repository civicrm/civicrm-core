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
