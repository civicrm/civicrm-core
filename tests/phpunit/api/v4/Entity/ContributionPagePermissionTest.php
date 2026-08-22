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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

namespace api\v4\Entity;

use api\v4\Api4TestBase;
use Civi\Api4\ContributionPage;
use Civi\Test\TransactionalInterface;

/**
 * Tests the 'edit all contribution pages' permission.
 *
 * @group headless
 */
class ContributionPagePermissionTest extends Api4TestBase implements TransactionalInterface {

  /**
   * Test that the new permission grants the full create/get/update/delete cycle
   */
  public function testManageContributionPages(): void {
    $this->setPermissions(['access CiviCRM', 'access CiviContribute', 'edit all contribution pages']);
    $this->assertFullAccess();
  }

  /**
   * Test scenarios that would result in insufficient permissions
   *
   * @dataProvider insufficientAdminRoles
   */
  public function testRequireAccessCiviContribute(): void {
    $this->setPermissions(['access CiviCRM', 'edit all contribution pages']);
    $this->expectException(\Civi\API\Exception\UnauthorizedException::class);
    ContributionPage::get()
      ->execute()
      ->single();
  }

  /**
   * These roles could reach the contribution page screens before the permission
   * existed, so they must keep working after an upgrade.
   *
   * @dataProvider preExistingAdminRoles
   */
  public function testNoRegressionForExistingAdminRoles(array $permissions): void {
    $this->setPermissions($permissions);
    $this->assertFullAccess();
  }

  public static function insufficientAdminRoles(): array {
    return [
      'missing edit all contrib pages' => [['access CiviCRM', 'access CiviContribute']],
      'missing access civicontribute' => [['access CiviCRM', 'administer CiviCRM data']],
    ];
  }

  public static function preExistingAdminRoles(): array {
    return [
      'administer CiviCRM' => [['access CiviCRM', 'access CiviContribute', 'administer CiviCRM']],
      'administer CiviCRM data' => [['access CiviCRM', 'access CiviContribute', 'administer CiviCRM data']],
    ];
  }

  private function assertFullAccess(): void {
    $id = ContributionPage::create()
      ->addValue('title', 'Managed page')
      ->addValue('financial_type_id', 1)
      ->execute()
      ->single()['id'];

    $page = ContributionPage::get()
      ->addWhere('id', '=', $id)
      ->execute()
      ->single();
    $this->assertEquals('Managed page', $page['title']);

    ContributionPage::update()
      ->addWhere('id', '=', $id)
      ->addValue('title', 'Renamed page')
      ->execute();

    ContributionPage::delete()
      ->addWhere('id', '=', $id)
      ->execute();

    $this->assertCount(0, ContributionPage::get()->addWhere('id', '=', $id)->execute());
  }

  private function setPermissions(array $permissions): void {
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = $permissions;
    // The implied-permission map is statically cached; drop it so that
    // 'administer CiviCRM data' implying 'edit all contribution pages' is recomputed.
    unset(\Civi::$statics['CRM_Core_Permission']['basicPermissions']);
  }

}
