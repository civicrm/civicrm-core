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
 * Verify that CRM_Utils_Cache_SqlGroup complies with PSR-16.
 *
 * @group e2e
 */
class E2E_Cache_SqlGroupTest extends E2E_Cache_CacheTestCase {

  public function createSimpleCache() {
    return CRM_Utils_Cache::create([
      'name' => 'e2e sqlgroup test',
      'type' => ['SqlGroup'],
    ]);
  }

}
