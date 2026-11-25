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

use Civi\Api4\WordReplacement;

/**
 * Tests for 6.20 upgrade logic.
 *
 * @group headless
 */
class CRM_Upgrade_Incremental_php_SixTwentyTest extends CiviUnitTestCase {

  protected function tearDown(): void {
    WordReplacement::delete(FALSE)->addWhere('id', '>', 0)->execute();
    CRM_Core_BAO_WordReplacement::rebuild();
    CRM_Core_DAO::executeQuery("UPDATE civicrm_domain SET locale_custom_strings = NULL WHERE id = %1", [
      1 => [CRM_Core_Config::domainID(), 'Integer'],
    ]);
    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_country WHERE name LIKE 'TestDup%'", i18nRewrite: FALSE);
    Civi::schemaHelper()->createIndex('civicrm_country', 'UI_name', [
      'fields' => ['name' => TRUE],
      'unique' => TRUE,
    ]);
    unset(Civi::$statics['CRM_Core_BAO_Domain']['version']);
    parent::tearDown();
  }

  /**
   * Test updateWordReplacements backfills language and migrates domain locale_custom_strings.
   */
  public function testUpdateWordReplacements(): void {
    $domainId = CRM_Core_Config::domainID();

    // 1. Insert a legacy row with NULL language (simulating pre-6.20 table state)
    CRM_Core_DAO::executeQuery("
      INSERT INTO civicrm_word_replacement (find_word, replace_word, is_active, match_type, domain_id, language)
      VALUES ('OldTerm', 'NewTerm', 1, 'wildcardMatch', %1, NULL)
    ", [
      1 => [$domainId, 'Integer'],
    ]);

