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
