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

namespace Civi\tests\phpunit\api\v4\Entity;

use api\v4\Api4TestBase;
use Civi\Api4\Dashboard;
use Civi\Api4\DashboardContact;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class DashboardTest extends Api4TestBase implements TransactionalInterface {

  /**
   * Test row-level access controls for Dashboard entity via APIv4
   */
  public function testApiRowLevelAccess(): void {
    // With checkPermissions = TRUE, only permitted dashboards should return
    $dashboards = Dashboard::get(TRUE)
      ->addSelect('name')
      ->execute()
      ->column('name');

    $this->assertTrue(in_array('blog', $dashboards));
    $this->assertFalse(in_array('myCases', $dashboards));

    // With checkPermissions = FALSE, all dashboards should return
    $allDashboards = Dashboard::get(FALSE)
      ->addSelect('name')
      ->execute()
      ->column('name');

    $this->assertTrue(in_array('blog', $allDashboards));
    $this->assertTrue(in_array('myCases', $allDashboards));
  }

  /**
   * Test DashboardContact::initialize APIv4 action.
   */
  public function testApiInitialize(): void {
    $userCid = $this->createLoggedInUser();

    // Delete existing dashboard contacts for the logged-in user to clean state
    DashboardContact::delete(FALSE)
      ->addWhere('contact_id', '=', $userCid)
      ->execute();

    // Call initialize APIv4 action
    $result = DashboardContact::initialize(TRUE)
      ->execute();

    // Check it initialized default dashlets
    $this->assertNotEmpty($result);
    $savedDashlets = DashboardContact::get(FALSE)
      ->addWhere('contact_id', '=', $userCid)
      ->execute()
      ->column('dashboard_id');
    $this->assertCount(2, $savedDashlets);

    // Add another dashlet
    $newDashlet = \Civi\Api4\Dashboard::get(FALSE)
      ->addWhere('name', 'NOT IN', ['blog', 'getting-started'])
      ->addWhere('domain_id', '=', 'current_domain')
      ->addWhere('is_active', '=', TRUE)
      ->setLimit(1)
      ->execute()
      ->single();
    DashboardContact::create(FALSE)
      ->addValue('contact_id', $userCid)
      ->addValue('column_no', 2)
      ->addValue('dashboard_id', $newDashlet['id'])
      ->execute();

    // Now we have 3 dashlets
    $savedDashlets = DashboardContact::get(FALSE)
      ->addWhere('contact_id', '=', $userCid)
      ->execute();
    $this->assertCount(3, $savedDashlets);

    // Calling initialize again without force should NOT create duplicates (returns empty result)
    $resultSecond = DashboardContact::initialize(TRUE)
      ->execute();
    $this->assertEmpty($resultSecond);

    $savedDashlets = DashboardContact::get(FALSE)
      ->addWhere('contact_id', '=', $userCid)
      ->execute();
    $this->assertCount(3, $savedDashlets);

    // Calling initialize again with force should re-initialize
    $resultForce = DashboardContact::initialize(TRUE)
      ->setForce(TRUE)
      ->execute();
    $this->assertCount(2, $resultForce);
    $savedDashlets = DashboardContact::get(FALSE)
      ->addWhere('contact_id', '=', $userCid)
      ->execute()
      ->column('dashboard_id');
    $this->assertCount(2, $savedDashlets);

    // Remove 'administer CiviCRM' permission
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];

    $otherContactId = $this->createTestRecord('Contact')['id'];
    // Non-admin attempting to initialize dashboard for another contact should fail authorization
    try {
      DashboardContact::initialize(TRUE)
        ->setContactId($otherContactId)
        ->execute();
      $this->fail('Expected UnauthorizedException when initializing dashboard for another contact');
    }
    catch (\Civi\API\Exception\UnauthorizedException $e) {
      $this->assertTrue(TRUE);
    }

    // Grant administer CiviCRM
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'administer CiviCRM'];

    // Admin should be able to initialize dashboard for another contact
    $resultAdmin = DashboardContact::initialize(TRUE)
      ->setContactId($otherContactId)
      ->execute();
    $this->assertNotEmpty($resultAdmin);
  }

}
