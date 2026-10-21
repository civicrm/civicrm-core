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

use Civi\Api4\StatusPreference;
use Civi\Api4\Dashboard;
use Civi\Api4\Navigation;

/**
 * Tests for 6.21 upgrade logic.
 *
 * @group headless
 */
class CRM_Upgrade_Incremental_php_SixTwentyOneTest extends CiviUnitTestCase {

  protected function tearDown(): void {
    CRM_Upgrade_Incremental_php_SixTwentyOne::makeStatusPreferenceNameUnique();
    StatusPreference::delete(FALSE)->addWhere('name', 'LIKE', 'upgradeTest%')->execute();
    Dashboard::delete(FALSE)
      ->addWhere('name', 'IN', ['report/987', 'report/988'])
      ->execute();
    Navigation::delete(FALSE)
      ->addWhere('name', 'IN', ['Legacy Report Nav 987', 'Legacy Report Nav 988'])
      ->execute();
    parent::tearDown();
  }

  /**
   * Duplicate status preferences collapse to the oldest row per name and domain.
   */
  public function testMakeStatusPreferenceNameUnique(): void {
    Civi::schemaHelper()->dropIndex('civicrm_status_pref', 'UI_name_domain_id');
    Civi::schemaHelper()->createIndex('civicrm_status_pref', 'UI_status_pref_name', ['fields' => ['name' => TRUE]]);
    $otherDomainId = 2;
    $rows = [
      ['upgradeTestA', 1, 4],
      ['upgradeTestA', 1, 0],
      ['upgradeTestA', $otherDomainId, 0],
      ['upgradeTestB', 1, 3],
    ];
    foreach ($rows as [$name, $domainId, $severity]) {
      CRM_Core_DAO::executeQuery('INSERT INTO civicrm_status_pref (name, domain_id, ignore_severity) VALUES (%1, %2, %3)', [
        1 => [$name, 'String'],
        2 => [$domainId, 'Integer'],
        3 => [$severity, 'Integer'],
      ]);
    }

    CRM_Upgrade_Incremental_php_SixTwentyOne::makeStatusPreferenceNameUnique();

    $remaining = StatusPreference::get(FALSE)
      ->addSelect('name', 'domain_id', 'ignore_severity')
      ->addWhere('name', 'LIKE', 'upgradeTest%')
      ->addOrderBy('id')
      ->execute();
    $this->assertSame([
      ['upgradeTestA', 1, 4],
      ['upgradeTestA', $otherDomainId, 0],
      ['upgradeTestB', 1, 3],
    ], array_map(fn($row) => [$row['name'], $row['domain_id'], $row['ignore_severity']], (array) $remaining));
    $this->assertFalse(Civi::schemaHelper()->indexExists('civicrm_status_pref', 'UI_status_pref_name'));
    $indexes = CRM_Core_BAO_SchemaHandler::getIndexes(['civicrm_status_pref'])['civicrm_status_pref'];
    $this->assertEquals(1, $indexes['UI_name_domain_id']['unique']);
  }

  /**
   * Test that report dashlets and navigation items are upgraded to synthetic permissions.
   */
  public function testReportSyntheticPermissionUpgrade(): void {
    $dashlet = Dashboard::create(FALSE)->setValues([
      'name' => 'report/987',
      'label' => 'Legacy Report Dashlet',
      'url' => 'civicrm/report/instance/987?reset=1',
      'permission' => 'access CiviContribute',
      'permission_operator' => 'OR',
    ])->execute()->first();

    $nav = Navigation::create(FALSE)->setValues([
      'name' => 'Legacy Report Nav 987',
      'label' => 'Legacy Report Nav 987',
      'url' => 'civicrm/report/instance/987?reset=1&output=criteria',
      'permission' => 'access CiviContribute',
      'permission_operator' => 'OR',
    ])->execute()->first();

    $upgrade = new CRM_Upgrade_Form();
    $upgrade->processSQL('6.21.alpha1');

    $updatedDashlet = Dashboard::get(FALSE)
      ->addWhere('id', '=', $dashlet['id'])
      ->execute()
      ->first();
    $this->assertEquals(['@report:987'], (array) $updatedDashlet['permission']);
    $this->assertEquals('AND', $updatedDashlet['permission_operator']);

    $updatedNav = Navigation::get(FALSE)
      ->addWhere('id', '=', $nav['id'])
      ->execute()
      ->first();
    $this->assertEquals(['@report:987'], (array) $updatedNav['permission']);
    $this->assertEquals('AND', $updatedNav['permission_operator']);
  }

}