    // 2. Set serialized locale_custom_strings on civicrm_domain
    $lcs = [
      'es_MX' => [
        'enabled' => [
          'exactMatch' => [
            'Staff' => 'Personal',
          ],
        ],
      ],
    ];
    CRM_Core_DAO::executeQuery("
      UPDATE civicrm_domain SET locale_custom_strings = %1 WHERE id = %2
    ", [
      1 => [serialize($lcs), 'String'],
      2 => [$domainId, 'Integer'],
    ]);

    // 3. Run the upgrader task
    $ctx = new CRM_Queue_TaskContext();
    $result = CRM_Upgrade_Incremental_php_SixTwenty::updateWordReplacements($ctx);
    $this->assertTrue($result);

    // 4. Verify the row with NULL language was backfilled with en_US
    $legacyRow = WordReplacement::get(FALSE)
      ->addWhere('find_word', '=', 'OldTerm')
      ->execute()
      ->first();
    $this->assertNotEmpty($legacyRow);
    $this->assertEquals('en_US', $legacyRow['language']);

    // 5. Verify the domain serialized string was migrated to civicrm_word_replacement
    $migratedRow = WordReplacement::get(FALSE)
      ->addWhere('find_word', '=', 'Staff')
      ->addWhere('language', '=', 'es_MX')
      ->execute()
      ->first();
    $this->assertNotEmpty($migratedRow);
    $this->assertEquals('Personal', $migratedRow['replace_word']);
    $this->assertEquals('exactMatch', $migratedRow['match_type']);
  }

  /**
   * Test getLocaleCustomStrings falls back to domain locale_custom_strings on pre-6.20 DB.
   */
  public function testGetLocaleCustomStringsPreSixTwenty(): void {
    $domainId = CRM_Core_Config::domainID();

    $lcs = [
      'fr_CA' => [
        'enabled' => [
          'wildcardMatch' => [
            'Volunteer' => 'Bénévole',
          ],
        ],
      ],
    ];
    CRM_Core_DAO::executeQuery("
      UPDATE civicrm_domain SET locale_custom_strings = %1 WHERE id = %2
    ", [
      1 => [serialize($lcs), 'String'],
      2 => [$domainId, 'Integer'],
    ]);

    // Simulate pre-6.20 DB version
    Civi::$statics['CRM_Core_BAO_Domain']['version'] = '5.69.5';

    $result = CRM_Core_BAO_WordReplacement::getLocaleCustomStrings('fr_CA');
    $this->assertEquals([
      'enabled' => [
        'wildcardMatch' => [
          'Volunteer' => 'Bénévole',
        ],
      ],
    ], $result);
  }

  /**
   * Test ensureCountryNameUnique disambiguates duplicate country names and creates UI_name.
   */
  public function testEnsureCountryNameUnique(): void {
    Civi::schemaHelper()->dropIndex('civicrm_country', 'UI_name');
    $this->assertFalse(Civi::schemaHelper()->indexExists('civicrm_country', 'UI_name'));

    // 1. Duplicate name where inactive row has lower id: active row keeps name, inactive is suffixed.
    CRM_Core_DAO::executeQuery("
      INSERT INTO civicrm_country (name, iso_code, country_code, region_id, is_active)
      VALUES ('TestDupActive', 'T1', NULL, 1, 0)
    ", i18nRewrite: FALSE);
    $inactiveCountryId = (int) CRM_Core_DAO::singleValueQuery("SELECT LAST_INSERT_ID()");

    CRM_Core_DAO::executeQuery("
      INSERT INTO civicrm_country (name, iso_code, country_code, region_id, is_active)
      VALUES ('TestDupActive', 'T2', NULL, 1, 1)
    ", i18nRewrite: FALSE);
    $activeCountryId = (int) CRM_Core_DAO::singleValueQuery("SELECT LAST_INSERT_ID()");

    // 2. Duplicate 64-char name where both are active: lower id keeps name, higher id is truncated + suffixed.
    $longName = str_pad('TestDupLong', 64, 'X');
    CRM_Core_DAO::executeQuery("
      INSERT INTO civicrm_country (name, iso_code, country_code, region_id, is_active)
      VALUES (%1, 'T3', '33', 1, 1)
    ", [1 => [$longName, 'String']], i18nRewrite: FALSE);
    $longKeepId = (int) CRM_Core_DAO::singleValueQuery("SELECT LAST_INSERT_ID()");

    CRM_Core_DAO::executeQuery("
      INSERT INTO civicrm_country (name, iso_code, country_code, region_id, is_active)
      VALUES (%1, 'T4', '44', 1, 1)
    ", [1 => [$longName, 'String']], i18nRewrite: FALSE);
    $longRenameId = (int) CRM_Core_DAO::singleValueQuery("SELECT LAST_INSERT_ID()");

    // Run upgrader task
    $ctx = new CRM_Queue_TaskContext();
    $this->assertTrue(CRM_Upgrade_Incremental_php_SixTwenty::ensureCountryNameUnique($ctx));
    $this->assertTrue(Civi::schemaHelper()->indexExists('civicrm_country', 'UI_name'));

    // Verify Case 1: active row keeps original name, inactive row is suffixed.
    $this->assertSame('TestDupActive', CRM_Core_DAO::singleValueQuery(
      "SELECT name FROM civicrm_country WHERE id = %1",
      [1 => [$activeCountryId, 'Integer']]
    ));
    $this->assertSame("TestDupActive ({$inactiveCountryId})", CRM_Core_DAO::singleValueQuery(
      "SELECT name FROM civicrm_country WHERE id = %1",
      [1 => [$inactiveCountryId, 'Integer']]
    ));

    // Verify Case 2: long name kept on first row, truncated + suffixed on second row.
    $this->assertSame($longName, CRM_Core_DAO::singleValueQuery(
      "SELECT name FROM civicrm_country WHERE id = %1",
      [1 => [$longKeepId, 'Integer']]
    ));
    $renamedLong = CRM_Core_DAO::singleValueQuery(
      "SELECT name FROM civicrm_country WHERE id = %1",
      [1 => [$longRenameId, 'Integer']]
    );
    $this->assertLessThanOrEqual(64, strlen($renamedLong));
    $this->assertStringEndsWith(" ({$longRenameId})", $renamedLong);
  }

}
