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

}
