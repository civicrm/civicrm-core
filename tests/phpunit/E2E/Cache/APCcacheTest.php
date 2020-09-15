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
 * Verify that CRM_Utils_Cache_APCcache complies with PSR-16.
 *
 * @group e2e
 */
class E2E_Cache_APCcacheTest extends E2E_Cache_CacheTestCase {

  public function createSimpleCache() {
    if (!function_exists('apc_store')) {
      $this->markTestSkipped('This environment does not have the APC extension.');
    }

    if (PHP_SAPI === 'cli') {
      $c = (string) ini_get('apc.enable_cli');
      if ($c != 1 && strtolower($c) !== 'on') {
        $this->markTestSkipped('This environment is not configured to use APC cache service. Set apc.enable_cli=on');
      }
    }

    $config = [
      'prefix' => 'foozball/',
    ];
    $c = new CRM_Utils_Cache_APCcache($config);
    return $c;
  }

}
