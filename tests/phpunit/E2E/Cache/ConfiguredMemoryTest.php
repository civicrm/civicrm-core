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
