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
 * Tests for CRM_Core_Config::clearDBCache().
 *
 * @group headless
 */
class CRM_Core_Config_ClearDBCacheTest extends CiviUnitTestCase {

  public function tearDown(): void {
    $this->quickCleanup(['civicrm_group_contact_cache', 'civicrm_prevnext_cache', 'civicrm_acl_contact_cache', 'civicrm_acl_cache']);
    parent::tearDown();
  }

  /**
   * The cache tables are emptied and smart group caches are marked stale.
   */
  public function testClearDBCacheEmptiesCacheTables(): void {
    $contactID = $this->individualCreate();
    $groupID = $this->groupCreate();
    CRM_Core_DAO::executeQuery('UPDATE civicrm_group SET cache_date = NOW() WHERE id = %1', [1 => [$groupID, 'Positive']]);
    CRM_Core_DAO::executeQuery('INSERT INTO civicrm_group_contact_cache (group_id, contact_id) VALUES (%1, %2)', [
      1 => [$groupID, 'Positive'],
      2 => [$contactID, 'Positive'],
    ]);
    CRM_Core_DAO::executeQuery("INSERT INTO civicrm_acl_contact_cache (user_id, contact_id, operation, domain_id) VALUES (%1, %1, 'View', 1)", [1 => [$contactID, 'Positive']]);
    CRM_Core_DAO::executeQuery('INSERT INTO civicrm_acl_cache (contact_id, acl_id) VALUES (%1, 1)', [1 => [$contactID, 'Positive']]);
    $this->insertPrevNextRows(3);

    CRM_Core_Config::clearDBCache();

    foreach (['civicrm_group_contact_cache', 'civicrm_acl_contact_cache', 'civicrm_acl_cache', 'civicrm_prevnext_cache'] as $table) {
      $this->assertEquals(0, CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM $table"), "$table should be empty");
    }
    $this->assertNull(CRM_Core_DAO::singleValueQuery('SELECT cache_date FROM civicrm_group WHERE id = %1', [1 => [$groupID, 'Positive']]));
  }

  /**
   * A table with more rows than one chunk is emptied completely.
   */
  public function testDeleteInChunksEmptiesTableAcrossChunks(): void {
    $this->insertPrevNextRows(25);
    CRM_Core_Config::deleteInChunks('civicrm_prevnext_cache', 10);
    $this->assertEquals(0, CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_prevnext_cache'));
  }

  /**
   * An empty table is fine too.
   */
  public function testDeleteInChunksOnEmptyTable(): void {
    CRM_Core_Config::deleteInChunks('civicrm_prevnext_cache', 10);
    $this->assertEquals(0, CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_prevnext_cache'));
  }

  private function insertPrevNextRows(int $count): void {
    for ($i = 1; $i <= $count; $i++) {
      CRM_Core_DAO::executeQuery("INSERT INTO civicrm_prevnext_cache (entity_table, entity_id1, cachekey, data) VALUES ('civicrm_contact', %1, 'ClearDBCacheTest', 'x')", [1 => [$i, 'Positive']]);
    }
  }

}
