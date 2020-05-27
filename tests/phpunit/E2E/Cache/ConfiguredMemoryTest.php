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
 * Verify that CRM_Utils_Cache_{Redis,Memcache} complies with PSR-16.
 *
 * NOTE: Only works if the local system is configured to use one of
 * those services.
 *
 * @group e2e
 */
class E2E_Cache_ConfiguredMemoryTest extends E2E_Cache_CacheTestCase {

  /**
   * @return bool
   */
  public static function isMemorySupported() {
    $cache = Civi::cache('default');
    return ($cache instanceof CRM_Utils_Cache_Redis || $cache instanceof CRM_Utils_Cache_Memcache || $cache instanceof CRM_Utils_Cache_Memcached);
  }

  public function createSimpleCache() {
    $isMemorySupported = self::isMemorySupported();
    if ($isMemorySupported) {
      return Civi::cache('default');
    }
    else {
      $this->markTestSkipped('This environment is not configured to use a memory-backed cache service.');
    }
  }

}
