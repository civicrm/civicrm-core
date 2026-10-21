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

use Civi\Api4\Dashboard;
use Civi\Api4\Navigation;

/**
 * Tests for 6.21 upgrade logic.
 *
 * @group headless
 */
class CRM_Upgrade_Incremental_php_SixTwentyOneTest extends CiviUnitTestCase {

  protected function tearDown(): void {
    Dashboard::delete(FALSE)
      ->addWhere('name', 'IN', ['report/987', 'report/988'])
      ->execute();
    Navigation::delete(FALSE)
      ->addWhere('name', 'IN', ['Legacy Report Nav 987', 'Legacy Report Nav 988'])
      ->execute();
    parent::tearDown();
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
